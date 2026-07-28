<?php

declare(strict_types=1);

namespace RunApi\Kling\Resources;

use RunApi\Core\Errors\ValidationException;
use RunApi\Kling\Types;

final class O1ReferenceValidation
{
    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    /** @var list<string> */
    private const VIDEO_EXTENSIONS = ['mp4', 'mov'];
    /** @var list<string> */
    private const BLOCKED_IP_CIDRS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
        '::/128',
        '::1/128',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '100::/64',
        '2001::/32',
        '2001:2::/48',
        '2001:db8::/32',
        '2002::/16',
        '3fff::/20',
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    /**
     * @param array<string, mixed> $params
     */
    public static function validate(array $params, string $model): void
    {
        if ($model !== Types::MODEL_O1) {
            return;
        }
        if (($params['enable_sound'] ?? false) === true) {
            throw new ValidationException('enable_sound is not supported by kling-o1');
        }

        self::validateFrameUrls($params);
        if (array_key_exists('prompt', $params) && !is_string($params['prompt'])) {
            throw new ValidationException('prompt must be a string');
        }
        $prompt = is_string($params['prompt'] ?? null) ? $params['prompt'] : '';
        $referenceImages = is_array($params['reference_image_urls'] ?? null)
            ? array_values($params['reference_image_urls'])
            : [];
        if (array_key_exists('reference_video_url', $params) && !is_string($params['reference_video_url'])) {
            throw new ValidationException('reference_video_url must be a string');
        }
        $referenceVideoUrl = $params['reference_video_url'] ?? null;

        if (self::present($params['last_frame_image_url'] ?? null)
            && ($referenceImages !== [] || self::present($referenceVideoUrl))) {
            throw new ValidationException('last_frame_image_url cannot be combined with reference_image_urls or reference_video_url');
        }
        if ($referenceVideoUrl !== null && count($referenceImages) > 4) {
            throw new ValidationException('reference_image_urls must contain at most 4 items when reference_video_url is present');
        }

        self::validateReferenceImages($prompt, $referenceImages);
        self::validateReferenceVideo($params, $prompt, $referenceVideoUrl);
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function validateFrameUrls(array $params): void
    {
        foreach (['first_frame_image_url', 'last_frame_image_url'] as $field) {
            $value = $params[$field] ?? null;
            if (is_string($value) && $value !== '' && !self::isPublicHttpUrl($value)) {
                throw new ValidationException($field . ' must be a public HTTP or HTTPS URL');
            }
            if (is_string($value) && $value !== '' && !in_array(self::urlExtension($value), self::IMAGE_EXTENSIONS, true)) {
                throw new ValidationException($field . ' must use a JPG, JPEG, or PNG URL');
            }
        }
    }

    /**
     * @param list<mixed> $urls
     */
    private static function validateReferenceImages(string $prompt, array $urls): void
    {
        foreach ($urls as $index => $url) {
            if (!is_string($url)) {
                throw new ValidationException('reference_image_urls[' . $index . '] must be a string');
            }
            if (!self::isPublicHttpUrl($url)) {
                throw new ValidationException('reference_image_urls[' . $index . '] must be a public HTTP or HTTPS URL');
            }
            if (!in_array(self::urlExtension($url), self::IMAGE_EXTENSIONS, true)) {
                throw new ValidationException('reference_image_urls[' . $index . '] must use a JPG, JPEG, or PNG URL');
            }

            $marker = '<<<image_' . ($index + 1) . '>>>';
            if (!str_contains($prompt, $marker)) {
                throw new ValidationException('prompt must reference reference_image_urls[' . $index . '] as ' . $marker);
            }
        }

        preg_match_all('/<<<image_(\d+)>>>/', $prompt, $matches);
        foreach ($matches[1] as $markerIndex) {
            $index = (int) $markerIndex;
            if ($index < 1 || $index > count($urls)) {
                throw new ValidationException('prompt references missing image_' . $index);
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function validateReferenceVideo(array $params, string $prompt, ?string $referenceVideoUrl): void
    {
        if ($referenceVideoUrl === null || $referenceVideoUrl === '') {
            if (array_key_exists('reference_video_type', $params)) {
                throw new ValidationException('reference_video_type requires reference_video_url');
            }
            if (array_key_exists('preserve_reference_video_audio', $params)) {
                throw new ValidationException('preserve_reference_video_audio requires reference_video_url');
            }
            if (preg_match('/<<<video_([^>]+)>>>/', $prompt, $missingMarker) === 1) {
                throw new ValidationException('prompt references missing video_' . $missingMarker[1]);
            }

            return;
        }

        if (!self::isPublicHttpUrl($referenceVideoUrl)) {
            throw new ValidationException('reference_video_url must be a public HTTP or HTTPS URL');
        }
        if (!in_array(self::urlExtension($referenceVideoUrl), self::VIDEO_EXTENSIONS, true)) {
            throw new ValidationException('reference_video_url must use an MP4 or MOV URL');
        }
        if (!str_contains($prompt, '<<<video_1>>>')) {
            throw new ValidationException('prompt must reference reference_video_url as <<<video_1>>>');
        }

        preg_match_all('/<<<video_([^>]+)>>>/', $prompt, $matches);
        foreach ($matches[1] as $markerIndex) {
            if ($markerIndex !== '1') {
                throw new ValidationException('prompt may only reference video_1');
            }
        }

        $referenceVideoType = $params['reference_video_type'] ?? 'base';
        if ($referenceVideoType === 'base' && (self::present($params['first_frame_image_url'] ?? null) || self::present($params['last_frame_image_url'] ?? null))) {
            throw new ValidationException('reference_video_type base cannot be combined with first_frame_image_url or last_frame_image_url');
        }
    }

    private static function present(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : $value !== null;
    }

    private static function urlExtension(string $value): string
    {
        $path = parse_url($value, PHP_URL_PATH);
        if (!is_string($path)) {
            return '';
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private static function isPublicHttpUrl(string $value): bool
    {
        $parts = parse_url($value);
        $host = is_array($parts) && isset($parts['host']) ? (string) $parts['host'] : '';

        return is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && $host !== ''
            && !isset($parts['user'], $parts['pass'])
            && !self::isBlockedHost($host);
    }

    private static function isBlockedHost(string $host): bool
    {
        $host = rtrim(strtolower(trim($host, '[]')), '.');
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        $address = inet_pton($host);
        if ($address === false) {
            return preg_match('/\A(?:0x[0-9a-f]+|\d+)(?:\.(?:0x[0-9a-f]+|\d+)){0,3}\z/i', $host) === 1;
        }
        if (strlen($address) === 16 && substr($address, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $address = substr($address, 12);
        }

        foreach (self::BLOCKED_IP_CIDRS as $cidr) {
            [$networkAddress, $prefixText] = explode('/', $cidr, 2);
            $network = inet_pton($networkAddress);
            if ($network === false || strlen($network) !== strlen($address)) {
                continue;
            }
            if (self::matchesCidr($address, $network, (int) $prefixText)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesCidr(string $address, string $network, int $prefix): bool
    {
        $fullBytes = intdiv($prefix, 8);
        if (substr($address, 0, $fullBytes) !== substr($network, 0, $fullBytes)) {
            return false;
        }
        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }
        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($address[$fullBytes]) & $mask) === (ord($network[$fullBytes]) & $mask);
    }

    private function __construct()
    {
    }
}
