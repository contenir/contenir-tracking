<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tag\MetaPixel;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_replace;

#[Group('unit')]
final class MetaPixelTest extends TestCase
{
    private const string PIXEL = <<<'JS'
        (function (w, d, id) {
            var nonce = d.currentScript ? d.currentScript.nonce || d.currentScript.getAttribute('nonce') : '';
            if (!w.fbq) {
                var fbq = w.fbq = function () {
                    fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
                };
                if (!w._fbq) {
                    w._fbq = fbq;
                }
                fbq.push = fbq;
                fbq.loaded = true;
                fbq.version = '2.0';
                fbq.queue = [];
                var script = d.createElement('script');
                script.async = true;
                script.src = "https://connect.facebook.net/en_US/fbevents.js";
                if (nonce) {
                    script.nonce = nonce;
                }
                d.head.appendChild(script);
            }
            var state = w["contenirConsent"];
            w.fbq('consent', state && state.ads ? 'grant' : 'revoke');
            w.fbq('init', id);
            w.fbq('track', 'PageView');
            w.addEventListener("contenir:consent", function (event) {
                w.fbq('consent', event.detail && event.detail.ads === true ? 'grant' : 'revoke');
            });
        })(window, document, "123456789012345");
        JS;

    private const string REVOKE = <<<'JS'

            var state = w["contenirConsent"];
            w.fbq('consent', state && state.ads ? 'grant' : 'revoke');
        JS;

    #[Test]
    public function isNotGoogles(): void
    {
        static::assertFalse((new MetaPixel(TagIdFactory::meta()))->isGoogle());
    }

    #[Test]
    public function refusesAnotherProvidersId(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('1234567 is not a Meta pixel ID'));

        new MetaPixel(TagIdFactory::linkedIn());
    }

    #[Test]
    public function runsGrantedWithAFallbackPixelWhenNotWaiting(): void
    {
        static::assertEquals(
            [
                Snippet::inline(
                    str_replace(self::REVOKE, replace: '', subject: self::PIXEL),
                    Placement::Head,
                    Category::Marketing,
                ),
                Noscript::pixel(
                    'https://www.facebook.com/tr?ev=PageView&noscript=1&id=123456789012345',
                    Placement::BodyStart,
                    Category::Marketing,
                ),
            ],
            (new MetaPixel(TagIdFactory::meta()))->snippets(new ConsentSettings(waitForConsent: false)),
        );
    }

    #[Test]
    public function startsRevokedUnlessAdsAreAllowedWhileWaiting(): void
    {
        static::assertEquals(
            [Snippet::inline(self::PIXEL, Placement::Head, Category::Marketing)],
            (new MetaPixel(TagIdFactory::meta()))->snippets(new ConsentSettings()),
        );
    }
}
