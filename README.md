# reverso-php

[![License: MIT](https://img.shields.io/badge/license-MIT-blueviolet.svg)](https://github.com/TreehillStudio/Reverso/blob/main/LICENSE.md)

Inofficial PHP client library for the Reverso API.

This code bases on the code of [deepl-php](https://github.com/DeepLcom/deepl-php) and was modified for the use of the Reverso API. It currently supports the TranslateText, the TranslateHtml and the GetAllTranslationDirections methods.

## Caution
The Reverso API responses are quite nasty. If the API does not get the right credentials in the it will create a certificate problem with cURL: certificate problem: unable to get local issuer certificate.
