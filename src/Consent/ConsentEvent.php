<?php

declare(strict_types=1);

namespace Contenir\Tracking\Consent;

use Contenir\Tracking\Internal\Js;

use function sprintf;

/**
 * The browser contract between a consent banner and the tags. A banner fires
 * contenir:consent on window with the visitor's choice whenever it is made,
 * and on every page from the stored choice:
 *
 *     window.dispatchEvent(new CustomEvent('contenir:consent', {
 *         detail: { analytics: true, ads: false }
 *     }));
 *
 * Only true counts as consent. The tags map the choice to each provider's own
 * call: Google's Consent Mode update, the Meta pixel's grant or revoke, and
 * loading LinkedIn Insight. The last choice is kept in window.contenirConsent,
 * so a tag that starts later (one a banner switches on) still sees it.
 *
 * @api
 */
final class ConsentEvent
{
    /**
     * The event's name.
     */
    public const string NAME = 'contenir:consent';

    /**
     * The window property holding the last choice, as {analytics, ads}.
     */
    public const string STATE = 'contenirConsent';

    /**
     * The JavaScript that fires the event with a choice, for a site's own
     * banner or a test page.
     */
    public static function dispatch(Choice $choice): string
    {
        return sprintf(
            'window.dispatchEvent(new CustomEvent(%s, {detail: %s}));',
            Js::value(self::NAME),
            Js::value(['analytics' => $choice->analytics, 'ads' => $choice->ads]),
        );
    }
}
