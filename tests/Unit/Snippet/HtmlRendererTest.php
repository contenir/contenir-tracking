<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Snippet;

use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class HtmlRendererTest extends TestCase
{
    #[Test]
    public function escapesApostrophesAndBadBytesAsHtml5(): void
    {
        $snippet = Snippet::src('https://example.com/a.js', Placement::Head, Category::Necessary, [
            'data-name' => "it's \xC3",
        ]);

        static::assertSame(
            '<script src="https://example.com/a.js" data-name="it&apos;s ' . "\u{FFFD}" . '"></script>',
            (new HtmlRenderer())->render([$snippet]),
        );
    }

    #[Test]
    public function escapesAttributeValuesAndTheNonce(): void
    {
        $snippet = Snippet::src('https://example.com/a.js?x=1&y="2"', Placement::Head, Category::Necessary, [
            'data-name' => '<b>',
        ]);

        static::assertSame(
            '<script src="https://example.com/a.js?x=1&amp;y=&quot;2&quot;" nonce="a&quot;b" data-name="&lt;b&gt;"></script>',
            (new HtmlRenderer())->render([$snippet], 'a"b'),
        );
    }

    #[Test]
    public function gatesAScriptThatNeedsConsent(): void
    {
        $snippet = Snippet::src('https://example.com/a.js', Placement::Head, Category::Analytics, ['async' => true]);

        static::assertSame(
            '<script type="text/plain" data-category="analytics" src="https://example.com/a.js" nonce="n" async></script>',
            (new HtmlRenderer(gate: true))->render([$snippet], 'n'),
        );
    }

    #[Test]
    public function keepsAttributesItWritesItselfAndDropsUnsafeOrFalseOnes(): void
    {
        $snippet = Snippet::src('https://example.com/a.js', Placement::Head, Category::Analytics, [
            'type'          => 'module',
            'src'           => 'https://evil.example/',
            'nonce'         => 'guess',
            'data-category' => 'necessary',
            'onload'        => 'alert(1)',
            'Async'         => true,
            '9x'            => true,
            'bad name'      => true,
            'defer'         => false,
            'crossorigin'   => 'anonymous',
        ]);

        static::assertSame(
            '<script src="https://example.com/a.js" crossorigin="anonymous"></script>',
            (new HtmlRenderer())->render([$snippet]),
        );
    }

    #[Test]
    public function leavesANecessaryScriptRunningWhenGated(): void
    {
        $snippet = Snippet::inline('go();', Placement::Head, Category::Necessary);

        static::assertSame('<script>go();</script>', (new HtmlRenderer(gate: true))->render([$snippet]));
    }

    #[Test]
    public function leavesOutAFallbackThatNeedsConsentWhenGated(): void
    {
        $noscripts = [
            Noscript::pixel('https://example.com/px', Placement::BodyStart, Category::Marketing),
            Noscript::pixel('https://example.com/ok', Placement::BodyStart, Category::Necessary),
        ];

        static::assertSame(
            '<noscript><img height="1" width="1" style="display:none" alt="" src="https://example.com/ok"></noscript>',
            (new HtmlRenderer(gate: true))->render($noscripts),
        );
    }

    #[Test]
    public function leavesOutAnEmptyNonce(): void
    {
        $snippet = Snippet::inline('go();', Placement::Head, Category::Necessary);

        static::assertSame('<script>go();</script>', (new HtmlRenderer())->render([$snippet], ''));
    }

    #[Test]
    public function writesEachSnippetOnItsOwnLine(): void
    {
        $snippets = [
            Snippet::inline('one();', Placement::Head, Category::Necessary),
            Snippet::inline('two();', Placement::Head, Category::Analytics),
        ];

        static::assertSame(
            "<script nonce=\"n\">one();</script>\n<script nonce=\"n\">two();</script>",
            (new HtmlRenderer())->render($snippets, 'n'),
        );
    }

    #[Test]
    public function writesFallbacksForVisitorsWithoutScripts(): void
    {
        $noscripts = [
            Noscript::frame('https://example.com/ns?id=1&b=2', Placement::BodyStart, Category::Analytics),
            Noscript::pixel('https://example.com/px', Placement::BodyStart, Category::Marketing),
        ];

        static::assertSame(
            '<noscript><iframe src="https://example.com/ns?id=1&amp;b=2" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'
                . "\n"
                . '<noscript><img height="1" width="1" style="display:none" alt="" src="https://example.com/px"></noscript>',
            (new HtmlRenderer())->render($noscripts),
        );
    }

    #[Test]
    public function writesNothingForNoSnippets(): void
    {
        static::assertSame('', (new HtmlRenderer())->render([], 'n'));
    }
}
