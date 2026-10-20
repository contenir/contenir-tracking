<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tag\GoogleTag;
use Contenir\Tracking\Tests\TestAsset\Factory\TagIdFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class GoogleTagTest extends TestCase
{
    #[Test]
    public function configuresTheTagWithoutGoogleSignalsWhenAdSignalsAreOff(): void
    {
        $snippets = (new GoogleTag(TagIdFactory::google()))->snippets(new ConsentSettings(adSignals: false));

        static::assertSame(
            "gtag('js', new Date());\n"
                . 'gtag(\'config\', "G-ABC1234567", {"allow_google_signals":false,"allow_ad_personalization_signals":false});',
            $snippets[1]->inline ?? null,
        );
    }

    #[Test]
    public function countsAnAdsIdAsMarketing(): void
    {
        $snippets = (new GoogleTag(TagIdFactory::google('AW-123456789')))->snippets(new ConsentSettings());

        static::assertSame(
            [Category::Marketing, Category::Marketing],
            [$snippets[0]->category, $snippets[1]->category],
        );
    }

    #[Test]
    public function isGoogles(): void
    {
        static::assertTrue((new GoogleTag(TagIdFactory::google()))->isGoogle());
    }

    #[Test]
    public function loadsGtagThenConfiguresItInTheHead(): void
    {
        $snippets = (new GoogleTag(TagIdFactory::google()))->snippets(new ConsentSettings());

        static::assertEquals(
            [
                Snippet::src(
                    'https://www.googletagmanager.com/gtag/js?id=G-ABC1234567',
                    Placement::Head,
                    Category::Analytics,
                    ['async' => true],
                )->readingConsentMode(),
                Snippet::inline(
                    "gtag('js', new Date());\ngtag('config', \"G-ABC1234567\");",
                    Placement::Head,
                    Category::Analytics,
                )->readingConsentMode(),
            ],
            $snippets,
        );
    }

    #[Test]
    public function refusesAnotherProvidersId(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('GTM-ABCD123 is not a Google tag ID'));

        new GoogleTag(TagIdFactory::tagManager());
    }
}
