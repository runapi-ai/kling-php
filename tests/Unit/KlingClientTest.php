<?php

declare(strict_types=1);

namespace RunApi\Kling\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RunApi\Core\ClientOptions;
use RunApi\Core\Errors\TaskFailedException;
use RunApi\Core\Errors\TaskTimeoutException;
use RunApi\Core\Errors\ValidationException;
use RunApi\Core\Polling\Poller;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\Account;
use RunApi\Core\Resources\Files;
use RunApi\Core\Tests\Fixtures\QueueHttpClient;
use RunApi\Kling\KlingClient;
use RunApi\Kling\Models\CompletedTextToVideoResponse;
use RunApi\Kling\Resources\AiAvatar;
use RunApi\Kling\Resources\ImageToVideo;
use RunApi\Kling\Resources\MotionControl;
use RunApi\Kling\Resources\TextToVideo;
use RunApi\Kling\Types;

final class KlingClientTest extends TestCase
{
    public function testExposesProviderAndUniversalResources(): void
    {
        $client = $this->client();

        self::assertInstanceOf(TextToVideo::class, $client->textToVideo);
        self::assertInstanceOf(ImageToVideo::class, $client->imageToVideo);
        self::assertInstanceOf(AiAvatar::class, $client->aiAvatar);
        self::assertInstanceOf(MotionControl::class, $client->motionControl);
        self::assertInstanceOf(Files::class, $client->files);
        self::assertInstanceOf(Account::class, $client->account);
    }

