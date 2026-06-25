<?php

declare(strict_types=1);

namespace RunApi\Kling\Models;

/**
 * Completed completed ai avatar response task response returned by run(); outputs are guaranteed present.
 */
readonly class CompletedAiAvatarResponse extends AiAvatarResponse
{
    /**
     * Hydrate a completed AI avatar response from a RunAPI response object.
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
    public static function fromResponse(AiAvatarResponse $response): self
    {
        return self::fromArray($response->toArray());
    }
}
