<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tag;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Internal\Js;
use Contenir\Tracking\Provider;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;
use Contenir\Tracking\TagId;
use InvalidArgumentException;
use Override;

use function rawurlencode;
use function sprintf;

/**
 * The Google tag (gtag.js) for a Google Analytics 4 measurement ID (G-), a
 * Google tag ID (GT-) or a Google Ads conversion ID (AW-). It relies on the
 * gtag() function Consent Mode defines first.
 *
 * @api
 */
final readonly class GoogleTag implements TagInterface
{
    public const string SRC = 'https://www.googletagmanager.com/gtag/js?id=';

    /**
     * @throws InvalidArgumentException When $id isn't a Google tag ID.
     */
    public function __construct(
        private TagId $id,
    ) {
        if (Provider::GoogleTag !== $id->provider) {
            throw new InvalidArgumentException(sprintf('%s is not a Google tag ID', $id->value));
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
        $category = $this->id->isAds() ? Category::Marketing : Category::Analytics;
        $config   = $consent->adSignals
            ? sprintf("gtag('config', %s);", Js::value($this->id->value))
            : sprintf(
                "gtag('config', %s, %s);",
                Js::value($this->id->value),
                Js::value(['allow_google_signals' => false, 'allow_ad_personalization_signals' => false]),
            );

        return [
            Snippet::src(
                self::SRC . rawurlencode($this->id->value),
                Placement::Head,
                $category,
                ['async' => true],
            )->readingConsentMode(),
            Snippet::inline("gtag('js', new Date());\n{$config}", Placement::Head, $category)->readingConsentMode(),
        ];
    }
}
