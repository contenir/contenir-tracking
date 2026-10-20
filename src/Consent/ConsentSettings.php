<?php

declare(strict_types=1);

namespace Contenir\Tracking\Consent;

/**
 * How tags treat consent.
 *
 * Waiting for consent writes Google's Consent Mode defaults as denied, starts
 * the Meta pixel revoked and holds LinkedIn back, until a contenir:consent
 * event grants them. Without it, tags run as they always have, and a
 * contenir:consent event can still withdraw them.
 *
 * @api
 */
final readonly class ConsentSettings
{
    /**
     * Where Google's tags wait; everywhere when empty. Meta and LinkedIn wait everywhere.
     */
    public Regions $regions;

    /**
     * @param bool    $waitForConsent Hold tags back until the visitor consents
     * @param ?Regions $regions       Where Google's tags wait; null for everywhere
     * @param bool    $adSignals      Send Google the ad_user_data and ad_personalization signals when the visitor allows ads
     * @param bool    $urlPassthrough Keep ad click IDs in links while consent is denied, so conversions still count
     */
    public function __construct(
        public bool $waitForConsent = true,
        ?Regions $regions = null,
        public bool $adSignals = true,
        public bool $urlPassthrough = false,
    ) {
        $this->regions = $regions ?? Regions::everywhere();
    }
}
