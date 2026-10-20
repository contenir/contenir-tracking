<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Consent\Regions;
use Contenir\Tracking\Provider;
use Contenir\Tracking\SettingKeys;
use Contenir\Tracking\Tests\TestAsset\Factory\SettingsFactory;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use Contenir\Tracking\TrackingConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TrackingConfigTest extends TestCase
{
    #[Test]
    public function doesNotTrackAnEnvironmentThatHasNotSaidSo(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([SettingKeys::GOOGLE_TAG => 'G-ABC12345']));

        static::assertSame([false, false], [$config->track, $config->writesTags()]);
    }

    #[Test]
    public function doesNotTrackOrHaveTagsByDefault(): void
    {
        $config = new TrackingConfig();

        static::assertSame([[], false, []], [$config->tags, $config->track, $config->problems]);
    }

    #[Test]
    public function keepsTheProblemWithABadRegionListAndWaitsEverywhere(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([SettingKeys::CONSENT_REGIONS => 'ES, Spain']));

        static::assertSame(
            [
                [
                    SettingKeys::CONSENT_REGIONS => '"SPAIN" is not a region code. Use a country code such as ES or a subdivision such as US-CA.',
                ],
                [],
            ],
            [$config->problems, $config->consent->regions->codes],
        );
    }

    #[Test]
    public function keepsTheProblemWithEachBadIdAndLeavesItOut(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([
            SettingKeys::GOOGLE_TAG  => 'UA-142376215-1',
            SettingKeys::META_PIXEL  => 'pixel',
            SettingKeys::TAG_MANAGER => '',
        ]));

        static::assertSame(
            [
                [],
                [
                    SettingKeys::GOOGLE_TAG => Provider::GoogleTag->problem('UA-142376215-1'),
                    SettingKeys::META_PIXEL => Provider::MetaPixel->problem('pixel'),
                ],
            ],
            [$config->tags, $config->problems],
        );
    }

    #[Test]
    public function readsEveryTagUnderTodaysKeys(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::everyTag());

        static::assertEquals(
            [
                Provider::GoogleTag->value       => TagIdFactory::google(),
                Provider::TagManager->value      => TagIdFactory::tagManager(),
                Provider::MetaPixel->value       => TagIdFactory::meta(),
                Provider::LinkedInInsight->value => TagIdFactory::linkedIn(),
            ],
            $config->tags,
        );
    }

    #[Test]
    public function readsTheConsentSettings(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([
            SettingKeys::WAIT_FOR_CONSENT => '0',
            SettingKeys::CONSENT_REGIONS  => 'es, us-ca',
            SettingKeys::AD_SIGNALS       => false,
            SettingKeys::URL_PASSTHROUGH  => '1',
        ]));

        static::assertSame(
            [false, ['ES', 'US-CA'], false, true],
            [
                $config->consent->waitForConsent,
                $config->consent->regions->codes,
                $config->consent->adSignals,
                $config->consent->urlPassthrough,
            ],
        );
    }

    #[Test]
    public function returnsATagByProvider(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([SettingKeys::LINKEDIN_INSIGHT => '1234567']));

        static::assertSame(
            ['1234567', null],
            [$config->tag(Provider::LinkedInInsight)?->value, $config->tag(Provider::MetaPixel)],
        );
    }

    #[Test]
    public function tracksAnEnvironmentThatSaysSo(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::everyTag());

        static::assertSame([true, true], [$config->track, $config->writesTags()]);
    }

    #[Test]
    public function waitsForConsentForABannerAndKeepsEverythingElse(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::everyTag([
            SettingKeys::WAIT_FOR_CONSENT => '0',
            SettingKeys::CONSENT_REGIONS  => 'ES, US-CA',
            SettingKeys::AD_SIGNALS       => '0',
            SettingKeys::URL_PASSTHROUGH  => '1',
            SettingKeys::GOOGLE_TAG       => 'UA-1234-1',
        ]));
        $waiting = $config->waitingForConsent();

        static::assertEquals(
            [
                new ConsentSettings(
                    waitForConsent: true,
                    regions: Regions::parse('ES, US-CA'),
                    adSignals: false,
                    urlPassthrough: true,
                ),
                $config->tags,
                true,
                $config->problems,
            ],
            [$waiting->consent, $waiting->tags, $waiting->track, $waiting->problems],
        );
    }

    #[Test]
    public function waitsForConsentWithAdSignalsAndNoPassthroughUnlessTold(): void
    {
        $config = TrackingConfig::fromSettings([]);

        static::assertSame(
            [true, true, false],
            [$config->consent->waitForConsent, $config->consent->adSignals, $config->consent->urlPassthrough],
        );
    }

    #[Test]
    public function writesNoTagsWhenTrackingWithoutAnyIds(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([SettingKeys::TRACK_ENVIRONMENT => true]));

        static::assertFalse($config->writesTags());
    }
}
