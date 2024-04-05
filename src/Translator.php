<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

use DateTime;
use Exception;
use Iso639p3\Iso639p3;
use JsonException;
use const JSON_THROW_ON_ERROR;

/**
 * Wrapper for the Reverso API for language translation.
 * Create an instance of Translator to use the Reverso API.
 */
class Translator
{
    /**
     * Library version.
     */
    public const VERSION = '1.0.0';

    /**
     * Implements all HTTP requests and retries.
     */
    private $client;

    /**
     * Construct a Translator object wrapping the Reverso API using your username, password key.
     * This does not connect to the API, and returns immediately.
     * @param string $url Translator API url.
     * @param string $username Authentication username.
     * @param string $password Authentication password.
     * @param array $options Additional options controlling Translator behaviour.
     * @throws ReversoException
     * @see TranslatorOptions for a list of available request options.
     */
    public function __construct(string $url, string $username, string $password, array $options = [])
    {
        if ($username === '' || $password === '') {
            $fields = [];
            if ($username === '') {
                $fields[] = 'username';
            }
            if ($password === '') {
                $fields[] = 'password';
            }
            throw new ReversoException(implode(' and ', $fields) . ' must be a non-empty string');
        }
        // Validation is currently only logging warnings
        TranslatorOptions::isValid($options);

        $serverUrl = $options[TranslatorOptions::SERVER_URL] ?? $url ?: TranslatorOptions::DEFAULT_SERVER_URL;
        if (!is_string($serverUrl) || strlen($serverUrl) == 0) {
            throw new ReversoException('If specified, ' .
                TranslatorOptions::SERVER_URL . ' option must be a non-empty string.');
        } elseif (substr($serverUrl, -1) === "/") { // Remove trailing slash if present
            $serverUrl = substr($serverUrl, 0, strlen($serverUrl) - 1);
        }

        $created = new DateTime();
        $created = $created->format('Y-m-d H:i:s');
        $signature = $this->getSignature($username, $created, $password);

        $headers = array_replace(
            [
                'Created' => $created,
                'Username' => $username,
                'Signature' => $signature,
                'User-Agent' => self::constructUserAgentString(
                    $options[TranslatorOptions::SEND_PLATFORM_INFO] ?? true,
                    $options[TranslatorOptions::APP_INFO] ?? null
                ),
            ],
            $options[TranslatorOptions::HEADERS] ?? []
        );

        $timeout = $options[TranslatorOptions::TIMEOUT] ?? TranslatorOptions::DEFAULT_TIMEOUT;

        $maxRetries = $options[TranslatorOptions::MAX_RETRIES] ?? TranslatorOptions::DEFAULT_MAX_RETRIES;

        $logger = $options[TranslatorOptions::LOGGER] ?? null;

        $proxy = $options[TranslatorOptions::PROXY] ?? null;

        $http_client = $options[TranslatorOptions::HTTP_CLIENT] ?? null;

        $this->client = new HttpClientWrapper(
            $serverUrl,
            $headers,
            $timeout,
            $maxRetries,
            $logger,
            $proxy,
            $http_client
        );
    }

    /**
     * Queries source languages supported by the Reverso API.
     * @return Language[] Array of Language objects containing available source languages.
     * @throws ReversoException
     */
    public function getSourceLanguages(): array
    {
        return $this->getLanguages(false);
    }

    /**
     * Queries target languages supported by the Reverso API.
     * @return Language[] Array of Language objects containing available target languages.
     * @throws ReversoException
     */
    public function getTargetLanguages(): array
    {
        return $this->getLanguages(true);
    }

    /**
     * Translates specified text string into the target language.
     * @param string $text A single string containing input text to translate.
     * @param string|null $sourceLang Language code of input text language, or null to use auto-detection.
     * @param string $targetLang Language code of language to translate into.
     * @param array $options Translation options to apply. See TranslateTextOptions.
     * @return TextResult A TextResult object containing translated texts.
     * @throws ReversoException
     * @see TranslateTextOptions
     */
    public function translateText(string $text, ?string $sourceLang, string $targetLang, array $options = []): TextResult
    {
        $source = Iso639p3::code($sourceLang);
        $target = Iso639p3::code($targetLang);

        $params = [];
        $this->validateAndAppendBody($params, $text);
        $options = !empty($options) ? '?' . http_build_query($options) : '';

        $response = $this->client->sendRequestWithBackoff(
            'POST',
            '/v1/TranslateText/direction=' . $source . '-' . $target . $options,
            [HttpClientWrapper::OPTION_PARAMS => $params]
        );
        $this->checkStatusCode($response);

        list(, $content) = $response;
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $text = $decoded['TranslatedText'];
        $truncated = $decoded['Truncated'];
        $wordsLeft = $decoded['WordsLeft'];
        return new TextResult($text, $truncated, $wordsLeft);
    }

