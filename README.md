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

## Kling O1 reference media

```php
$result = $client->textToVideo->run([
    'model' => 'kling-o1',
    'prompt' => 'Keep <<<image_1>>> beside the performer from <<<video_1>>>',
    'reference_image_urls' => ['https://cdn.runapi.ai/public/samples/portrait.jpg'],
    'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
    'reference_video_type' => 'feature',
    'preserve_reference_video_audio' => true,
    'mode' => 'pro',
    'duration_seconds' => 5,
]);
```

Number reference images in prompt order as `<<<image_1>>>`, `<<<image_2>>>`, and so on; the optional video is `<<<video_1>>>`. With a video, send at most four images. Do not combine `last_frame_image_url` with reference images or a reference video. A `feature` reference video may be used with the required first frame; `base` cannot be combined with frame inputs. O1 requests are five seconds and keep sound disabled. Pricing and limits: https://runapi.ai/models/kling/o1.

## Kling V3 Omni source-video editing

Use `kling-v3-omni-reference` with `textToVideo` for image-only reference inputs. For a source video, use `editVideo` with either `kling-v3-omni-reference` or `kling-v3-omni-edit`. The reference model keeps sound disabled for source-video requests.

Use `create()` to submit a task and return quickly, `get()` to fetch the latest task state, and `run()` when a script should create and poll until completion. In web request handlers, prefer `create()` plus webhook or later `get()` polling so a worker is not held open.

Returned file URLs are temporary. Download and store generated files in your own durable storage within the retention window.

All SDK exceptions inherit from `RunApi\Core\Errors\RunApiException`, including validation, authentication, rate limit, task failure, and task timeout errors.

## Links

- Model page: https://runapi.ai/models/kling
- SDK docs: https://runapi.ai/docs/resources/sdks
- Product docs: https://runapi.ai/docs/api/kling/text-to-video
- Pricing and rate limits: https://runapi.ai/models/kling/3.0
- Full catalog: https://runapi.ai/models
- GitHub repository: https://github.com/runapi-ai/kling-php
- Multi-language SDK repository: https://github.com/runapi-ai/kling-sdk

## License

Licensed under the Apache License, Version 2.0.
