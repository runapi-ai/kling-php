<?php

declare(strict_types=1);

namespace RunApi\Kling\Models;

/**
 * Completed completed motion control response task response returned by run(); outputs are guaranteed present.
 */
readonly class CompletedMotionControlResponse extends MotionControlResponse
{
    /**
     * Hydrate a completed motion-control response from a RunAPI response object.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            id: self::id($raw),
            status: self::status($raw),
            error: self::error($raw),
            videos: self::videos($raw, required: true),
            raw: $raw,
        );
    }

    /**
     * Narrow a polled task response after completion has been confirmed.
     */
    public static function fromResponse(MotionControlResponse $response): self
    {
        return self::fromArray($response->toArray());
    }
}
