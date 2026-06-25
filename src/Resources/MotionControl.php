<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Errors\ValidationException;
use RunApi\Core\Models\TaskCreateResponse;
use RunApi\Core\Models\TaskResponse;
use RunApi\Core\RequestOptions;
use RunApi\Core\Resources\AsyncResource;
use RunApi\Kling\Models\CompletedMotionControlResponse;
use RunApi\Kling\Models\MotionControlResponse;
use RunApi\Kling\Types;

/**
 * Transfers motion from a reference video onto a subject image. The subject adopts the movement patterns from the reference while preserving its own appearance.
 */
readonly class MotionControl extends AsyncResource
{
    private const ENDPOINT = '/api/v1/kling/motion_control';
    private const ACTION = 'kling/motion-control';

    /**
     * Submits a motion control task and returns immediately with a task id.
     *
     * @param array{
     *   model: string,
     *   source_image_url?: string,
     *   reference_video_url?: string,
     *   prompt?: string,
     *   output_resolution?: string,
     *   character_orientation?: string,
     *   background_source?: string,
     *   callback_url?: string
     * } $params
     */
    public function create(array $params, ?RequestOptions $options = null): TaskCreateResponse
    {
        return parent::create($params, $options);
    }

    /**
     * Fetches the current status of a motion control task by id.
     */
    public function get(string $id, ?RequestOptions $options = null): MotionControlResponse
    {
        $response = parent::get($id, $options);
        if (!$response instanceof MotionControlResponse) {
            throw new ValidationException('motion-control status returned an invalid response');
        }

        return $response;
    }

    /**
     * Submits a motion control task and polls until it completes.
     *
     * @param array<string, mixed> $params
     */
    public function run(array $params, ?RequestOptions $options = null): CompletedMotionControlResponse
    {
        $response = parent::run($params, $options);

        if (!$response instanceof CompletedMotionControlResponse) {
            throw new ValidationException('motion-control polling returned an invalid response');
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
    protected function hydrate(array $raw): MotionControlResponse
    {
        return MotionControlResponse::fromArray($raw);
    }

    protected function hydrateCompleted(TaskResponse $response): CompletedMotionControlResponse
    {
        if (!$response instanceof MotionControlResponse) {
            throw new ValidationException('motion-control polling returned an invalid response');
        }

        return CompletedMotionControlResponse::fromResponse($response);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function validate(array $params, string $model): void
    {
        if ($model === '_') {
            throw new ValidationException('model is required');
        }

        $this->validateModel($model, Types::MOTION_CONTROL_MODELS);
        $this->requireField($params, 'source_image_url');
        $this->requireField($params, 'reference_video_url');
    }
}
