<?php

declare(strict_types=1);

namespace RunApi\Kling;

/**
 * Constants for model slugs supported by the Kling PHP SDK.
 */
final class Types
{
    public const MODEL_KLING_30 = GeneratedModels::TEXT_TO_VIDEO_KLING_3_0;
    public const MODEL_V26 = GeneratedModels::TEXT_TO_VIDEO_KLING_V2_6;
    public const MODEL_V3_OMNI = GeneratedModels::TEXT_TO_VIDEO_KLING_V3_OMNI;
    public const MODEL_V3_TURBO_TEXT_TO_VIDEO = GeneratedModels::TEXT_TO_VIDEO_KLING_V3_TURBO_TEXT_TO_VIDEO;
    public const MODEL_V25_TURBO_TEXT_TO_VIDEO_PRO = GeneratedModels::TEXT_TO_VIDEO_KLING_V2_5_TURBO_TEXT_TO_VIDEO_PRO;
    public const MODEL_V21_MASTER_TEXT_TO_VIDEO = GeneratedModels::TEXT_TO_VIDEO_KLING_V2_1_MASTER_TEXT_TO_VIDEO;
    public const MODEL_V3_TURBO_IMAGE_TO_VIDEO = GeneratedModels::IMAGE_TO_VIDEO_KLING_V3_TURBO_IMAGE_TO_VIDEO;
    public const MODEL_V25_TURBO_IMAGE_TO_VIDEO_PRO = GeneratedModels::IMAGE_TO_VIDEO_KLING_V2_5_TURBO_IMAGE_TO_VIDEO_PRO;
    public const MODEL_V21_PRO = GeneratedModels::IMAGE_TO_VIDEO_KLING_V2_1_PRO;
    public const MODEL_V21_STANDARD = GeneratedModels::IMAGE_TO_VIDEO_KLING_V2_1_STANDARD;
    public const MODEL_V21_MASTER_IMAGE_TO_VIDEO = GeneratedModels::IMAGE_TO_VIDEO_KLING_V2_1_MASTER_IMAGE_TO_VIDEO;
    public const MODEL_AI_AVATAR_PRO = GeneratedModels::AVATAR_KLING_AI_AVATAR_PRO;
    public const MODEL_AI_AVATAR_STANDARD = GeneratedModels::AVATAR_KLING_AI_AVATAR_STANDARD;
    public const MODEL_AI_AVATAR_V1_PRO = GeneratedModels::AVATAR_KLING_AI_AVATAR_V1_PRO;
    public const MODEL_V1_AVATAR_STANDARD = GeneratedModels::AVATAR_KLING_V1_AVATAR_STANDARD;

    /** @var list<string> */
    public const TEXT_TO_VIDEO_MODELS = [
        self::MODEL_KLING_30,
        self::MODEL_V26,
        self::MODEL_V3_OMNI,
        self::MODEL_V3_TURBO_TEXT_TO_VIDEO,
        self::MODEL_V25_TURBO_TEXT_TO_VIDEO_PRO,
        self::MODEL_V21_MASTER_TEXT_TO_VIDEO,
    ];

    /** @var list<string> */
    public const IMAGE_TO_VIDEO_MODELS = [
        self::MODEL_V3_TURBO_IMAGE_TO_VIDEO,
        self::MODEL_V26,
        self::MODEL_V3_OMNI,
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
