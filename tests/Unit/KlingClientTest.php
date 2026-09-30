<?php

declare(strict_types=1);

namespace RunApi\Kling\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RunApi\Core\ClientOptions;
use RunApi\Core\Errors\TaskFailedException;
use RunApi\Core\Errors\TaskTimeoutException;
use RunApi\Core\Polling\Poller;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\Account;
use RunApi\Core\Resources\Files;
use RunApi\Core\Tests\Fixtures\QueueHttpClient;
use RunApi\Kling\KlingClient;
use RunApi\Kling\Models\CompletedTextToVideoResponse;
use RunApi\Kling\Resources\AiAvatar;
use RunApi\Kling\Resources\EditVideo;
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
        self::assertInstanceOf(EditVideo::class, $client->editVideo);
        self::assertInstanceOf(Files::class, $client->files);
        self::assertInstanceOf(Account::class, $client->account);
    }

    public function testTextToVideoCreatePostsCompactedBodyAndOptionsHeaders(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}')]);
        $client = $this->client($transport);

        $task = $client->textToVideo->create([
            'model' => 'kling-3.0',
            'prompt' => 'A cat walking through a garden',
            'aspect_ratio' => '16:9',
            'callback_url' => '',
            'kling_elements' => []], new RequestOptions(headers: ['X-Test' => 'yes']));

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
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_abc","status":"processing"}')]);
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
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"completed","videos":[{"url":"https://file.runapi.ai/video.mp4"}],"usage":{"cost":0.05}}')]);
        $resource = $this->textToVideo($transport);

        $response = $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest'], new RequestOptions(pollIntervalSeconds: 0.0));

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
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"failed","error":"render failed"}')]);
        $resource = $this->textToVideo($transport);

        $this->expectException(TaskFailedException::class);
        $this->expectExceptionMessage('render failed');

        $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest'], new RequestOptions(pollIntervalSeconds: 0.0));
    }

    public function testTextToVideoRunRaisesTaskTimeout(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_123","status":"processing"}')]);
        $resource = $this->textToVideo($transport, [0.0, 2.0]);

        $this->expectException(TaskTimeoutException::class);
        $this->expectExceptionMessage('Task polling timed out');

        $resource->run([
            'model' => 'kling-3.0',
            'prompt' => 'A serene forest'], new RequestOptions(maxWaitSeconds: 1.0, pollIntervalSeconds: 0.0));
    }

    public function testTextToVideoAcceptsV3TurboModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3"}')]);
        $client = $this->client($transport);

        $task = $client->textToVideo->create([
            'model' => Types::MODEL_V3_TURBO_TEXT_TO_VIDEO,
            'prompt' => 'A silver train crossing a moonlit bridge',
            'duration_seconds' => 7,
            'aspect_ratio' => '16:9',
            'output_resolution' => '1080p']);

        self::assertSame('task_v3', $task->id);
        self::assertSame(
            '{"model":"kling-v3-turbo-text-to-video","prompt":"A silver train crossing a moonlit bridge","duration_seconds":7,"aspect_ratio":"16:9","output_resolution":"1080p"}',
            (string) $transport->requests[0]->getBody(),
        );
    }

    public function testTextToVideoAcceptsV26ModeAndSoundFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v26"}')]);
        $client = $this->client($transport);

        $client->textToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'A paper boat crossing a rain puddle',
            'mode' => 'pro',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9']);

        self::assertSame([
            'model' => 'kling-v2.6',
            'prompt' => 'A paper boat crossing a rain puddle',
            'mode' => 'pro',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9'], json_decode((string) $transport->requests[0]->getBody(), true));
    }

    public function testTextToVideoAcceptsV3OmniResolutionAndSoundFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_omni"}')]);
        $client = $this->client($transport);

        $client->textToVideo->create([
            'model' => Types::MODEL_V3_OMNI,
            'prompt' => 'A paper boat crossing a rain puddle',
            'output_resolution' => '1080p',
            'duration_seconds' => 10,
            'enable_sound' => true,
            'aspect_ratio' => '16:9']);

        self::assertSame('kling-v3-omni', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testTextToVideoAcceptsO1ReferenceMedia(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_o1"}')]);
        $client = $this->client($transport);

        $client->textToVideo->create([
            'model' => Types::MODEL_O1,
            'prompt' => 'Keep <<<image_1>>> beside <<<video_1>>>',
            'reference_image_urls' => ['https://cdn.runapi.ai/public/samples/portrait.jpg'],
            'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'reference_video_type' => 'feature',
            'preserve_reference_video_audio' => true,
            'duration_seconds' => 5]);

        self::assertSame([
            'model' => 'kling-o1',
            'prompt' => 'Keep <<<image_1>>> beside <<<video_1>>>',
            'reference_image_urls' => ['https://cdn.runapi.ai/public/samples/portrait.jpg'],
            'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'reference_video_type' => 'feature',
            'preserve_reference_video_audio' => true,
            'duration_seconds' => 5], json_decode((string) $transport->requests[0]->getBody(), true));
    }

    public function testImageToVideoAcceptsV3TurboModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_i2v"}')]);
        $client = $this->client($transport);

        $task = $client->imageToVideo->create([
            'model' => Types::MODEL_V3_TURBO_IMAGE_TO_VIDEO,
            'prompt' => 'Camera glides toward the lighthouse',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'duration_seconds' => 7,
            'output_resolution' => '720p']);

        self::assertSame('task_v3_i2v', $task->id);
        self::assertSame([
            'model' => 'kling-v3-turbo-image-to-video',
            'prompt' => 'Camera glides toward the lighthouse',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'duration_seconds' => 7,
            'output_resolution' => '720p'], json_decode((string) $transport->requests[0]->getBody(), true));
    }

    public function testImageToVideoAcceptsV26ConditionalFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v26_i2v"}')]);
        $client = $this->client($transport);

        $client->imageToVideo->create([
            'model' => Types::MODEL_V26,
            'prompt' => 'Camera follows the cyclist through fog',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image-to-video.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/last-frame.jpg',
            'mode' => 'pro',
            'duration_seconds' => 5,
            'enable_sound' => true,
            'aspect_ratio' => '16:9']);

        self::assertSame('kling-v2.6', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testImageToVideoAcceptsV3OmniConditionalFields(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"task_v3_omni_i2v"}')]);
        $client = $this->client($transport);

        $client->imageToVideo->create([
            'model' => Types::MODEL_V3_OMNI,
            'prompt' => 'Camera follows the cyclist through fog',
            'first_frame_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'last_frame_image_url' => 'https://cdn.runapi.ai/public/samples/image.jpg',
            'output_resolution' => '4k',
            'duration_seconds' => 5,
            'enable_sound' => false,
            'aspect_ratio' => '9:16']);

        self::assertSame('kling-v3-omni', json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testAiAvatarAndMotionControlCreate(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"avatar_task"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"motion_task"}')]);
        $client = $this->client($transport);

        self::assertSame('avatar_task', $client->aiAvatar->create([
            'model' => 'kling-ai-avatar-pro',
            'prompt' => 'A presenter speaking naturally',
            'source_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'source_audio_url' => 'https://cdn.runapi.ai/public/samples/voice.mp3'])->id);

        self::assertSame('motion_task', $client->motionControl->create([
            'model' => 'kling-3.0',
            'source_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'output_resolution' => '1080p'])->id);

        self::assertSame('/api/v1/kling/ai_avatar', $transport->requests[0]->getUri()->getPath());
        self::assertSame('/api/v1/kling/motion_control', $transport->requests[1]->getUri()->getPath());
    }

    public function testMotionControlCreatesV26Request(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"motion_v26"}')]);
        $client = $this->client($transport);

        self::assertSame('motion_v26', $client->motionControl->create([
            'model' => Types::MODEL_V26,
            'source_image_url' => 'https://cdn.runapi.ai/public/samples/portrait.jpg',
            'reference_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'output_resolution' => '1080p',
            'character_orientation' => 'image'])->id);
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
