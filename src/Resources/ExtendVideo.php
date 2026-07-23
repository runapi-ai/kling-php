<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Models\TaskCreateResponse;
use RunApi\Core\Models\TaskResponse;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\AsyncResource;
use RunApi\Kling\Models\CompletedTextToVideoResponse;
use RunApi\Kling\Models\TextToVideoResponse;

/** Continues a completed Kling V2.5 Turbo video task. */
readonly class ExtendVideo extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/extend_video';
    private const ACTION = 'kling/extend-video';

    /** @param array<string, mixed> $params */
    public function create(array $params, ?RequestOptions $options = null): TaskCreateResponse
    {
        return parent::create($params, $options);
    }

    public function get(string $id, ?RequestOptions $options = null): TextToVideoResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof TextToVideoResponse) {
            throw new \RunApi\Core\Errors\ValidationException('extend-video status returned an invalid response');
        }

        return $response;
    }

    /** @param array<string, mixed> $params */
    public function run(array $params, ?RequestOptions $options = null): CompletedTextToVideoResponse
    {
        $response = parent::run($params, $options);
        if (!$response instanceof CompletedTextToVideoResponse) {
            throw new \RunApi\Core\Errors\ValidationException('extend-video polling returned an invalid response');
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
            throw new \RunApi\Core\Errors\ValidationException('extend-video polling returned an invalid response');
        }

        return CompletedTextToVideoResponse::fromResponse($response);
    }

    /** @param array<string, mixed> $params */
    protected function validate(array $params, string $model): void
    {
        $this->requireField($params, 'source_task_id');
        if (isset($params['mode']) && !in_array($params['mode'], ['std', 'pro'], true)) {
            throw new \RunApi\Core\Errors\ValidationException('mode must be std or pro');
        }
    }
}
