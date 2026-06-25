<?php

declare(strict_types=1);

namespace RunApi\Kling\Models;

use RunApi\Core\Models\BaseModel;
use RunApi\Core\Support\Payload;

/**
 * Generated video file metadata.
 */
readonly class Video extends BaseModel
{
    /**
     * Create a video value object.
     *
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $url,
        array $raw = [],
    ) {
        parent::__construct($raw === [] ? ['url' => $url] : $raw);
    }

    /**
     * Hydrate a video from a RunAPI response object.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            url: Payload::string($raw, 'url'),
            raw: $raw,
        );
    }
}
