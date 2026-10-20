# contenir/tracking

[![Continuous Integration](https://github.com/contenir/contenir-tracking/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-tracking/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-tracking/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-tracking)

Framework-free analytics and tracking tags for [Contenir](https://github.com/contenir) sites:

- tag definitions for the Google tag (gtag.js), Google Tag Manager, the Meta pixel and LinkedIn Insight;
- Google Consent Mode v2, and a browser event, `contenir:consent`, that any consent banner can fire;
- validation of each provider's ID, with messages an editor can act on;
- snippet rendering with a CSP nonce, and consent gating for a banner to switch scripts on.

It has no framework dependency. Sites use it through an adapter, and the CMS through an add-on:

- **Laminas MVC sites:** [contenir/tracking-laminas-mvc](https://github.com/contenir/contenir-tracking-laminas-mvc)
  adds a `tracking()` view helper with `head()` and `bodyStart()`.
- **The CMS:** the [Tracking add-on](https://github.com/contenir-extension/tracking) (`contenir-extension/tracking`)
  holds the settings section, validates each ID as it is saved, and offers the tags to the CMS's script contract.

## Requirements

- PHP 8.3, 8.4 or 8.5

## Usage

```php
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Tags;
use Contenir\Tracking\TrackingConfig;

$config = TrackingConfig::fromSettings($settings);   // the site's settings, as nested arrays
$tags   = new Tags($config);

$renderer = new HtmlRenderer();                       // new HtmlRenderer(gate: true) when a banner gates scripts
echo $renderer->render($tags->at(Placement::Head), $nonce);       // as early in <head> as possible
echo $renderer->render($tags->at(Placement::BodyStart), $nonce);  // straight after <body>
```

`Tags` writes, in this order:

| Snippet | Placement | Consent category |
|---|---|---|
| Consent Mode defaults and the `contenir:consent` bridge | head | necessary |
| Google tag: gtag.js, then its config | head | analytics (marketing for an AW- ID) |
| Tag Manager container | head | analytics |
| Meta pixel | head | marketing |
| LinkedIn Insight | head | marketing |
| Noscript fallbacks for Tag Manager, Meta and LinkedIn, only when tags don't wait for consent | body start | as their tag |

Nothing is written unless the environment tracks (`extensions.tracking.track_environment`), so a copy of a site
never reports to the live site's accounts.

## Settings

`TrackingConfig::fromSettings()` reads these keys (`SettingKeys`). The tag IDs keep the keys the CMS's Tracking plugin
used, so moving a site to the add-on changes nothing:

| Key | Holds | When missing |
|---|---|---|
| `google.analytics.key` | Google tag ID: G-, GT- or AW- | no Google tag |
| `google.tag_manager.id` | Tag Manager container: GTM- | no container |
| `facebook.analytics.key` | Meta pixel ID | no pixel |
| `linkedin.insight.partner_id` | LinkedIn Insight partner ID | no Insight tag |
| `extensions.tracking.wait_for_consent` | hold tags back until the visitor consents | on |
| `extensions.tracking.consent_regions` | where Google's tags wait: country or subdivision codes (ES, US-CA) | everywhere |
| `extensions.tracking.ad_signals` | send Google ad_user_data and ad_personalization when ads are allowed | on |
| `extensions.tracking.url_passthrough` | keep ad click IDs in links while consent is denied | off |
| `extensions.tracking.track_environment` | write tags on this environment; kept in local settings | off |

An ID that isn't valid is left out rather than written, and `TrackingConfig::$problems` says why, by key. A UA-
(Universal Analytics) ID is one of these: Google stopped collecting data for them in July 2023.

## Consent

While tags wait for consent:

- Google's Consent Mode defaults are denied (in the chosen regions, or everywhere), with `wait_for_update` and ads
  data redaction. Tag Manager reads Consent Mode itself.
- The Meta pixel starts revoked.
- LinkedIn Insight, which has no consent call of its own, isn't loaded.
- No noscript fallbacks are written, since a visitor without scripts can't consent.

Without waiting, the defaults are granted and every tag runs as it always has; a banner can still withdraw consent.

### The `contenir:consent` event

A consent banner tells the tags what the visitor allowed by firing `contenir:consent` on `window`, whenever the choice
is made and on every page from the stored choice:

```js
window.dispatchEvent(new CustomEvent('contenir:consent', {
    detail: { analytics: true, ads: false }
}));
```

Only `true` counts as consent. Each choice becomes:

| Provider | `analytics` | `ads` |
|---|---|---|
| Google (Consent Mode update) | `analytics_storage` | `ad_storage`, plus `ad_user_data` and `ad_personalization` when ad signals are on |
| Meta pixel | | `fbq('consent', 'grant')`, or `'revoke'` |
| LinkedIn Insight | | loaded once allowed |

The last choice is kept in `window.contenirConsent`, so a tag a banner switches on later still sees it.
`ConsentEvent::dispatch()` writes the call for a site's own banner. The Consent add-on will fire it; until then a
site's own banner can.

### Gating

`new HtmlRenderer(gate: true)` writes every snippet that isn't necessary as
`<script type="text/plain" data-category="analytics|marketing">`, for a consent banner to switch on, and leaves out the
noscript fallbacks. Only the Consent Mode bridge runs straight away.

## Content Security Policy

Every script carries the nonce passed to `render()`. The Tag Manager, Meta and LinkedIn loaders read their own
nonce while they run and give it to the scripts they load, so a policy with `'nonce-…' 'strict-dynamic'` lets the
whole chain run. Inline snippets can't contain `</script`, attribute values are escaped, and event-handler
attributes are dropped.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
