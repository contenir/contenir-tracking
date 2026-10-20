<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\TestAsset\Factory;

use function array_replace_recursive;
use function array_reverse;
use function explode;

/**
 * Builds a site's settings as nested arrays, the way settings files hold
 * them, from dotted keys.
 */
final class SettingsFactory
{
    public const string GOOGLE_TAG = 'G-ABC1234567';

    public const string TAG_MANAGER = 'GTM-ABCD123';

    public const string META_PIXEL = '123456789012345';

    public const string LINKEDIN = '1234567';

    /**
     * Every tag set, tracking on, and the values given.
     *
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function everyTag(array $values = []): array
    {
        return self::make([
            'google.analytics.key'                  => self::GOOGLE_TAG,
            'google.tag_manager.id'                 => self::TAG_MANAGER,
            'facebook.analytics.key'                => self::META_PIXEL,
            'linkedin.insight.partner_id'           => self::LINKEDIN,
            'extensions.tracking.track_environment' => '1',
            ...$values,
        ]);
    }

    /**
     * @param array<string, mixed> $values By dotted key
     *
     * @return array<array-key, mixed>
     */
    public static function make(array $values): array
    {
        $settings = [];
        foreach ($values as $key => $value) {
            foreach (array_reverse(explode('.', $key)) as $segment) {
                $value = [$segment => $value];
            }
            $settings = array_replace_recursive($settings, $value);
        }

        return $settings;
    }
}
