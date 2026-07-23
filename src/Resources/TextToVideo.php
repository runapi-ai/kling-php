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
use RunApi\Kling\Types;

/**
 * Generates video from a text prompt. Supports multi-shot mode, first/last frame images, sound generation, and Kling elements on kling-3.0; negative prompts and cfg_scale on V2.x models.
 */
readonly class TextToVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/text_to_video';
    private const ACTION = 'kling/text-to-video';
    private const V3_TURBO_UNSUPPORTED_FIELDS = [
        'enable_sound',
        'negative_prompt',
        'cfg_scale',
        'multi_shots',
        'multi_prompt',
        'first_frame_image_url',
        'last_frame_image_url',
        'kling_elements',
    ];

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
     *   kling_elements?: list<array<string, mixed>>
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

    protected function action(): string
    {
        return self::ACTION;
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

    /**
     * @param array<string, mixed> $params
     */
    protected function validate(array $params, string $model): void
    {
        if ($model === '_') {
            throw new ValidationException('model is required');
        }

        $this->validateModel($model, Types::TEXT_TO_VIDEO_MODELS);
        if ($model === Types::MODEL_V3_TURBO_TEXT_TO_VIDEO) {
            $this->rejectUnsupportedV3TurboFields($params);
        }
        if ($model === Types::MODEL_V26 && ($params['enable_sound'] ?? false) === true && ($params['mode'] ?? 'std') !== 'pro') {
            throw new ValidationException('enable_sound requires mode pro for kling-v2.6');
        }

        $multiShots = ($params['multi_shots'] ?? false) === true;
        if ($multiShots) {
            if (($params['enable_sound'] ?? null) !== true) {
                throw new ValidationException('enable_sound must be true when multi_shots is true');
            }

            if (array_key_exists('last_frame_image_url', $params)) {
                throw new ValidationException('last_frame_image_url is not supported when multi_shots is true');
            }

            $this->validateMultiPrompt($params['multi_prompt'] ?? null);

            return;
        }

        $this->requireField($params, 'prompt');
    }

    private function validateMultiPrompt(mixed $multiPrompt): void
    {
        if (!is_array($multiPrompt) || $multiPrompt === []) {
            throw new ValidationException('multi_prompt must be a non-empty array when multi_shots is true');
        }

        foreach ($multiPrompt as $index => $shot) {
            if (!is_array($shot)) {
                throw new ValidationException('multi_prompt[' . $index . '] must be an object');
            }

            $prompt = $shot['prompt'] ?? null;
            if (!is_string($prompt) || $prompt === '') {
                throw new ValidationException('multi_prompt[' . $index . '].prompt is required');
            }

            if (strlen($prompt) > Types::MULTI_PROMPT_MAX_LENGTH) {
                throw new ValidationException('multi_prompt[' . $index . '].prompt exceeds ' . Types::MULTI_PROMPT_MAX_LENGTH . ' characters');
            }

            $duration = $shot['duration_seconds'] ?? null;
            if (!is_int($duration)) {
                throw new ValidationException('multi_prompt[' . $index . '].duration_seconds is required');
            }

            if ($duration < 1 || $duration > 12) {
                throw new ValidationException('multi_prompt[' . $index . '].duration_seconds must be between 1 and 12');
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function rejectUnsupportedV3TurboFields(array $params): void
    {
        foreach (self::V3_TURBO_UNSUPPORTED_FIELDS as $field) {
            if ($this->fieldPresent($params, $field)) {
                throw new ValidationException($field . ' is not supported by ' . Types::MODEL_V3_TURBO_TEXT_TO_VIDEO);
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