    /**
     * Translates specified html string into the target language.
     * @param string $text A single string containing input text to translate.
     * @param string|null $sourceLang Language code of input text language, or null to use auto-detection.
     * @param string $targetLang Language code of language to translate into.
     * @param array $options Translation options to apply. See TranslateTextOptions.
     * @return TextResult A TextResult object containing translated texts.
     * @throws ReversoException
     * @see TranslateTextOptions
     */
    public function translateHtml(string $text, ?string $sourceLang, string $targetLang, array $options = []): TextResult
    {
        $source = Iso639p3::code($sourceLang);
        $target = Iso639p3::code($targetLang);

        $params = [];
        $this->validateAndAppendBody($params, base64_encode($text));
        $options = !empty($options) ? '?' . http_build_query($options) : '';

        $response = $this->client->sendRequestWithBackoff(
            'POST',
            '/v1/TranslateHtml/direction=' . $source . '-' . $target . $options,
            [HttpClientWrapper::OPTION_PARAMS => $params]
        );
        $this->checkStatusCode($response);

        list(, $content) = $response;
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $text = base64_decode($decoded['TranslatedText']);
        $truncated = $decoded['Truncated'];
        $wordsLeft = $decoded['WordsLeft'];
        return new TextResult($text, $truncated, $wordsLeft);
    }

    /**
     * Queries source or target languages supported by the Reverso API.
     * @param bool $target Query target languages if true, source languages otherwise.
     * @return Language[] Array of Language objects containing available languages.
     * @throws ReversoException
     */
    public function getLanguages(bool $target): array
    {
        $response = $this->client->sendRequestWithBackoff(
            'GET',
            '/v1/GetAllTranslationDirections'
        );
        $this->checkStatusCode($response);
        list(, $content) = $response;

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $result = [];
        foreach ($decoded['Directions'] as $direction) {
            if (!$target) {
                $language = Iso639p3::code2letters($direction['srcLanguageCode']);
            } else {
                $language = Iso639p3::code2letters($direction['destLanguageCode']);
            }
            $name = Iso639p3::englishName($language);
            $code = Iso639p3::code2letters($language);
            $codeLong = Iso639p3::code($language);
            $result[] = new Language($name, $code, $codeLong);
        }
        return array_unique($result);
    }

    /**
     * Validates and appends text to HTTP request body.
     * @param array $params Parameters for HTTP request.
     * @param string $text User-supplied text to be checked.
     * @throws ReversoException
     */
    private function validateAndAppendBody(array &$params, $text)
    {
        if (!is_string($text)) {
            throw new ReversoException(
                'text parameter must be a string',
            );
        }
        $params['body'] = $text;
    }

    /**
     * Checks the HTTP status code, and in case of failure, throws an exception with diagnostic information.
     * @throws ReversoException
     */
    private function checkStatusCode(array $response)
    {
        list($statusCode, $content) = $response;

        if (200 <= $statusCode && $statusCode < 400) {
            return;
        }

        $message = '';
        try {
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (isset($json['message'])) {
                $message .= ", message: {$json['message']}";
            }
            if (isset($json['detail'])) {
                $message .= ", detail: {$json['detail']}";
            }
        } catch (Exception $e) {
            // JSON parsing errors are ignored, and we fall back to the raw response
            $message = ", $content";
        }

        switch ($statusCode) {
            case 403:
                throw new AuthorizationException("Authorization failure, check authentication key$message");
            case 404:
                throw new NotFoundException("Not found, check server_url$message");
            case 400:
                throw new ReversoException("Bad request$message");
            case 429:
                throw new TooManyRequestsException("Too many requests, Reverso servers are currently experiencing high load$message");
            case 503:
                throw new ReversoException("Service unavailable$message");
            default:
                throw new ReversoException("Unexpected status code: $statusCode $message, content: $content.");
        }
    }

    private static function constructUserAgentString(bool $sendPlatformInfo, ?AppInfo $appInfo): string
    {
        $libraryVersion = self::VERSION;
        $libraryInfoStr = "reversp-php/$libraryVersion";
        try {
            if ($sendPlatformInfo) {
                $platformStr = php_uname('s r v m');
                $phpVersion = phpversion();
                $libraryInfoStr .= " ($platformStr) php/$phpVersion";
                $curlVer = curl_version()['version'];
                $libraryInfoStr .= " curl/$curlVer";
            }
            if (!is_null($appInfo)) {
                $libraryInfoStr .= " $appInfo->appName/$appInfo->appVersion";
            }
        } catch (Exception $e) {
            // Do not fail request, simply send req with an incomplete user agent string
        }
        return $libraryInfoStr;
    }

    private function getSignature($username, $created, $password): string
    {
        return hash_hmac('sha1', $username . $created, $password);
    }
}
