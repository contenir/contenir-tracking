<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tag\TagManager;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TagManagerTest extends TestCase
{
    private const string LOADER = <<<'JS'
        (function (w, d, id) {
            var nonce = d.currentScript ? d.currentScript.nonce || d.currentScript.getAttribute('nonce') : '';
            w.dataLayer = w.dataLayer || [];
            w.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
            var script = d.createElement('script');
            script.async = true;
            script.src = "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(id);
            if (nonce) {
                script.nonce = nonce;
            }
            d.head.appendChild(script);
        })(window, document, "GTM-ABCD123");
        JS;

    #[Test]
    public function isGoogles(): void
    {
        static::assertTrue((new TagManager(TagIdFactory::tagManager()))->isGoogle());
    }

    #[Test]
    public function loadsTheContainerWithTheNonceAndOffersAFrameWhenNotWaiting(): void
    {
        static::assertEquals(
            [
                Snippet::inline(self::LOADER, Placement::Head, Category::Analytics)->readingConsentMode(),
                Noscript::frame(
                    'https://www.googletagmanager.com/ns.html?id=GTM-ABCD123',
                    Placement::BodyStart,
                    Category::Analytics,
                ),
            ],
            (new TagManager(TagIdFactory::tagManager()))->snippets(new ConsentSettings(waitForConsent: false)),
        );
    }

    #[Test]
    public function offersNoFrameWhileWaitingForConsent(): void
    {
        static::assertEquals(
            [Snippet::inline(self::LOADER, Placement::Head, Category::Analytics)->readingConsentMode()],
            (new TagManager(TagIdFactory::tagManager()))->snippets(new ConsentSettings()),
        );
    }

    #[Test]
    public function refusesAnotherProvidersId(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('G-ABC1234567 is not a Tag Manager container ID'));

        new TagManager(TagIdFactory::google());
    }
}
