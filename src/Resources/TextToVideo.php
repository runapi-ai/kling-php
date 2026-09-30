<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Errors\ValidationException;
use RunApi\Core\Models\TaskCreateResponse;
use RunApi\Core\Models\TaskResponse;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\AsyncResource;
use RunApi\Kling\Models\CompletedTextToVideoResponse;
use RunApi\Kling\Models\TextToVideoResponse;

/**
 * Generates video from a text prompt. Supports multi-shot mode, first/last frame images, sound generation, and Kling elements on kling-3.0; negative prompts and cfg_scale on V2.x models.
 */
readonly class TextToVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/text_to_video';

    /**
     * Submits a text-to-video task and returns immediately with a task id.
     *
     * @param array{
     *   model: string,
     *   prompt?: string,
     *   callback_url?: string,
     *   enable_sound?: bool,
     *   duration_seconds?: int,
     *   aspect_ratio?: string,
     *   output_resolution?: string,
     *   negative_prompt?: string,
     *   cfg_scale?: float|int,
     *   multi_shots?: bool,
     *   multi_prompt?: list<array{prompt?: string, duration_seconds?: int}>,
     *   first_frame_image_url?: string,
     *   last_frame_image_url?: string,
     *   kling_elements?: list<array<string, mixed>>,
     *   reference_image_urls?: list<string>,
     *   reference_video_url?: string,
     *   reference_video_type?: string,
     *   preserve_reference_video_audio?: bool
     * } $params
     */
    public function create(array $params, ?RequestOptions $options = null): TaskCreateResponse
    {
        return parent::create($params, $options);
    }

    /**
     * Fetches the current status of a text-to-video task by id.
     */
    public function get(string $id, ?RequestOptions $options = null): TextToVideoResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof TextToVideoResponse) {
            throw new ValidationException('text-to-video status returned an invalid response');
        }

        return $response;
    }

    /**
     * Submits a text-to-video task and polls until it completes.
     *
     * @param array<string, mixed> $params
     */
    public function run(array $params, ?RequestOptions $options = null): CompletedTextToVideoResponse
    {
        $response = parent::run($params, $options);

        if (!$response instanceof CompletedTextToVideoResponse) {
            throw new ValidationException('text-to-video polling returned an invalid response');
        }

        return $response;
    }

    protected function endpoint(): string
    {
        return self::ENDPOINT;
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected function hydrate(array $raw): TextToVideoResponse
    {
        return TextToVideoResponse::fromArray($raw);
    }

    protected function hydrateCompleted(TaskResponse $response): CompletedTextToVideoResponse
    {
        if (!$response instanceof TextToVideoResponse) {
            throw new ValidationException('text-to-video polling returned an invalid response');
        }

        return CompletedTextToVideoResponse::fromResponse($response);
    }
}
