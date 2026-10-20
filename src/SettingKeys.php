<?php

declare(strict_types=1);

namespace Contenir\Tracking;

/**
 * Where a site's settings keep each tracking value, as dotted keys. The tag
 * IDs keep the keys the CMS's Tracking plugin used, so moving to the add-on
 * changes nothing on a site; the rest sit under extensions.tracking, the
 * add-on's own namespace.
 *
 * @api
 */
final class SettingKeys
{
    public const string GOOGLE_TAG = 'google.analytics.key';

    public const string TAG_MANAGER = 'google.tag_manager.id';

    public const string META_PIXEL = 'facebook.analytics.key';

    public const string LINKEDIN_INSIGHT = 'linkedin.insight.partner_id';

    public const string WAIT_FOR_CONSENT = 'extensions.tracking.wait_for_consent';

    public const string CONSENT_REGIONS = 'extensions.tracking.consent_regions';

    public const string AD_SIGNALS = 'extensions.tracking.ad_signals';

    public const string URL_PASSTHROUGH = 'extensions.tracking.url_passthrough';

    /**
     * Kept in the environment's local settings, never in a committed file.
     */
    public const string TRACK_ENVIRONMENT = 'extensions.tracking.track_environment';
}
