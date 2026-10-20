<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Consent;

use Contenir\Tracking\Consent\Choice;
use Contenir\Tracking\Consent\ConsentMode;
use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Consent\Regions;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Placement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentModeTest extends TestCase
{
    private const array GRANTED = [
        'ad_storage'         => 'granted',
        'ad_user_data'       => 'granted',
        'ad_personalization' => 'granted',
        'analytics_storage'  => 'granted',
    ];

    private const array DENIED = [
        'ad_storage'         => 'denied',
        'ad_user_data'       => 'denied',
        'ad_personalization' => 'denied',
        'analytics_storage'  => 'denied',
    ];

    /**
     * @return array<string, array{ConsentSettings, Choice, array<string, string>}>
     */
    public static function updates(): array
    {
        $noAdSignals = new ConsentSettings(adSignals: false);

        return [
            'nothing allowed'               => [new ConsentSettings(), new Choice(), self::DENIED],
            'everything allowed'            => [
                new ConsentSettings(),
                new Choice(
                    analytics: true,
                    ads: true,
                ),
                self::GRANTED,
            ],
            'analytics only'                => [
                new ConsentSettings(),
                new Choice(analytics: true),
                [...self::DENIED, 'analytics_storage' => 'granted'],
            ],
            'ads only'                      => [
                new ConsentSettings(),
                new Choice(ads: true),
                [...self::GRANTED, 'analytics_storage' => 'denied'],
            ],
            'everything without ad signals' => [
                $noAdSignals,
                new Choice(
                    analytics: true,
                    ads: true,
                ),
                [...self::GRANTED, 'ad_user_data' => 'denied', 'ad_personalization' => 'denied'],
            ],
        ];
    }

    #[Test]
    public function bridgesAPageWithoutGoogleByRecordingEachChoice(): void
    {
        $snippet = (new ConsentMode(new ConsentSettings()))->bridge();

        static::assertSame(
            <<<'JS'
                window.addEventListener("contenir:consent", function (event) {
                    var detail = event.detail || {};
                    var analytics = detail.analytics === true;
                    var ads = detail.ads === true;
                    window["contenirConsent"] = {analytics: analytics, ads: ads};
                });
                JS,
            $snippet->inline,
        );
    }

    #[Test]
    public function deniesEverythingEverywhereWhileWaiting(): void
    {
        static::assertSame(
            [[...self::DENIED, 'wait_for_update' => 500]],
            (new ConsentMode(new ConsentSettings()))->defaults(),
        );
    }

    #[Test]
    public function deniesOnlyInItsRegionsWhileWaitingThere(): void
    {
        static::assertSame(
            [self::GRANTED, [...self::DENIED, 'wait_for_update' => 500, 'region' => ['ES', 'US-CA']]],
            (new ConsentMode(new ConsentSettings(regions: Regions::parse('ES, US-CA'))))->defaults(),
        );
    }

    #[Test]
    public function grantsEverythingWhenNotWaiting(): void
    {
        static::assertSame(
            [self::GRANTED],
            (new ConsentMode(new ConsentSettings(waitForConsent: false)))->defaults(),
        );
    }

    #[Test]
    public function keepsAdSignalsDeniedByDefaultWithoutThem(): void
    {
        static::assertSame(
            [[...self::GRANTED, 'ad_user_data' => 'denied', 'ad_personalization' => 'denied']],
            (new ConsentMode(new ConsentSettings(
                waitForConsent: false,
                adSignals: false,
            )))->defaults(),
        );
    }

    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('updates')]
    public function mapsAChoiceToGooglesConsentUpdate(ConsentSettings $settings, Choice $choice, array $expected): void
    {
        static::assertSame($expected, (new ConsentMode($settings))->update($choice));
    }

    #[Test]
    public function placesTheBridgeFirstInTheHeadForEveryVisitor(): void
    {
        $mode = new ConsentMode(new ConsentSettings());

        static::assertSame(
            [Placement::Head, Category::Necessary, Placement::Head, Category::Necessary],
            [
                $mode->bridge()->placement,
                $mode->bridge()->category,
                $mode->googleBridge()->placement,
                $mode->googleBridge()->category,
            ],
        );
    }

    #[Test]
    public function setsGoogleUpWhileWaitingWithPassthrough(): void
    {
        $snippet = (new ConsentMode(new ConsentSettings(
            regions: Regions::parse('ES'),
            urlPassthrough: true,
        )))->googleBridge();

        static::assertSame(
            <<<'JS'
                window.dataLayer = window.dataLayer || [];
                window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };
                gtag('consent', 'default', {"ad_storage":"granted","ad_user_data":"granted","ad_personalization":"granted","analytics_storage":"granted"});
                gtag('consent', 'default', {"ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied","analytics_storage":"denied","wait_for_update":500,"region":["ES"]});
                gtag('set', 'ads_data_redaction', true);
                gtag('set', 'url_passthrough', true);
                window.addEventListener("contenir:consent", function (event) {
                    var detail = event.detail || {};
                    var analytics = detail.analytics === true;
                    var ads = detail.ads === true;
                    window["contenirConsent"] = {analytics: analytics, ads: ads};
                    gtag('consent', 'update', {ad_storage: ads ? 'granted' : 'denied', ad_user_data: ads ? 'granted' : 'denied', ad_personalization: ads ? 'granted' : 'denied', analytics_storage: analytics ? 'granted' : 'denied'});
                });
                JS,
            $snippet->inline,
        );
    }

    #[Test]
    public function setsGoogleUpWithoutWaitingOrAdSignals(): void
    {
        $snippet = (new ConsentMode(new ConsentSettings(
            waitForConsent: false,
            adSignals: false,
        )))->googleBridge();

        static::assertSame(
            <<<'JS'
                window.dataLayer = window.dataLayer || [];
                window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };
                gtag('consent', 'default', {"ad_storage":"granted","ad_user_data":"denied","ad_personalization":"denied","analytics_storage":"granted"});
                window.addEventListener("contenir:consent", function (event) {
                    var detail = event.detail || {};
                    var analytics = detail.analytics === true;
                    var ads = detail.ads === true;
                    window["contenirConsent"] = {analytics: analytics, ads: ads};
                    gtag('consent', 'update', {ad_storage: ads ? 'granted' : 'denied', ad_user_data: "denied", ad_personalization: "denied", analytics_storage: analytics ? 'granted' : 'denied'});
                });
                JS,
            $snippet->inline,
        );
    }
}
