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
use RunApi\Kling\Types;

/**
 * Animates a still image into video, guided by a text prompt and first-frame image.
 */
readonly class ImageToVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/image_to_video';
    private const ACTION = 'kling/image-to-video';
    private const V3_TURBO_UNSUPPORTED_FIELDS = [
        'aspect_ratio',
        'negative_prompt',
        'cfg_scale',
        'last_frame_image_url',
    ];

    /**
     * Submits an image-to-video task and returns immediately with a task id.
     *
     * @param array{
     *   model: string,
     *   prompt?: string,
     *   first_frame_image_url?: string,
     *   callback_url?: string,
     *   duration_seconds?: int,
     *   output_resolution?: string,
     *   negative_prompt?: string,
     *   cfg_scale?: float|int,
     *   aspect_ratio?: string,
     *   last_frame_image_url?: string
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

    protected function action(): string
    {
        return self::ACTION;
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

    /**
     * @param array<string, mixed> $params
     */
    protected function validate(array $params, string $model): void
    {
        if ($model === '_') {
            throw new ValidationException('model is required');
        }

        $this->validateModel($model, Types::IMAGE_TO_VIDEO_MODELS);
        if ($model === Types::MODEL_V3_TURBO_IMAGE_TO_VIDEO) {
            $this->rejectUnsupportedV3TurboFields($params);
        }

        $this->requireField($params, 'prompt');
        $this->requireField($params, 'first_frame_image_url');

        if (array_key_exists('last_frame_image_url', $params) && !in_array($model, Types::LAST_FRAME_IMAGE_MODELS, true)) {
            throw new ValidationException('last_frame_image_url is only supported by kling-v2.5-turbo-image-to-video-pro and kling-v2.1-pro');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function rejectUnsupportedV3TurboFields(array $params): void
    {
        foreach (self::V3_TURBO_UNSUPPORTED_FIELDS as $field) {
            if ($this->fieldPresent($params, $field)) {
                throw new ValidationException($field . ' is not supported by ' . Types::MODEL_V3_TURBO_IMAGE_TO_VIDEO);
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function fieldPresent(array $params, string $field): bool
    {
        if (!array_key_exists($field, $params)) {
            return false;
        }

        $value = $params[$field];
        if ($value === false) {
            return true;
        }

        return $this->present($value);
    }

    private function present(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }
}
