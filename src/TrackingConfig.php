<?php

declare(strict_types=1);

namespace Contenir\Tracking;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Consent\Regions;
use Contenir\Tracking\Internal\SettingsArray;

/**
 * A site's tracking: which tags it has, how they treat consent, and whether
 * this environment tracks at all.
 *
 * @api
 */
final readonly class TrackingConfig
{
    /**
     * @param array<value-of<Provider>, TagId> $tags     The valid tag IDs, by provider
     * @param bool                             $track    Whether this environment writes the tags
     * @param array<string, string>            $problems What was wrong with each stored value left out, by setting key
     */
    public function __construct(
        public array $tags = [],
        public ConsentSettings $consent = new ConsentSettings(),
        public bool $track = false,
        public array $problems = [],
    ) {}

    /**
     * Reads the tracking values from a site's settings, as nested arrays
     * (['google' => ['analytics' => ['key' => 'G-…']]]). A tag ID or region
     * list that isn't valid is left out and its problem kept. Unless the
     * settings say otherwise, this environment doesn't track and tags wait
     * for consent.
     *
     * @param array<array-key, mixed> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $values   = new SettingsArray($settings);
        $tags     = [];
        $problems = [];
        foreach (Provider::cases() as $provider) {
            $value   = $values->text($provider->settingKey());
            $problem = $provider->problem($value);
            if (null !== $problem) {
                $problems[$provider->settingKey()] = $problem;
                continue;
            }

            $id = TagId::tryParse($provider, $value);
            if (null !== $id) {
                $tags[$provider->value] = $id;
            }
        }

        $regions        = $values->text(SettingKeys::CONSENT_REGIONS);
        $regionProblems = Regions::problems($regions);
        if ([] !== $regionProblems) {
            $problems[SettingKeys::CONSENT_REGIONS] = $regionProblems[0];
            $regions                                = '';
        }

        return new self(
            $tags,
            new ConsentSettings(
                waitForConsent: $values->flag(SettingKeys::WAIT_FOR_CONSENT, default: true),
                regions: Regions::parse($regions),
                adSignals: $values->flag(SettingKeys::AD_SIGNALS, default: true),
                urlPassthrough: $values->flag(SettingKeys::URL_PASSTHROUGH, default: false),
            ),
            $values->flag(SettingKeys::TRACK_ENVIRONMENT, default: false),
            $problems,
        );
    }

    /**
     * The tag ID for $provider, or null when the site has none.
     */
    public function tag(Provider $provider): ?TagId
    {
        return $this->tags[$provider->value] ?? null;
    }

    /**
     * The same config, waiting for consent whatever the settings say: what a
     * site with a consent banner needs, so Google's defaults are denied,
     * the Meta pixel starts revoked and LinkedIn waits until the banner's
     * contenir:consent event allows them.
     */
    public function waitingForConsent(): self
    {
        return new self(
            $this->tags,
            new ConsentSettings(
                waitForConsent: true,
                regions: $this->consent->regions,
                adSignals: $this->consent->adSignals,
                urlPassthrough: $this->consent->urlPassthrough,
            ),
            $this->track,
            $this->problems,
        );
    }

    /**
     * Whether this environment writes any tags.
     */
    public function writesTags(): bool
    {
        return $this->track && [] !== $this->tags;
    }
}
