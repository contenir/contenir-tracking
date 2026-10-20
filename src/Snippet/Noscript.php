<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

use Contenir\Tracking\Exception\InvalidSnippetException;

/**
 * What a provider asks for when a visitor has scripting switched off: a
 * hidden frame (Tag Manager) or a one-pixel image (Meta, LinkedIn).
 *
 * @api
 */
final readonly class Noscript
{
    private function __construct(
        public Placement $placement,
        public Category $category,
        public NoscriptKind $kind,
        public string $src,
    ) {}

    /**
     * @throws InvalidSnippetException When $src is empty.
     */
    public static function frame(string $src, Placement $placement, Category $category): self
    {
        return new self($placement, $category, NoscriptKind::Frame, self::checked($src));
    }

    /**
     * @throws InvalidSnippetException When $src is empty.
     */
    public static function pixel(string $src, Placement $placement, Category $category): self
    {
        return new self($placement, $category, NoscriptKind::Pixel, self::checked($src));
    }

    /**
     * @throws InvalidSnippetException When $src is empty.
     */
    private static function checked(string $src): string
    {
        if ('' === $src) {
            throw InvalidSnippetException::because('A noscript fallback needs its src');
        }

        return $src;
    }
}
