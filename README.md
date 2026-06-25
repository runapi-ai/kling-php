# Kling PHP SDK for RunAPI

[![Packagist](https://img.shields.io/packagist/v/runapi-ai/kling)](https://packagist.org/packages/runapi-ai/kling)
[![License](https://img.shields.io/github/license/runapi-ai/kling-php)](https://github.com/runapi-ai/kling-php/blob/main/LICENSE)

The Kling PHP SDK is the Composer package for Kling on RunAPI. Use it when your PHP application needs associative-array request bodies, task status lookup, polling helpers, file helpers, and consistent RunAPI errors.

## Install

```bash
composer require runapi-ai/kling
```

## Quick start

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use RunApi\Kling\KlingClient;

$client = new KlingClient(); // reads RUNAPI_API_KEY

$task = $client->textToVideo->create([
    'model' => 'kling-3.0',
    'prompt' => 'A cat walking through a garden',
]);

$status = $client->textToVideo->get($task->id);

$result = $client->textToVideo->run([
    'model' => 'kling-3.0',
    'prompt' => 'A cinematic drone shot over a misty forest',
]);

echo $result->videos[0]->url . PHP_EOL;
```

Use `create()` to submit a task and return quickly, `get()` to fetch the latest task state, and `run()` when a script should create and poll until completion. In web request handlers, prefer `create()` plus webhook or later `get()` polling so a worker is not held open.

Returned file URLs are temporary. Download and store generated files in your own durable storage within the retention window.

All SDK exceptions inherit from `RunApi\Core\Errors\RunApiException`, including validation, authentication, rate limit, task failure, and task timeout errors.

## Links

- Model page: https://runapi.ai/models/kling
- SDK docs: https://runapi.ai/docs#sdk-kling
- Product docs: https://runapi.ai/docs#kling
- Pricing and rate limits: https://runapi.ai/models/kling/3.0
- Full catalog: https://runapi.ai/models
- GitHub repository: https://github.com/runapi-ai/kling-php
- Multi-language SDK repository: https://github.com/runapi-ai/kling-sdk

## License

Licensed under the Apache License, Version 2.0.
