# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Added

- `Snippet::$consentMode` and `Snippet::readingConsentMode()`, which mark the Google tag's and Tag Manager's snippets
  as reading Google Consent Mode themselves.
- `HtmlRenderer`'s `advancedConsentMode` option, which leaves those snippets running while it gates the rest, for
  Google's advanced Consent Mode.
- `TrackingConfig::waitingForConsent()`, the same config waiting for consent whatever the settings say, for a site
  with a consent banner.

## [0.1.0] - 2026-10-20

### Added

- Tag definitions for the Google tag (G-, GT- and AW- IDs), Google Tag Manager, the Meta pixel and LinkedIn Insight
  (`Tag\*`), each written as snippets in page order with its consent category.
- ID validation (`Provider`, `TagId`), with messages for editors, including one for retired Universal Analytics
  (UA-) IDs.
- Google Consent Mode v2 (`Consent\ConsentMode`): denied defaults while tags wait for consent, everywhere or only in
  chosen regions (`Consent\Regions`), ad signals, ads data redaction and URL passthrough.
- The `contenir:consent` browser event (`Consent\ConsentEvent`), which a consent banner fires with
  `{analytics, ads}`. It is mapped to Google's consent update, the Meta pixel's grant and revoke, and loading
  LinkedIn Insight.
- `TrackingConfig::fromSettings()`, which reads a site's settings under the keys the CMS's Tracking plugin used
  (`SettingKeys`), and `Tags`, which turns them into snippets. Nothing is written unless the environment tracks.
- `Snippet\HtmlRenderer`, which writes snippets with the page's CSP nonce, and can gate every snippet that isn't
  necessary as `type="text/plain" data-category="…"` for a consent banner.
