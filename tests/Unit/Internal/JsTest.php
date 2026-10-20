<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Internal;

use Contenir\Tracking\Internal\Js;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class JsTest extends TestCase
{
    #[Test]
    public function capturesTheRunningScriptsNonce(): void
    {
        static::assertSame(
            "var nonce = d.currentScript ? d.currentScript.nonce || d.currentScript.getAttribute('nonce') : '';",
            Js::nonce(),
        );
    }

    #[Test]
    public function escapesWhatCouldEndAScriptElement(): void
    {
        static::assertSame(
            '"\u003C/script\u003E \u0026 \u0027 \u0022 a/b"',
            Js::value("</script> & ' \" a/b"),
        );
    }

    #[Test]
    public function indentsEveryLineButTheFirst(): void
    {
        static::assertSame("a\n  b\n  c", Js::indent("a\nb\nc", spaces: 2));
    }

    #[Test]
    public function loadsAScriptWithTheCapturedNonce(): void
    {
        static::assertSame(
            <<<'JS'
                var script = d.createElement('script');
                script.async = true;
                script.src = "https://example.com/a.js";
                if (nonce) {
                    script.nonce = nonce;
                }
                d.head.appendChild(script);
                JS,
            Js::load('"https://example.com/a.js"'),
        );
    }

    #[Test]
    public function writesListsAndObjects(): void
    {
        static::assertSame('{"a":["x","y"],"b":false,"c":1}', Js::value(['a' => ['x', 'y'], 'b' => false, 'c' => 1]));
    }
}
