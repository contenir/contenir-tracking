<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tag\LinkedInInsight;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_replace;

#[Group('unit')]
final class LinkedInInsightTest extends TestCase
{
    private const string INSIGHT = <<<'JS'
        (function (w, d, id) {
            var nonce = d.currentScript ? d.currentScript.nonce || d.currentScript.getAttribute('nonce') : '';
            var loaded = false;
            w._linkedin_partner_id = id;
            w._linkedin_data_partner_ids = w._linkedin_data_partner_ids || [];
            w._linkedin_data_partner_ids.push(id);
            function load() {
                if (loaded) {
                    return;
                }
                loaded = true;
                if (!w.lintrk) {
                    w.lintrk = function (a, b) {
                        w.lintrk.q.push([a, b]);
                    };
                    w.lintrk.q = [];
                }
                var script = d.createElement('script');
                script.async = true;
                script.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
                if (nonce) {
                    script.nonce = nonce;
                }
                d.head.appendChild(script);
            }
        {start}
        })(window, document, "1234567");
        JS;

    private const string WAIT = <<<'JS'
            var state = w["contenirConsent"];
            if (state && state.ads) {
                load();
            }
            w.addEventListener("contenir:consent", function (event) {
                if (event.detail && event.detail.ads === true) {
                    load();
                }
            });
        JS;

    #[Test]
    public function holdsBackUntilAdsAreAllowedWhileWaiting(): void
    {
        static::assertEquals(
            [Snippet::inline(str_replace('{start}', self::WAIT, self::INSIGHT), Placement::Head, Category::Marketing)],
            (new LinkedInInsight(TagIdFactory::linkedIn()))->snippets(new ConsentSettings()),
        );
    }

    #[Test]
    public function isNotGoogles(): void
    {
        static::assertFalse((new LinkedInInsight(TagIdFactory::linkedIn()))->isGoogle());
    }

    #[Test]
    public function loadsAtOnceWithAFallbackPixelWhenNotWaiting(): void
    {
        static::assertEquals(
            [
                Snippet::inline(
                    str_replace('{start}', replace: '    load();', subject: self::INSIGHT),
                    Placement::Head,
                    Category::Marketing,
                ),
                Noscript::pixel(
                    'https://px.ads.linkedin.com/collect/?fmt=gif&pid=1234567',
                    Placement::BodyStart,
                    Category::Marketing,
                ),
            ],
            (new LinkedInInsight(TagIdFactory::linkedIn()))->snippets(new ConsentSettings(waitForConsent: false)),
        );
    }

    #[Test]
    public function refusesAnotherProvidersId(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('123456789012345 is not a LinkedIn Insight partner ID'),
        );

        new LinkedInInsight(TagIdFactory::meta());
    }
}
