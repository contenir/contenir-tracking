<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tag;

use Contenir\Tracking\Consent\ConsentEvent;
use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Internal\Js;
use Contenir\Tracking\Provider;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Noscript;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\TagId;
use InvalidArgumentException;
use Override;

use function rawurlencode;
use function sprintf;
use function strtr;

/**
 * The LinkedIn Insight tag. LinkedIn has no consent call of its own, so while
 * tags wait for consent it isn't loaded until the visitor allows ads.
 *
 * @api
 */
final readonly class LinkedInInsight implements TagInterface
{
    public const string SRC = 'https://snap.licdn.com/li.lms-analytics/insight.min.js';

    public const string NOSCRIPT = 'https://px.ads.linkedin.com/collect/?fmt=gif&pid=';

    /**
     * @throws InvalidArgumentException When $id isn't a LinkedIn partner ID.
     */
    public function __construct(
        private TagId $id,
    ) {
        if (Provider::LinkedInInsight !== $id->provider) {
            throw new InvalidArgumentException(sprintf('%s is not a LinkedIn Insight partner ID', $id->value));
        }
    }

    #[Override]
    public function isGoogle(): bool
    {
        return false;
    }

    #[Override]
    public function snippets(ConsentSettings $consent): array
    {
        $start = $consent->waitForConsent
            ? <<<'JS'
                    var state = w[{state}];
                    if (state && state.ads) {
                        load();
                    }
                    w.addEventListener({event}, function (event) {
                        if (event.detail && event.detail.ads === true) {
                            load();
                        }
                    });
                JS
            : '    load();';

        $code = strtr(
            <<<'JS'
                (function (w, d, id) {
                    {nonce}
                    var loaded = false;
                    w._linkedin_partner_id = id;
                    w._linkedin_data_partner_ids = w._linkedin_data_partner_ids || [];
                    w._linkedin_data_partner_ids.push(id);
                    function load() {
                        if (loaded) {
                            return;
                        }
                        loaded = true;
                        if (!w.lintrk) {
                            w.lintrk = function (a, b) {
                                w.lintrk.q.push([a, b]);
                            };
                            w.lintrk.q = [];
                        }
                        {load}
                    }
                {start}
                })(window, document, {id});
                JS,
            [
                '{nonce}' => Js::nonce(),
                '{load}'  => Js::indent(Js::load(Js::value(self::SRC)), spaces: 8),
                '{start}' => strtr($start, [
                    '{state}' => Js::value(ConsentEvent::STATE),
                    '{event}' => Js::value(ConsentEvent::NAME),
                ]),
                '{id}'    => Js::value($this->id->value),
            ],
        );

        $snippets = [Snippet::inline($code, Placement::Head, Category::Marketing)];
        if (! $consent->waitForConsent) {
            $snippets[] = Noscript::pixel(
                self::NOSCRIPT . rawurlencode($this->id->value),
                Placement::BodyStart,
                Category::Marketing,
            );
        }

        return $snippets;
    }
}
