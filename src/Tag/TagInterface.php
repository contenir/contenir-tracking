<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Snippet;

/**
 * One provider's tag: the snippets it writes for a page, in order.
 *
 * @api
 */
interface TagInterface
{
    /**
     * Whether the tag is Google's, and so needs Consent Mode set up first.
     */
    public function isGoogle(): bool;

    /**
     * @return list<Snippet|Noscript>
     */
    public function snippets(ConsentSettings $consent): array;
}
