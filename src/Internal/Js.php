<?php

declare(strict_types=1);

namespace Contenir\Tracking\Internal;

use function json_encode;
use function str_repeat;
use function str_replace;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Writes PHP values as JavaScript literals that are safe inside a script
 * element: <, >, &, ' and " are escaped, so no value can end the element.
 *
 * @internal
 */
final class Js
{
    /**
     * Every line of $code after the first, indented by $spaces.
     *
     * @param non-negative-int $spaces
     */
    public static function indent(string $code, int $spaces): string
    {
        return str_replace("\n", "\n" . str_repeat(' ', $spaces), $code);
    }

    /**
     * Statements that add an async script loaded from $src (a JavaScript
     * expression) to the page, with the nonce the enclosing snippet captured
     * in a variable named nonce, so a strict CSP lets it run.
     */
    public static function load(string $src): string
    {
        return <<<JS
            var script = d.createElement('script');
            script.async = true;
            script.src = {$src};
            if (nonce) {
                script.nonce = nonce;
            }
            d.head.appendChild(script);
            JS;
    }

    /**
     * The line, first in a snippet's function, that captures the snippet's
     * own nonce while it runs, for the scripts it loads later.
     */
    public static function nonce(): string
    {
        return 'var nonce = d.currentScript ? d.currentScript.nonce || d.currentScript.getAttribute(\'nonce\') : \'\';';
    }

    /**
     * @param string|int|bool|list<string>|array<string, string|int|bool|list<string>> $value
     */
    public static function value(string|int|bool|array $value): string
    {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
