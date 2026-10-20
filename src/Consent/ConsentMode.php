<?php

declare(strict_types=1);

namespace Contenir\Tracking\Consent;

use Contenir\Tracking\Internal\Js;
use Contenir\Tracking\Snippet\Category;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Snippet\Snippet;

use function array_keys;
use function array_map;
use function implode;
use function sprintf;
use function strtr;

/**
 * Google Consent Mode v2 and the contenir:consent bridge: the first script on
 * the page. It sets Google's consent defaults before any tag loads, records
 * each contenir:consent choice in window.contenirConsent, and passes it to
 * Google as a consent update.
 *
 * @api
 */
final readonly class ConsentMode
{
    /**
     * How long Google's tags wait for a banner's first update, in
     * milliseconds.
     */
    public const int WAIT_FOR_UPDATE = 500;

    private const string GRANTED = 'granted';

    private const string DENIED = 'denied';

    public function __construct(
        private ConsentSettings $settings,
    ) {}

    /**
     * The bridge alone, for a page with no Google tag: it records each
     * contenir:consent choice for the other tags.
     */
    public function bridge(): Snippet
    {
        return Snippet::inline($this->listener(''), Placement::Head, Category::Necessary);
    }

    /**
     * The consent default commands, in order: what gtag('consent',
     * 'default', …) receives. Waiting for consent in some regions writes a
     * granted default for everywhere else, then the denied one for those
     * regions; Google applies the most specific.
     *
     * @return list<array<string, string|int|list<string>>>
     */
    public function defaults(): array
    {
        if (! $this->settings->waitForConsent) {
            return [$this->signals(self::GRANTED)];
        }

        $denied = [...$this->signals(self::DENIED), 'wait_for_update' => self::WAIT_FOR_UPDATE];
        if ($this->settings->regions->isEverywhere()) {
            return [$denied];
        }

        return [$this->signals(self::GRANTED), [...$denied, 'region' => $this->settings->regions->codes]];
    }

    /**
     * The bridge with Google's part: gtag(), the consent defaults, and an
     * update for every contenir:consent choice.
     */
    public function googleBridge(): Snippet
    {
        return Snippet::inline($this->googleScript(), Placement::Head, Category::Necessary);
    }

    /**
     * What gtag('consent', 'update', …) receives for a choice. Without ad
     * signals, ad_user_data and ad_personalization stay denied whatever the
     * visitor allows.
     *
     * @return array<string, string>
     */
    public function update(Choice $choice): array
    {
        return array_map(
            static fn(?string $source): string => match ($source) {
                'analytics' => $choice->analytics ? self::GRANTED : self::DENIED,
                'ads'       => $choice->ads ? self::GRANTED : self::DENIED,
                default     => self::DENIED,
            },
            $this->sources(),
        );
    }

    private function googleScript(): string
    {
        $lines = [
            'window.dataLayer = window.dataLayer || [];',
            'window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };',
            ...array_map(
                /** @param array<string, string|int|list<string>> $default */
                static fn(array $default): string => sprintf("gtag('consent', 'default', %s);", Js::value($default)),
                $this->defaults(),
            ),
        ];

        if ($this->settings->waitForConsent) {
            $lines[] = "gtag('set', 'ads_data_redaction', true);";
        }

        if ($this->settings->urlPassthrough) {
            $lines[] = "gtag('set', 'url_passthrough', true);";
        }

        $update = implode(', ', array_map(
            static fn(string $signal, ?string $source): string => sprintf(
                '%s: %s',
                $signal,
                null === $source ? Js::value(self::DENIED) : "{$source} ? 'granted' : 'denied'",
            ),
            array_keys($this->sources()),
            $this->sources(),
        ));

        return implode("\n", $lines) . "\n" . $this->listener("gtag('consent', 'update', {{$update}});");
    }

    private function listener(string $then): string
    {
        return strtr(
            <<<'JS'
                window.addEventListener({event}, function (event) {
                    var detail = event.detail || {};
                    var analytics = detail.analytics === true;
                    var ads = detail.ads === true;
                    window[{state}] = {analytics: analytics, ads: ads};{then}
                });
                JS,
            [
                '{event}' => Js::value(ConsentEvent::NAME),
                '{state}' => Js::value(ConsentEvent::STATE),
                '{then}'  => '' === $then ? '' : "\n    {$then}",
            ],
        );
    }

    /**
     * Every signal at $value, except those that are never granted.
     *
     * @return array<string, string>
     */
    private function signals(string $value): array
    {
        return array_map(
            static fn(?string $source): string => null === $source ? self::DENIED : $value,
            $this->sources(),
        );
    }

    /**
     * Which part of a choice grants each signal; null for never.
     *
     * @return array<string, 'analytics'|'ads'|null>
     */
    private function sources(): array
    {
        $adSignal = $this->settings->adSignals ? 'ads' : null;

        return [
            'ad_storage'         => 'ads',
            'ad_user_data'       => $adSignal,
            'ad_personalization' => $adSignal,
            'analytics_storage'  => 'analytics',
        ];
    }
}
