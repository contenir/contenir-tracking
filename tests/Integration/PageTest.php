<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Integration;

use Contenir\Tracking\SettingKeys;
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Tags;
use Contenir\Tracking\Tests\TestAsset\Factory\SettingsFactory;
use Contenir\Tracking\TrackingConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function preg_match_all;
use function substr_count;

/**
 * A site's settings, read and written as a page's head and body start.
 */
#[Group('integration')]
final class PageTest extends TestCase
{
    #[Test]
    public function gatesEveryTagButTheConsentBridgeForABanner(): void
    {
        $tags = new Tags(TrackingConfig::fromSettings(SettingsFactory::everyTag()));
        $html = (new HtmlRenderer(gate: true))->render($tags->at(Placement::Head), 'n0nce');

        static::assertSame(
            [
                '<script nonce="n0nce">',
                '<script type="text/plain" data-category="analytics" src="https://www.googletagmanager.com/gtag/js?id=G-ABC1234567" nonce="n0nce" async>',
                '<script type="text/plain" data-category="analytics" nonce="n0nce">',
                '<script type="text/plain" data-category="analytics" nonce="n0nce">',
                '<script type="text/plain" data-category="marketing" nonce="n0nce">',
                '<script type="text/plain" data-category="marketing" nonce="n0nce">',
            ],
            $this->openingTags($html),
        );
    }

    #[Test]
    public function keepsAnExistingSitesTagsRunningWhenItDoesNotWait(): void
    {
        $settings = SettingsFactory::make([
            SettingKeys::GOOGLE_TAG        => 'G-ABC1234567',
            SettingKeys::META_PIXEL        => '123456789012345',
            SettingKeys::WAIT_FOR_CONSENT  => '0',
            SettingKeys::TRACK_ENVIRONMENT => '1',
        ]);
        $tags = new Tags(TrackingConfig::fromSettings($settings));
        $head = (new HtmlRenderer())->render($tags->at(Placement::Head));

        static::assertSame(
            [
                'granted by default' => 1,
                'denied anywhere'    => 0,
                'pixel revoked'      => 0,
                'fallback pixel'     => '<noscript><img height="1" width="1" style="display:none" alt="" '
                    . 'src="https://www.facebook.com/tr?ev=PageView&amp;noscript=1&amp;id=123456789012345"></noscript>',
            ],
            [
                'granted by default' => substr_count($head, needle: '{"ad_storage":"granted"'),
                'denied anywhere'    => substr_count($head, needle: '"denied"'),
                'pixel revoked'      => substr_count($head, needle: "'revoke');\n    w.fbq('init'"),
                'fallback pixel'     => (new HtmlRenderer())->render($tags->at(Placement::BodyStart)),
            ],
        );
    }

    #[Test]
    public function writesEveryTagWithTheNonceInOrderWhileWaiting(): void
    {
        $tags = new Tags(TrackingConfig::fromSettings(SettingsFactory::everyTag()));
        $html = (new HtmlRenderer())->render($tags->at(Placement::Head), 'n0nce');

        static::assertSame(
            [
                [
                    '<script nonce="n0nce">',
                    '<script src="https://www.googletagmanager.com/gtag/js?id=G-ABC1234567" nonce="n0nce" async>',
                    '<script nonce="n0nce">',
                    '<script nonce="n0nce">',
                    '<script nonce="n0nce">',
                    '<script nonce="n0nce">',
                ],
                '',
            ],
            [$this->openingTags($html), (new HtmlRenderer())->render($tags->at(Placement::BodyStart), 'n0nce')],
        );
    }

    /**
     * @return list<string>
     */
    private function openingTags(string $html): array
    {
        preg_match_all('/<script[^>]*>/', $html, $matches);

        return $matches[0];
    }
}
