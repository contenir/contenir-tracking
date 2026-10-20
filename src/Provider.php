<?php

declare(strict_types=1);

namespace Contenir\Tracking;

use function ctype_alnum;
use function ctype_digit;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * A tag the package knows how to write, with the shape of its ID.
 *
 * @api
 */
enum Provider: string
{
    case GoogleTag       = 'google-tag';
    case LinkedInInsight = 'linkedin-insight';
    case MetaPixel       = 'meta-pixel';
    case TagManager      = 'tag-manager';

    private const int MIN_CODE = 4;

    private const int MAX_CODE = 16;

    private const int MIN_NUMBER = 3;

    private const int MAX_NUMBER = 20;

    private static function code(string $id, string $prefix): bool
    {
        if (! str_starts_with($id, $prefix)) {
            return false;
        }

        $code   = substr($id, strlen($prefix));
        $length = strlen($code);

        return $length >= self::MIN_CODE && $length <= self::MAX_CODE && ctype_alnum($code);
    }

    private static function googleTagProblem(string $id): ?string
    {
        if (self::code($id, 'G-') || self::code($id, 'GT-')) {
            return null;
        }

        if (self::code($id, 'AW-') && ctype_digit(substr($id, offset: 3))) {
            return null;
        }

        if (str_starts_with($id, 'UA-')) {
            return (
                'UA- IDs belong to Universal Analytics, which stopped collecting data in July 2023. '
                    . 'Enter the G- measurement ID from Google Analytics 4.'
            );
        }

        if (str_starts_with($id, 'GTM-')) {
            return 'That is a Tag Manager container ID. Enter it as the Tag Manager container instead.';
        }

        return 'Enter a Google tag ID such as G-XXXXXXXXXX, GT-XXXXXXX or AW-XXXXXXXXX.';
    }

    private static function number(string $id): bool
    {
        $length = strlen($id);

        return $length >= self::MIN_NUMBER && $length <= self::MAX_NUMBER && ctype_digit($id);
    }

    /**
     * The tag's name as an editor knows it.
     */
    public function label(): string
    {
        return match ($this) {
            self::GoogleTag => 'Google tag ID',
            self::LinkedInInsight => 'LinkedIn Insight partner ID',
            self::MetaPixel => 'Meta pixel ID',
            self::TagManager => 'Tag Manager container ID',
        };
    }

    /**
     * $value as it is stored: trimmed, and upper case for Google's IDs.
     */
    public function normalise(string $value): string
    {
        $trimmed = trim($value);

        return match ($this) {
            self::GoogleTag, self::TagManager => strtoupper($trimmed),
            self::LinkedInInsight, self::MetaPixel => $trimmed,
        };
    }

    /**
     * What is wrong with $value, or null when it is a valid ID once
     * normalised. A blank value is valid: it switches the tag off.
     */
    public function problem(string $value): ?string
    {
        $id = $this->normalise($value);
        if ('' === $id) {
            return null;
        }

        return match ($this) {
            self::GoogleTag => self::googleTagProblem($id),
            self::LinkedInInsight => self::number($id)
                ? null
                : 'Enter the LinkedIn Insight partner ID: the number from Campaign Manager, such as 1234567.',
            self::MetaPixel => self::number($id)
                ? null
                : 'Enter the Meta pixel ID: the number from Events Manager, such as 123456789012345.',
            self::TagManager => self::code($id, 'GTM-')
                ? null
                : 'Enter a Tag Manager container ID such as GTM-XXXXXXX.',
        };
    }

    /**
     * The settings key a site keeps the ID under.
     */
    public function settingKey(): string
    {
        return match ($this) {
            self::GoogleTag => SettingKeys::GOOGLE_TAG,
            self::LinkedInInsight => SettingKeys::LINKEDIN_INSIGHT,
            self::MetaPixel => SettingKeys::META_PIXEL,
            self::TagManager => SettingKeys::TAG_MANAGER,
        };
    }
}
