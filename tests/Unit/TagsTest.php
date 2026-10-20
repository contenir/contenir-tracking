<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit;

use Contenir\Tracking\Consent\ConsentMode;
use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\SettingKeys;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Tag\GoogleTag;
use Contenir\Tracking\Tag\LinkedInInsight;
use Contenir\Tracking\Tag\MetaPixel;
use Contenir\Tracking\Tag\TagManager;
use Contenir\Tracking\Tags;
use Contenir\Tracking\Tests\TestAsset\Factory\SettingsFactory;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use Contenir\Tracking\TrackingConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TagsTest extends TestCase
{
    #[Test]
    public function bridgesConsentWithGoogleForTagManagerAlone(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([
            SettingKeys::TAG_MANAGER       => SettingsFactory::TAG_MANAGER,
            SettingKeys::TRACK_ENVIRONMENT => '1',
        ]));

        static::assertEquals(
            (new ConsentMode($config->consent))->googleBridge(),
            (new Tags($config))->all()[0] ?? null,
        );
    }

    #[Test]
    public function bridgesConsentWithoutGoogleWhenThereIsNoGoogleTag(): void
    {
        $config = TrackingConfig::fromSettings(SettingsFactory::make([
            SettingKeys::META_PIXEL        => SettingsFactory::META_PIXEL,
            SettingKeys::TRACK_ENVIRONMENT => '1',
        ]));

        static::assertEquals(
            (new ConsentMode($config->consent))->bridge(),
            (new Tags($config))->all()[0] ?? null,
        );
    }

    #[Test]
    public function listsTheConsentBridgeThenEachTagInOrder(): void
    {
        $consent = new ConsentSettings(waitForConsent: false);
        $config  = new TrackingConfig(
            [
                'linkedin-insight' => TagIdFactory::linkedIn(),
                'meta-pixel'       => TagIdFactory::meta(),
                'tag-manager'      => TagIdFactory::tagManager(),
                'google-tag'       => TagIdFactory::google(),
            ],
            $consent,
            track: true,
        );

        static::assertEquals(
            [
                (new ConsentMode($consent))->googleBridge(),
                ...(new GoogleTag(TagIdFactory::google()))->snippets($consent),
                ...(new TagManager(TagIdFactory::tagManager()))->snippets($consent),
                ...(new MetaPixel(TagIdFactory::meta()))->snippets($consent),
                ...(new LinkedInInsight(TagIdFactory::linkedIn()))->snippets($consent),
            ],
            (new Tags($config))->all(),
        );
    }

    #[Test]
    public function picksTheSnippetsForOnePlace(): void
    {
        $tags = new Tags(TrackingConfig::fromSettings(SettingsFactory::everyTag([
            SettingKeys::WAIT_FOR_CONSENT => false,
        ])));

        static::assertEquals(
            [
                Noscript::frame(
                    'https://www.googletagmanager.com/ns.html?id=GTM-ABCD123',
                    Placement::BodyStart,
                    Category::Analytics,
                ),
                Noscript::pixel(
                    'https://www.facebook.com/tr?ev=PageView&noscript=1&id=123456789012345',
                    Placement::BodyStart,
                    Category::Marketing,
                ),
                Noscript::pixel(
                    'https://px.ads.linkedin.com/collect/?fmt=gif&pid=1234567',
                    Placement::BodyStart,
                    Category::Marketing,
                ),
            ],
            $tags->at(Placement::BodyStart),
        );
    }

    #[Test]
    public function writesNothingWhereTheEnvironmentDoesNotTrack(): void
    {
        $tags = new Tags(TrackingConfig::fromSettings(SettingsFactory::everyTag([
            SettingKeys::TRACK_ENVIRONMENT => '0',
        ])));

        static::assertSame([], $tags->all());
    }
}
