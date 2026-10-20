<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

use Contenir\Tracking\Exception\InvalidSnippetException;

use function stripos;

/**
 * A script for a page: written inline or loaded from a URL, where it goes,
 * and the consent it needs.
 *
 * @api
 */
final readonly class Snippet
{
    /**
     * @param array<string, string|bool> $attributes Extra attributes such as async; true writes the bare name
     */
    private function __construct(
        public Placement $placement,
        public Category $category,
        public ?string $src,
        public ?string $inline,
        public array $attributes,
    ) {}

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidSnippetException When $code is empty or would close its script element.
     */
    public static function inline(
        string $code,
        Placement $placement,
        Category $category,
        array $attributes = [],
    ): self {
        if ('' === $code) {
            throw InvalidSnippetException::because('An inline snippet needs code');
        }

        if (false !== stripos($code, needle: '</script')) {
            throw InvalidSnippetException::because('An inline snippet may not contain "</script"');
        }

        return new self($placement, $category, null, $code, $attributes);
    }

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidSnippetException When $src is empty.
     */
    public static function src(
        string $src,
        Placement $placement,
        Category $category,
        array $attributes = [],
    ): self {
        if ('' === $src) {
            throw InvalidSnippetException::because('A snippet loaded from a URL needs its src');
        }

        return new self($placement, $category, $src, null, $attributes);
    }
}
