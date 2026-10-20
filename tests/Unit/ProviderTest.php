<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit;

use Contenir\Tracking\Provider;
use Contenir\Tracking\SettingKeys;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ProviderTest extends TestCase
{
    /**
     * @return array<string, array{Provider, string}>
     */
    public static function invalidIds(): array
    {
        return [
            'Google: no prefix'              => [Provider::GoogleTag, 'ABC12345'],
            'Google: code too short'         => [Provider::GoogleTag, 'G-ABC'],
            'Google: code too long'          => [Provider::GoogleTag, 'G-ABCDEFGHIJKLMNOPQ'],
            'Google: punctuation'            => [Provider::GoogleTag, 'G-ABC_1234'],
            'Google: letters in an Ads ID'   => [Provider::GoogleTag, 'AW-12345A'],
            'Google: short Ads ID'           => [Provider::GoogleTag, 'AW-123'],
            'Google: Ads ID led by a letter' => [Provider::GoogleTag, 'AW-A23456789'],
            'Google: prefix alone'           => [Provider::GoogleTag, 'GT-'],
            'Tag Manager: no prefix'         => [Provider::TagManager, 'ABCD123'],
            'Tag Manager: code too short'    => [Provider::TagManager, 'GTM-ABC'],
            'Tag Manager: code too long'     => [Provider::TagManager, 'GTM-ABCDEFGHIJKLMNOPQ'],
            'Tag Manager: punctuation'       => [Provider::TagManager, 'GTM-AB.C123'],
            'Meta: letters'                  => [Provider::MetaPixel, '12345678901234A'],
            'Meta: too short'                => [Provider::MetaPixel, '12'],
            'Meta: too long'                 => [Provider::MetaPixel, '123456789012345678901'],
            'LinkedIn: letters'              => [Provider::LinkedInInsight, 'p1234567'],
            'LinkedIn: negative'             => [Provider::LinkedInInsight, '-1234567'],
        ];
    }

    /**
     * @return array<string, array{Provider, string}>
     */
    public static function labels(): array
    {
        return [
            'Google'      => [Provider::GoogleTag, 'Google tag ID'],
            'Tag Manager' => [Provider::TagManager, 'Tag Manager container ID'],
            'Meta'        => [Provider::MetaPixel, 'Meta pixel ID'],
            'LinkedIn'    => [Provider::LinkedInInsight, 'LinkedIn Insight partner ID'],
        ];
    }

    /**
     * @return array<string, array{Provider, string, string}>
     */
    public static function normalised(): array
    {
        return [
            'Google: trimmed and upper case'      => [Provider::GoogleTag, ' g-abc12345 ', 'G-ABC12345'],
            'Tag Manager: trimmed and upper case' => [Provider::TagManager, "gtm-abcd123\n", 'GTM-ABCD123'],
            'Meta: trimmed'                       => [Provider::MetaPixel, ' 123456789012345 ', '123456789012345'],
            'LinkedIn: trimmed'                   => [Provider::LinkedInInsight, "\t1234567", '1234567'],
        ];
    }

    /**
     * @return array<string, array{Provider, string}>
     */
    public static function settingKeys(): array
    {
        return [
            'Google'      => [Provider::GoogleTag, SettingKeys::GOOGLE_TAG],
            'Tag Manager' => [Provider::TagManager, SettingKeys::TAG_MANAGER],
            'Meta'        => [Provider::MetaPixel, SettingKeys::META_PIXEL],
            'LinkedIn'    => [Provider::LinkedInInsight, SettingKeys::LINKEDIN_INSIGHT],
        ];
    }

    /**
     * @return array<string, array{Provider, string}>
     */
    public static function validIds(): array
    {
        return [
            'Google: measurement ID' => [Provider::GoogleTag, 'G-ABC1234567'],
            'Google: shortest code'  => [Provider::GoogleTag, 'G-AB12'],
            'Google: longest code'   => [Provider::GoogleTag, 'G-ABCDEFGHIJKLMNOP'],
            'Google: tag ID'         => [Provider::GoogleTag, 'GT-K8M2T5Z'],
            'Google: Ads ID'         => [Provider::GoogleTag, 'AW-123456789'],
            'Google: lower case'     => [Provider::GoogleTag, 'g-abc1234567'],
            'Google: blank'          => [Provider::GoogleTag, '   '],
            'Tag Manager: container' => [Provider::TagManager, 'GTM-ABCD123'],
            'Tag Manager: shortest'  => [Provider::TagManager, 'GTM-AB12'],
            'Tag Manager: longest'   => [Provider::TagManager, 'GTM-ABCDEFGHIJKLMNOP'],
            'Meta: pixel'            => [Provider::MetaPixel, '123456789012345'],
            'Meta: shortest'         => [Provider::MetaPixel, '123'],
            'Meta: longest'          => [Provider::MetaPixel, '12345678901234567890'],
            'LinkedIn: partner'      => [Provider::LinkedInInsight, '1234567'],
            'LinkedIn: blank'        => [Provider::LinkedInInsight, ''],
        ];
    }

    #[Test]
    #[DataProvider('validIds')]
    public function acceptsAValidOrBlankId(Provider $provider, string $value): void
    {
        static::assertNull($provider->problem($value));
    }

    #[Test]
    public function asksForAGa4IdInsteadOfAUniversalAnalyticsOne(): void
    {
        static::assertSame(
            'UA- IDs belong to Universal Analytics, which stopped collecting data in July 2023. '
                . 'Enter the G- measurement ID from Google Analytics 4.',
            Provider::GoogleTag->problem('UA-142376215-1'),
        );
    }

    #[Test]
    public function describesAGoogleTagId(): void
    {
        static::assertSame(
            'Enter a Google tag ID such as G-XXXXXXXXXX, GT-XXXXXXX or AW-XXXXXXXXX.',
            Provider::GoogleTag->problem('X-1234'),
        );
    }

    #[Test]
    public function describesALinkedInPartnerId(): void
    {
        static::assertSame(
            'Enter the LinkedIn Insight partner ID: the number from Campaign Manager, such as 1234567.',
            Provider::LinkedInInsight->problem('abc'),
        );
    }

    #[Test]
    public function describesAMetaPixelId(): void
    {
        static::assertSame(
            'Enter the Meta pixel ID: the number from Events Manager, such as 123456789012345.',
            Provider::MetaPixel->problem('abc'),
        );
    }

    #[Test]
    public function describesATagManagerId(): void
    {
        static::assertSame(
            'Enter a Tag Manager container ID such as GTM-XXXXXXX.',
            Provider::TagManager->problem('G-ABC12345'),
        );
    }

    #[Test]
    #[DataProvider('settingKeys')]
    public function keepsEachIdUnderTodaysSettingKey(Provider $provider, string $key): void
    {
        static::assertSame($key, $provider->settingKey());
    }

    #[Test]
    #[DataProvider('labels')]
    public function namesEachTagAsEditorsKnowIt(Provider $provider, string $label): void
    {
        static::assertSame($label, $provider->label());
    }

    #[Test]
    #[DataProvider('normalised')]
    public function normalisesAnIdForStorage(Provider $provider, string $value, string $expected): void
    {
        static::assertSame($expected, $provider->normalise($value));
    }

    #[Test]
    public function pointsATagManagerIdToItsOwnField(): void
    {
        static::assertSame(
            'That is a Tag Manager container ID. Enter it as the Tag Manager container instead.',
            Provider::GoogleTag->problem('GTM-ABCD123'),
        );
    }

    #[Test]
    #[DataProvider('invalidIds')]
    public function refusesAnIdOfTheWrongShape(Provider $provider, string $value): void
    {
        static::assertNotNull($provider->problem($value));
    }
}
