<?php

declare(strict_types=1);

namespace RunApi\Kling\Models;

/**
 * Async video task response with lifecycle status and output files.
 */
readonly class TextToVideoResponse extends KlingTaskResponse
{
    /**
     * Hydrate a text-to-video response from a RunAPI response object.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            id: self::id($raw),
            status: self::status($raw),
            error: self::error($raw),
            videos: self::videos($raw),
            raw: $raw,
        );
    }
}
