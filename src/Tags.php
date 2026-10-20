<?php

declare(strict_types=1);

namespace Contenir\Tracking;

use Contenir\Tracking\Consent\ConsentMode;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\Tag\GoogleTag;
use Contenir\Tracking\Tag\LinkedInInsight;
use Contenir\Tracking\Tag\MetaPixel;
use Contenir\Tracking\Tag\TagInterface;
use Contenir\Tracking\Tag\TagManager;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;

/**
 * A site's tags as snippets, in the order they go on the page: the Consent
 * Mode defaults and contenir:consent bridge first, then the Google tag, Tag
 * Manager, the Meta pixel and LinkedIn Insight. An environment that doesn't
 * track writes nothing.
 *
 * @api
 */
final readonly class Tags
{
    public function __construct(
        private TrackingConfig $config,
    ) {}

    /**
     * Every snippet, in order.
     *
     * @return list<Snippet|Noscript>
     */
    public function all(): array
    {
        if (! $this->config->writesTags()) {
            return [];
        }

        $tags   = $this->tags();
        $google = array_filter($tags, static fn(TagInterface $tag): bool => $tag->isGoogle());
        $mode   = new ConsentMode($this->config->consent);

        return array_merge(
            [[] === $google ? $mode->bridge() : $mode->googleBridge()],
            ...array_map(
                /** @return list<Snippet|Noscript> */
                fn(TagInterface $tag): array => $tag->snippets($this->config->consent),
                $tags,
            ),
        );
    }

    /**
     * The snippets for one place on the page, in order.
     *
     * @return list<Snippet|Noscript>
     */
    public function at(Placement $placement): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn(Snippet|Noscript $snippet): bool => $placement === $snippet->placement,
        ));
    }

    /**
     * @return list<TagInterface>
     */
    private function tags(): array
    {
        $google     = $this->config->tag(Provider::GoogleTag);
        $tagManager = $this->config->tag(Provider::TagManager);
        $meta       = $this->config->tag(Provider::MetaPixel);
        $linkedIn   = $this->config->tag(Provider::LinkedInInsight);

        return array_values(array_filter([
            null === $google ? null : new GoogleTag($google),
            null === $tagManager ? null : new TagManager($tagManager),
            null === $meta ? null : new MetaPixel($meta),
            null === $linkedIn ? null : new LinkedInInsight($linkedIn),
        ]));
    }
}
