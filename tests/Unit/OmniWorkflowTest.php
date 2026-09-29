<?php

declare(strict_types=1);

namespace RunApi\Kling\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RunApi\Core\ClientOptions;
use RunApi\Core\RequestOptions;
use RunApi\Core\Tests\Fixtures\QueueHttpClient;
use RunApi\Kling\KlingClient;
use RunApi\Kling\Models\CompletedTextToVideoResponse;
use RunApi\Kling\Types;

final class OmniWorkflowTest extends TestCase
{
    public function testTextToVideoAcceptsReferenceImageModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"reference-image","status":"processing"}')]);
        $client = $this->client($transport);

        self::assertSame('reference-image', $client->textToVideo->create([
            'model' => Types::MODEL_V3_OMNI_REFERENCE,
            'prompt' => 'Keep the subject from the reference image',
            'reference_image_urls' => ['https://cdn.runapi.ai/public/samples/image.jpg'],
            'aspect_ratio' => '16:9'])->id);
        self::assertSame('/api/v1/kling/text_to_video', $transport->requests[0]->getUri()->getPath());
    }

    public function testEditVideoCreatesGetsAndRunsForReferenceModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"edit-create","status":"processing"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"edit-get","status":"processing"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"edit-run","status":"processing"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"edit-run","status":"completed","videos":[{"url":"https://file.runapi.ai/edit.mp4"}],"usage":{"cost":0.05}}')]);
        $client = $this->client($transport);
        $params = [
            'model' => Types::MODEL_V3_OMNI_REFERENCE,
            'prompt' => 'Keep the subject from the reference image',
            'source_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'reference_image_urls' => ['https://cdn.runapi.ai/public/samples/image.jpg'],
            'aspect_ratio' => '16:9',
            'enable_sound' => false];

        self::assertSame('edit-create', $client->editVideo->create($params)->id);
        self::assertSame('processing', $client->editVideo->get('edit-get')->status);
        $result = $client->editVideo->run($params, new RequestOptions(pollIntervalSeconds: 0.0));

        self::assertInstanceOf(CompletedTextToVideoResponse::class, $result);
        self::assertSame('completed', $result->status);
        self::assertSame('/api/v1/kling/edit_video', $transport->requests[0]->getUri()->getPath());
        self::assertSame('/api/v1/kling/edit_video/edit-get', $transport->requests[1]->getUri()->getPath());
        self::assertSame(Types::MODEL_V3_OMNI_REFERENCE, json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    public function testEditVideoAcceptsEditModel(): void
    {
        $transport = new QueueHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"edit-create","status":"processing"}')]);
        $client = $this->client($transport);
        $params = [
            'model' => Types::MODEL_V3_OMNI_EDIT,
            'prompt' => 'Turn the source video into a watercolor scene',
            'source_video_url' => 'https://cdn.runapi.ai/public/samples/video.mp4',
            'aspect_ratio' => 'auto'];

        self::assertSame('edit-create', $client->editVideo->create($params)->id);
        self::assertSame('/api/v1/kling/edit_video', $transport->requests[0]->getUri()->getPath());
        self::assertSame(Types::MODEL_V3_OMNI_EDIT, json_decode((string) $transport->requests[0]->getBody(), true)['model']);
    }

    private function client(QueueHttpClient $transport): KlingClient
    {
        return new KlingClient(new ClientOptions(
            apiKey: 'test-key',
            httpClient: $transport,
            maxRetries: 0,
        ));
    }
}
