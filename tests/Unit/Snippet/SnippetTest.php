<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Snippet;

use Contenir\Tracking\Exception\InvalidSnippetException;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\NoscriptKind;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SnippetTest extends TestCase
{
    #[Test]
    public function holdsAFrameFallback(): void
    {
        $noscript = Noscript::frame('https://example.com/ns', Placement::BodyStart, Category::Analytics);

        static::assertSame(
            [Placement::BodyStart, Category::Analytics, NoscriptKind::Frame, 'https://example.com/ns'],
            [$noscript->placement, $noscript->category, $noscript->kind, $noscript->src],
        );
    }

    #[Test]
    public function holdsAnInlineScript(): void
    {
        $snippet = Snippet::inline('go();', Placement::Head, Category::Marketing, ['id' => 'go']);

        static::assertSame(
            [Placement::Head, Category::Marketing, null, 'go();', ['id' => 'go']],
            [$snippet->placement, $snippet->category, $snippet->src, $snippet->inline, $snippet->attributes],
        );
    }

    #[Test]
    public function holdsAPixelFallback(): void
    {
        $noscript = Noscript::pixel('https://example.com/px', Placement::BodyStart, Category::Marketing);

        static::assertSame(
            [NoscriptKind::Pixel, 'https://example.com/px'],
            [$noscript->kind, $noscript->src],
        );
    }

    #[Test]
    public function holdsAScriptLoadedFromAUrl(): void
    {
        $snippet = Snippet::src('https://example.com/a.js', Placement::BodyEnd, Category::Necessary);

        static::assertSame(
            [Placement::BodyEnd, Category::Necessary, 'https://example.com/a.js', null, []],
            [$snippet->placement, $snippet->category, $snippet->src, $snippet->inline, $snippet->attributes],
        );
    }

    #[Test]
    public function refusesAFallbackWithoutASrc(): void
    {
        $this->expectExceptionObject(InvalidSnippetException::because('A noscript fallback needs its src'));

        Noscript::pixel('', Placement::BodyStart, Category::Marketing);
    }

    #[Test]
    public function refusesAFrameWithoutASrc(): void
    {
        $this->expectExceptionObject(InvalidSnippetException::because('A noscript fallback needs its src'));

        Noscript::frame('', Placement::BodyStart, Category::Analytics);
    }

    #[Test]
    public function refusesAnEmptyInlineScript(): void
    {
        $this->expectExceptionObject(InvalidSnippetException::because('An inline snippet needs code'));

        Snippet::inline('', Placement::Head, Category::Necessary);
    }

    #[Test]
    public function refusesAScriptWithoutASrc(): void
    {
        $this->expectExceptionObject(InvalidSnippetException::because('A snippet loaded from a URL needs its src'));

        Snippet::src('', Placement::Head, Category::Necessary);
    }

    #[Test]
    public function refusesCodeThatWouldCloseItsScriptElement(): void
    {
        $this->expectExceptionObject(
            InvalidSnippetException::because('An inline snippet may not contain "</script"'),
        );

        Snippet::inline('var a = "</SCRIPT><script>alert(1)";', Placement::Head, Category::Necessary);
    }
}
