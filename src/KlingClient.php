<?php

declare(strict_types=1);

namespace RunApi\Kling;

use RunApi\Core\BaseClient;
use RunApi\Core\ClientOptions;
use RunApi\Kling\Resources\AiAvatar;
use RunApi\Kling\Resources\ImageToVideo;
use RunApi\Kling\Resources\MotionControl;
use RunApi\Kling\Resources\TextToVideo;

/**
 * Provides Kling video generation, AI avatar lip-sync, and motion control.
 *
 * Exposes typed model resources plus the universal files and account resources.
 */
final class KlingClient extends BaseClient
{
    /**
     * Text to video operations.
     */
    public readonly TextToVideo $textToVideo;
    /**
     * Image to video operations.
     */
    public readonly ImageToVideo $imageToVideo;
    /**
     * Ai avatar operations.
     */
    public readonly AiAvatar $aiAvatar;
    /**
     * Motion control operations.
     */
    public readonly MotionControl $motionControl;

    /**
     * Create a Kling client with optional API key, base URL, and transport overrides.
     */
    public function __construct(ClientOptions $options = new ClientOptions())
    {
        parent::__construct($options);
        $this->textToVideo = new TextToVideo($this->http);
        $this->imageToVideo = new ImageToVideo($this->http);
        $this->aiAvatar = new AiAvatar($this->http);
        $this->motionControl = new MotionControl($this->http);
    }
}
