<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Errors\ValidationException;
use RunApi\Core\Models\TaskCreateResponse;
use RunApi\Core\Models\TaskResponse;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\AsyncResource;
use RunApi\Kling\Models\AiAvatarResponse;
use RunApi\Kling\Models\CompletedAiAvatarResponse;
use RunApi\Kling\Types;

/**
 * Lip-syncs a face image to an audio track, producing a talking-head video.
 */
readonly class AiAvatar extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/ai_avatar';
    private const ACTION = 'kling/avatar';

    /**
     * Submits an AI avatar task and returns immediately with a task id.
     *
     * @param array{
     *   model: string,
     *   source_image_url?: string,
     *   source_audio_url?: string,
     *   prompt?: string,
     *   callback_url?: string
     * } $params
     */
    public function create(array $params, ?RequestOptions $options = null): TaskCreateResponse
    {
        return parent::create($params, $options);
    }

    /**
     * Fetches the current status of an AI avatar task by id.
     */
    public function get(string $id, ?RequestOptions $options = null): AiAvatarResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof AiAvatarResponse) {
            throw new ValidationException('ai-avatar status returned an invalid response');
        }

        return $response;
    }

    /**
     * Submits an AI avatar task and polls until it completes.
     *
     * @param array<string, mixed> $params
     */
    public function run(array $params, ?RequestOptions $options = null): CompletedAiAvatarResponse
    {
        $response = parent::run($params, $options);

        if (!$response instanceof CompletedAiAvatarResponse) {
            throw new ValidationException('ai-avatar polling returned an invalid response');
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
    protected function hydrate(array $raw): AiAvatarResponse
    {
        return AiAvatarResponse::fromArray($raw);
    }

    protected function hydrateCompleted(TaskResponse $response): CompletedAiAvatarResponse
    {
        if (!$response instanceof AiAvatarResponse) {
            throw new ValidationException('ai-avatar polling returned an invalid response');
        }

        return CompletedAiAvatarResponse::fromResponse($response);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function validate(array $params, string $model): void
    {
        if ($model === '_') {
            throw new ValidationException('model is required');
        }

        $this->validateModel($model, Types::AI_AVATAR_MODELS);
        $this->requireField($params, 'source_image_url');
        $this->requireField($params, 'source_audio_url');
        $this->requireField($params, 'prompt');
    }
}
