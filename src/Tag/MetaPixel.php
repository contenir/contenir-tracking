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
 * The Meta pixel. Waiting for consent starts it revoked, unless the visitor
 * already allowed ads, and every contenir:consent choice grants or revokes
 * it. It counts a PageView on every page.
 *
 * @api
 */
final readonly class MetaPixel implements TagInterface
{
    public const string SRC = 'https://connect.facebook.net/en_US/fbevents.js';

    public const string NOSCRIPT = 'https://www.facebook.com/tr?ev=PageView&noscript=1&id=';

    /**
     * @throws InvalidArgumentException When $id isn't a Meta pixel ID.
     */
    public function __construct(
        private TagId $id,
    ) {
        if (Provider::MetaPixel !== $id->provider) {
            throw new InvalidArgumentException(sprintf('%s is not a Meta pixel ID', $id->value));
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
        $revoke = $consent->waitForConsent
            ? sprintf(
                "\n    var state = w[%s];\n    w.fbq('consent', state && state.ads ? 'grant' : 'revoke');",
                Js::value(ConsentEvent::STATE),
            )
            : '';

        $code = strtr(
            <<<'JS'
                (function (w, d, id) {
                    {nonce}
                    if (!w.fbq) {
                        var fbq = w.fbq = function () {
                            fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
                        };
                        if (!w._fbq) {
                            w._fbq = fbq;
                        }
                        fbq.push = fbq;
                        fbq.loaded = true;
                        fbq.version = '2.0';
                        fbq.queue = [];
                        {load}
                    }{revoke}
                    w.fbq('init', id);
                    w.fbq('track', 'PageView');
                    w.addEventListener({event}, function (event) {
                        w.fbq('consent', event.detail && event.detail.ads === true ? 'grant' : 'revoke');
                    });
                })(window, document, {id});
                JS,
            [
                '{nonce}'  => Js::nonce(),
                '{load}'   => Js::indent(Js::load(Js::value(self::SRC)), spaces: 8),
                '{revoke}' => $revoke,
                '{event}'  => Js::value(ConsentEvent::NAME),
                '{id}'     => Js::value($this->id->value),
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