    public function testTextToVideoCreatePostsCompactedBodyAndOptionsHeaders(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
        ]);
        $client = $this->client($transport);

        $task = $client->textToVideo->create([
            'model' => 'kling-3.0',
            'prompt' => 'A cat walking through a garden',
            'aspect_ratio' => '16:9',
            'callback_url' => '',
            'kling_elements' => [],
        ], new RequestOptions(headers: ['X-Test' => 'yes']));

        self::assertSame('task_123', $task->id);
        $request = $transport->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/v1/kling/text_to_video', $request->getUri()->getPath());
        self::assertSame('yes', $request->getHeaderLine('X-Test'));
        self::assertSame(
            '{"model":"kling-3.0","prompt":"A cat walking through a garden","aspect_ratio":"16:9"}',
            (string) $request->getBody(),
        );
    }

    public function testTextToVideoGetFetchesTaskById(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_abc","status":"processing"}'),
        ]);
        $client = $this->client($transport);

        $response = $client->textToVideo->get('task_abc');

        self::assertSame('GET', $transport->requests[0]->getMethod());
        self::assertSame('/api/v1/kling/text_to_video/task_abc', $transport->requests[0]->getUri()->getPath());
        self::assertSame('processing', $response->status);
        self::assertSame('task_abc', $response->id);
    }

    public function testTextToVideoRunPollsUntilCompleted(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"processing"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"completed","videos":[{"url":"https://file.runapi.ai/video.mp4"}]}'),
        ]);
        $resource = $this->textToVideo($transport);

        $response = $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest',
        ], new RequestOptions(pollIntervalSeconds: 0.0));

        self::assertInstanceOf(CompletedTextToVideoResponse::class, $response);
        self::assertSame('completed', $response->status);
        self::assertSame('https://file.runapi.ai/video.mp4', $response->videos[0]->url);
        self::assertSame('/api/v1/kling/text_to_video', $transport->requests[0]->getUri()->getPath());
        self::assertSame('/api/v1/kling/text_to_video/task_123', $transport->requests[1]->getUri()->getPath());
        self::assertSame('/api/v1/kling/text_to_video/task_123', $transport->requests[2]->getUri()->getPath());
    }

    public function testTextToVideoRunRaisesTaskFailure(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"failed","error":"render failed"}'),
        ]);
        $resource = $this->textToVideo($transport);

        $this->expectException(TaskFailedException::class);
        $this->expectExceptionMessage('render failed');

        $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest',
        ], new RequestOptions(pollIntervalSeconds: 0.0));
    }

    public function testTextToVideoRunRaisesTaskTimeout(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"processing"}'),
        ]);
        $resource = $this->textToVideo($transport, [0.0, 2.0]);

        $this->expectException(TaskTimeoutException::class);
        $this->expectExceptionMessage('Task polling timed out');

        $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest',
        ], new RequestOptions(maxWaitSeconds: 1.0, pollIntervalSeconds: 0.0));
    }

    public function testTextToVideoUsesGeneratedContractValidation(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('duration_seconds must be one of the allowed values');

        $client->textToVideo->create([
            'model' => 'kling-v2.5-turbo-text-to-video-pro',
            'prompt' => 'A serene forest',
            'duration_seconds' => 6,
        ]);
    }

    public function testTextToVideoAcceptsV3TurboModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3"}'),
        ]);
        $client = $this->client($transport);

        $task = $client->textToVideo->create([
            'model' => Types::MODEL_V3_TURBO_TEXT_TO_VIDEO,
            'prompt' => 'A silver train crossing a moonlit bridge',
            'duration_seconds' => 7,
            'aspect_ratio' => '16:9',
            'output_resolution' => '1080p',
        ]);

        self::assertSame('task_v3', $task->id);
        self::assertSame(
            '{"model":"kling-v3-turbo-text-to-video","prompt":"A silver train crossing a moonlit bridge","duration_seconds":7,"aspect_ratio":"16:9","output_resolution":"1080p"}',
            (string) $transport->requests[0]->getBody(),
        );
    }

    public function testTextToVideoRejectsUnsupportedV3TurboFields(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('enable_sound is not allowed when model is kling-v3-turbo-text-to-video');

        $client->textToVideo->create([
            'model' => Types::MODEL_V3_TURBO_TEXT_TO_VIDEO,
            'prompt' => 'A quiet city street after rain',
            'enable_sound' => false,
        ]);
    }

    public function testTextToVideoAcceptsV26ModeAndSoundFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v26"}'),
        ]);
        $client = $this->client($transport);

        $client->textToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'A paper boat crossing a rain puddle',
            'mode' => 'pro',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9',
        ]);

        self::assertSame([
            'model' => 'kling-v2.6',
            'prompt' => 'A paper boat crossing a rain puddle',
            'mode' => 'pro',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9',
        ], json_decode((string) $transport->requests[0]->getBody(), true));
    }

    public function testTextToVideoAcceptsV3OmniResolutionAndSoundFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_omni"}'),
        ]);
        $client = $this->client($transport);

        $client->textToVideo->create([
            'model' => Types::MODEL_V3_OMNI,
            'prompt' => 'A paper boat crossing a rain puddle',
            'output_resolution' => '1080p',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9',
        ]);

        self::assertSame('kling-v3-omni', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testTextToVideoRejectsV26SoundOutsideProMode(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('enable_sound requires mode pro for kling-v2.6');

        $client->textToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'A paper boat crossing a rain puddle',
            'enable_sound' => true,
        ]);
    }

    public function testTextToVideoRejectsMissingPromptOutsideMultiShot(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('prompt is required');

        $client->textToVideo->create(['model' => 'kling-3.0']);
    }

    public function testTextToVideoRejectsInvalidMultiShotState(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('enable_sound must be true when multi_shots is true');

        $client->textToVideo->create([
            'model' => 'kling-3.0',
            'multi_shots' => true,
            'multi_prompt' => [['prompt' => 'shot one', 'duration_seconds' => 3]],
        ]);
    }

    public function testImageToVideoCreateAndLastFrameValidation(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
        ]);
        $client = $this->client($transport);

        $client->imageToVideo->create([
            'model' => 'kling-v2.5-turbo-image-to-video-pro',
            'prompt' => 'A bird takes flight',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/first-frame.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
        ]);

        self::assertSame('/api/v1/kling/image_to_video', $transport->requests[0]->getUri()->getPath());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('last_frame_image_url is only supported');

        $client->imageToVideo->create([
            'model' => 'kling-v2.1-standard',
            'prompt' => 'A bird takes flight',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/first-frame.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
        ]);
    }

    public function testImageToVideoAcceptsV3TurboModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_i2v"}'),
        ]);
        $client = $this->client($transport);

        $task = $client->imageToVideo->create([
            'model' => Types::MODEL_V3_TURBO_IMAGE_TO_VIDEO,
            'prompt' => 'Camera glides toward the lighthouse',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'duration_seconds' => 7,
            'output_resolution' => '720p',
        ]);

        self::assertSame('task_v3_i2v', $task->id);
        self::assertSame([
            'model' => 'kling-v3-turbo-image-to-video',
            'prompt' => 'Camera glides toward the lighthouse',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'duration_seconds' => 7,
            'output_resolution' => '720p',
        ], json_decode((string) $transport->requests[0]->getBody(), true));
    }

    public function testImageToVideoRejectsUnsupportedV3TurboFields(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('last_frame_image_url is not allowed when model is kling-v3-turbo-image-to-video');

        $client->imageToVideo->create([
            'model' => Types::MODEL_V3_TURBO_IMAGE_TO_VIDEO,
            'prompt' => 'Camera glides toward the lighthouse',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
        ]);
    }

    public function testImageToVideoAcceptsV26ConditionalFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v26_i2v"}'),
        ]);
        $client = $this->client($transport);

        $client->imageToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'Camera follows the cyclist through fog',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
            'mode' => 'pro',
            'duration_seconds' => 5,
            'enable_sound' => true,
            'aspect_ratio' => '16:9',
        ]);

        self::assertSame('kling-v2.6', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testImageToVideoAcceptsV3OmniConditionalFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_omni_i2v"}'),
        ]);
        $client = $this->client($transport);

        $client->imageToVideo->create([
            'model' => Types::MODEL_V3_OMNI,
            'prompt' => 'Camera follows the cyclist through fog',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image.jpg',
            'output_resolution' => '4k',
            'duration_seconds' => 5,
            'enable_sound' => false,
            'aspect_ratio' => '9:16',
        ]);

        self::assertSame('kling-v3-omni', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testImageToVideoRejectsV26SoundOutsideProMode(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('enable_sound requires mode pro for kling-v2.6');

        $client->imageToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'test',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'enable_sound' => true,
        ]);
    }

    /**
     * @dataProvider invalidV26FinalFrameProvider
     * @param array<string, mixed> $extra
     */
    public function testImageToVideoRejectsInvalidV26FinalFrameCombinations(array $extra, string $message): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);

        $client->imageToVideo->create(array_merge([
            'model' => Types::MODEL_V26,
            'prompt' => 'test',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
        ], $extra));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidV26FinalFrameProvider(): iterable
    {
        yield 'standard mode' => [[], 'last_frame_image_url requires mode pro for kling-v2.6'];
        yield 'ten seconds' => [
            ['mode' => 'pro', 'duration_seconds' => 10],
            'last_frame_image_url requires duration_seconds 5 for kling-v2.6',
        ];
    }

    public function testImageToVideoRejectsV3OmniFinalFrameOutsideFiveSeconds(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('last_frame_image_url requires duration_seconds 5 for kling-v3-omni');

        $client->imageToVideo->create([
            'model' => Types::MODEL_V3_OMNI,
            'prompt' => 'test',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image.jpg',
            'duration_seconds' => 7,
        ]);
    }

    public function testAiAvatarAndMotionControlCreate(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"avatar_task"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"motion_task"}'),
        ]);
        $client = $this->client($transport);

        self::assertSame('avatar_task', $client->aiAvatar->create([
            'model' => 'kling-ai-avatar-pro',
            'prompt' => 'A presenter speaking naturally',
            'source_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'source_audio_url' => 'https://cdn.runapi.ai/public/samples/voice.mp3',
        ])->id);

        self::assertSame('motion_task', $client->motionControl->create([
            'model' => 'kling-3.0',
            'source_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'output_resolution' => '1080p',
        ])->id);

        self::assertSame('/api/v1/kling/ai_avatar', $transport->requests[0]->getUri()->getPath());
        self::assertSame('/api/v1/kling/motion_control', $transport->requests[1]->getUri()->getPath());
    }

    private function client(?QueueHttpClient $transport = null): KlingClient
    {
        return new KlingClient(new ClientOptions(
            apiKey: 'test-key',
            httpClient: $transport ?? new QueueHttpClient([new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}')]),
            maxRetries: 0,
        ));
    }

    /**
     * @param list<float> $times
     */
    private function textToVideo(QueueHttpClient $transport, array $times = [0.0, 0.0, 0.0]): TextToVideo
    {
        $poller = new Poller(
            sleep: static fn (): null => null,
            now: static function () use (&$times): float {
                return array_shift($times) ?? 0.0;
            },
        );

        $http = new \RunApi\Core\Http\HttpClient(new ClientOptions(
            apiKey: 'test-key',
            httpClient: $transport,
            maxRetries: 0,
        ));

        return new TextToVideo($http, poller: $poller);
    }
}
