<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Errors\ValidationException;
use RunApi\Core\Models\TaskCreateResponse;
use RunApi\Core\Models\TaskResponse;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\AsyncResource;
use RunApi\Kling\Models\CompletedImageToVideoResponse;
use RunApi\Kling\Models\ImageToVideoResponse;

/**
 * Animates a still image into video, guided by a text prompt and first-frame image.
 */
readonly class ImageToVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/image_to_video';

    /**
     * Submits an image-to-video task and returns immediately with a task id.
     *
     * @param array{
     *   model: string,
     *   prompt?: string,
     *   first_frame_image_url?: string,
     *   callback_url?: string,
     *   mode?: string,
     *   enable_sound?: bool,
     *   duration_seconds?: int,
     *   output_resolution?: string,
     *   negative_prompt?: string,
     *   cfg_scale?: float|int,
     *   aspect_ratio?: string,
     *   last_frame_image_url?: string,
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
     * Fetches the current status of an image-to-video task by id.
     */
    public function get(string $id, ?RequestOptions $options = null): ImageToVideoResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof ImageToVideoResponse) {
            throw new ValidationException('image-to-video status returned an invalid response');
        }

        return $response;
    }

    /**
     * Submits an image-to-video task and polls until it completes.
     *
     * @param array<string, mixed> $params
     */
    public function run(array $params, ?RequestOptions $options = null): CompletedImageToVideoResponse
    {
        $response = parent::run($params, $options);

        if (!$response instanceof CompletedImageToVideoResponse) {
            throw new ValidationException('image-to-video polling returned an invalid response');
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
    protected function hydrate(array $raw): ImageToVideoResponse
    {
        return ImageToVideoResponse::fromArray($raw);
    }

    protected function hydrateCompleted(TaskResponse $response): CompletedImageToVideoResponse
    {
        if (!$response instanceof ImageToVideoResponse) {
            throw new ValidationException('image-to-video polling returned an invalid response');
        }

        return CompletedImageToVideoResponse::fromResponse($response);
    }
}
