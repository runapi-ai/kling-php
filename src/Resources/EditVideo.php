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

/** Edits a source video with a selected Kling V3 Omni model. */
readonly class EditVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/edit_video';
    private const ACTION = 'kling/edit-video';

    /** @param array<string, mixed> $params */
    public function create(array $params, ?RequestOptions $options = null): TaskCreateResponse
    {
        return parent::create($params, $options);
    }

    public function get(string $id, ?RequestOptions $options = null): TextToVideoResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof TextToVideoResponse) {
            throw new ValidationException('edit-video status returned an invalid response');
        }

        return $response;
    }

    /** @param array<string, mixed> $params */
    public function run(array $params, ?RequestOptions $options = null): CompletedTextToVideoResponse
    {
        $response = parent::run($params, $options);
        if (!$response instanceof CompletedTextToVideoResponse) {
            throw new ValidationException('edit-video polling returned an invalid response');
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

    /** @param array<string, mixed> $raw */
    protected function hydrate(array $raw): TextToVideoResponse
    {
        return TextToVideoResponse::fromArray($raw);
    }

    protected function hydrateCompleted(TaskResponse $response): CompletedTextToVideoResponse
    {
        if (!$response instanceof TextToVideoResponse) {
            throw new ValidationException('edit-video polling returned an invalid response');
        }

        return CompletedTextToVideoResponse::fromResponse($response);
    }
}
