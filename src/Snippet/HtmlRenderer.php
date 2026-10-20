<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

use function array_filter;
use function array_keys;
use function array_map;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;

use const ARRAY_FILTER_USE_BOTH;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Writes snippets as HTML, one element a line. Every script carries the CSP
 * nonce when one is given. When the site gates scripts behind consent, any
 * snippet that isn't necessary is written inert (type="text/plain" with its
 * data-category) for the consent banner to switch on, and its noscript
 * fallback is left out, since a visitor without scripts can't consent.
 *
 * @api
 */
final readonly class HtmlRenderer
{
    /**
     * Attributes the renderer writes itself, which a snippet can't override.
     * Event handler attributes (on…) are dropped too: they are inline code
     * the nonce doesn't cover.
     */
    private const array RESERVED = ['data-category', 'nonce', 'src', 'type'];

    /**
     * @param bool $gate Whether the site gates scripts behind consent
     */
    public function __construct(
        private bool $gate = false,
    ) {}

    /**
     * @param list<Snippet|Noscript> $snippets
     * @param ?string                $nonce    The page's CSP nonce
     */
    public function render(array $snippets, ?string $nonce = null): string
    {
        $lines = array_map(
            fn(Snippet|Noscript $snippet): string => $snippet instanceof Snippet
                ? $this->script($snippet, $nonce)
                : $this->noscript($snippet),
            $snippets,
        );

        return implode("\n", array_filter($lines, static fn(string $line): bool => '' !== $line));
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    private function attributes(array $attributes): string
    {
        $allowed = array_filter(
            $attributes,
            static fn(string|bool $value, string $name): bool => (
                false !== $value
                && ! in_array($name, self::RESERVED, strict: true)
                && ! str_starts_with($name, 'on')
                && 1 === preg_match('/^[a-z][a-z0-9-]*$/', $name)
            ),
            ARRAY_FILTER_USE_BOTH,
        );

        return implode('', array_map(
            fn(string $name, string|bool $value): string => is_string($value)
                ? sprintf(' %s="%s"', $name, $this->escape($value))
                : " {$name}",
            array_keys($allowed),
            $allowed,
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }

    private function noscript(Noscript $noscript): string
    {
        if ($this->gate && Category::Necessary !== $noscript->category) {
            return '';
        }

        $src = $this->escape($noscript->src);

        return match ($noscript->kind) {
            NoscriptKind::Frame => sprintf(
                '<noscript><iframe src="%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>',
                $src,
            ),
            NoscriptKind::Pixel => sprintf(
                '<noscript><img height="1" width="1" style="display:none" alt="" src="%s"></noscript>',
                $src,
            ),
        };
    }

    private function script(Snippet $snippet, ?string $nonce): string
    {
        $tag = '<script';
        if ($this->gate && Category::Necessary !== $snippet->category) {
            $tag .= sprintf(' type="text/plain" data-category="%s"', $snippet->category->value);
        }

        if (null !== $snippet->src) {
            $tag .= sprintf(' src="%s"', $this->escape($snippet->src));
        }

        if (null !== $nonce && '' !== $nonce) {
            $tag .= sprintf(' nonce="%s"', $this->escape($nonce));
        }

        return $tag . $this->attributes($snippet->attributes) . '>' . ($snippet->inline ?? '') . '</script>';
    }
}
