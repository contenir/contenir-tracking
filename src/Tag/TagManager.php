<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tag;

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
 * A Google Tag Manager container. The container reads Consent Mode itself,
 * so its own tags follow the visitor's choice. Its noscript frame is written
 * only when tags don't wait for consent: a visitor without scripts can't give
 * it.
 *
 * @api
 */
final readonly class TagManager implements TagInterface
{
    public const string SRC = 'https://www.googletagmanager.com/gtm.js?id=';

    public const string NOSCRIPT = 'https://www.googletagmanager.com/ns.html?id=';

    /**
     * @throws InvalidArgumentException When $id isn't a Tag Manager container ID.
     */
    public function __construct(
        private TagId $id,
    ) {
        if (Provider::TagManager !== $id->provider) {
            throw new InvalidArgumentException(sprintf('%s is not a Tag Manager container ID', $id->value));
        }
    }

    #[Override]
    public function isGoogle(): bool
    {
        return true;
    }

    #[Override]
    public function snippets(ConsentSettings $consent): array
    {
        $code = strtr(
            <<<'JS'
                (function (w, d, id) {
                    {nonce}
                    w.dataLayer = w.dataLayer || [];
                    w.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
                    {load}
                })(window, document, {id});
                JS,
            [
                '{nonce}' => Js::nonce(),
                '{load}'  => Js::indent(Js::load(Js::value(self::SRC) . ' + encodeURIComponent(id)'), spaces: 4),
                '{id}'    => Js::value($this->id->value),
            ],
        );

        $snippets = [Snippet::inline($code, Placement::Head, Category::Analytics)];
        if (! $consent->waitForConsent) {
            $snippets[] = Noscript::frame(
                self::NOSCRIPT . rawurlencode($this->id->value),
                Placement::BodyStart,
                Category::Analytics,
            );
        }

        return $snippets;
    }
}
