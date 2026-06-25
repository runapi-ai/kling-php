<?php

declare(strict_types=1);

namespace RunApi\Kling\Models;

use RunApi\Core\Models\TaskResponse;
use RunApi\Core\Support\Payload;

/**
 * Async task task response with lifecycle status and output files.
 */
readonly class KlingTaskResponse extends TaskResponse
{
    /**
     * Create a Kling task response value object.
     *
     * @param list<Video> $videos
     * @param array<string, mixed> $raw
     */
    public function __construct(
        ?string $id,
        string $status,
        ?string $error = null,
        public array $videos = [],
        array $raw = [],
    ) {
        parent::__construct(
            id: $id,
            status: $status,
            error: $error,
            raw: $raw === [] ? [
                'id' => $id,
                'status' => $status,
                'error' => $error,
                'videos' => array_map(static fn (Video $video): array => $video->toArray(), $videos),
            ] : $raw,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected static function id(array $raw): string
    {
        return Payload::string($raw, 'id');
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected static function status(array $raw): string
    {
        return Payload::string($raw, 'status');
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return list<Video>
     */
    protected static function videos(array $raw, bool $required = false): array
    {
        return Payload::listOf($raw, 'videos', Video::fromArray(...), $required);
    }
}
