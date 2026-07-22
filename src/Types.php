<?php

declare(strict_types=1);

namespace RunApi\Kling;

/**
 * Constants for model slugs supported by the Kling PHP SDK.
 */
final class Types
{
    public const MODEL_KLING_30 = 'kling-3.0';
    public const MODEL_V26 = 'kling-v2.6';
    public const MODEL_V3_TURBO_TEXT_TO_VIDEO = 'kling-v3-turbo-text-to-video';
    public const MODEL_V25_TURBO_TEXT_TO_VIDEO_PRO = 'kling-v2.5-turbo-text-to-video-pro';
    public const MODEL_V21_MASTER_TEXT_TO_VIDEO = 'kling-v2.1-master-text-to-video';
    public const MODEL_V3_TURBO_IMAGE_TO_VIDEO = 'kling-v3-turbo-image-to-video';
    public const MODEL_V25_TURBO_IMAGE_TO_VIDEO_PRO = 'kling-v2.5-turbo-image-to-video-pro';
    public const MODEL_V21_PRO = 'kling-v2.1-pro';
    public const MODEL_V21_STANDARD = 'kling-v2.1-standard';
    public const MODEL_V21_MASTER_IMAGE_TO_VIDEO = 'kling-v2.1-master-image-to-video';
    public const MODEL_AI_AVATAR_PRO = 'kling-ai-avatar-pro';
    public const MODEL_AI_AVATAR_STANDARD = 'kling-ai-avatar-standard';
    public const MODEL_AI_AVATAR_V1_PRO = 'kling-ai-avatar-v1-pro';
    public const MODEL_V1_AVATAR_STANDARD = 'kling-v1-avatar-standard';

    /** @var list<string> */
    public const TEXT_TO_VIDEO_MODELS = [
        self::MODEL_KLING_30,
        self::MODEL_V26,
        self::MODEL_V3_TURBO_TEXT_TO_VIDEO,
        self::MODEL_V25_TURBO_TEXT_TO_VIDEO_PRO,
        self::MODEL_V21_MASTER_TEXT_TO_VIDEO,
    ];

    /** @var list<string> */
    public const IMAGE_TO_VIDEO_MODELS = [
        self::MODEL_V3_TURBO_IMAGE_TO_VIDEO,
        self::MODEL_V26,
        self::MODEL_V25_TURBO_IMAGE_TO_VIDEO_PRO,
        self::MODEL_V21_PRO,
        self::MODEL_V21_STANDARD,
        self::MODEL_V21_MASTER_IMAGE_TO_VIDEO,
    ];

    /** @var list<string> */
    public const AI_AVATAR_MODELS = [
        self::MODEL_AI_AVATAR_PRO,
        self::MODEL_AI_AVATAR_STANDARD,
        self::MODEL_AI_AVATAR_V1_PRO,
        self::MODEL_V1_AVATAR_STANDARD,
    ];

    /** @var list<string> */
    public const MOTION_CONTROL_MODELS = [self::MODEL_KLING_30];

    /** @var list<string> */
    public const LAST_FRAME_IMAGE_MODELS = [
        self::MODEL_V25_TURBO_IMAGE_TO_VIDEO_PRO,
        self::MODEL_V21_PRO,
    ];

    public const MULTI_PROMPT_MAX_LENGTH = 500;

    private function __construct()
    {
    }
}
