# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- Screenshots of the dashboard panel in the README, English and French, light and dark theme, taken on the B2B demo (US-289).

## [0.3.0] - 2026-11-25

### Added
- Platform health panel on the administration dashboard (US-289): one line per category with its worst status spelled out and an icon of its own, a title that counts the critical categories ("1 critical category"), and a "See the details" link to the page. Deferred Live Component (`tmonier_platform_health:dashboard`) that reads the cache shared with the page: the dashboard never runs a probe while it is rendered. Declared through the `sylius_admin.dashboard.index.content` Twig hook in `config/twig_hooks/admin.yaml`, no Sylius template overridden.
- The panel is rendered only for an administrator who passes `PLATFORM_HEALTH_VIEW`; its component URL answers 403 to the others. A panel that fails shows nothing: the exception is logged with its trace and the dashboard stays intact.
- English and French translations of the panel (`tmonier_platform_health.ui.dashboard.*`).

## [0.2.1] - 2026-11-24

### Changed
- Translations (EN, FR) reviewed: the empty-state text no longer claims that the plugin ships no diagnostic of its own, the French empty-state title says "contrôle" like the rest of the page, and the loading text uses the ellipsis character.
- README (EN, FR) rewritten for the shipped version: environment probes, `access.role` and `ROLE_PLATFORM_HEALTH_VIEW`, accessibility, link to the demo and to the author; real screenshots (French and English, light and dark theme, 1280 px) taken on the test application. The demo section says that the B2B demo installs the plugin.

### Fixed
- The French and English catalogues are now guarded by tests: same keys and placeholders in both languages, no key referenced by the code or the templates missing, no unused key, and a wording rule (check/contrôle vocabulary, no stale text). They run in the unit and non-unit suites.

## [0.2.0] - 2026-11-23

### Added
- Six environment probes (US-293), registered by the plugin: `environment.php_version`, `environment.symfony_version`, `environment.sylius_version`, `environment.php_extensions`, `environment.opcache`, `environment.app_debug`. Read-only, no network call, no environment variable value rendered.
- `EndOfSupportTable`: end-of-support dates of PHP and Sylius frozen in the code, version `2026-12` displayed by the probes; a table older than 12 months turns them to `warning`, a branch absent from it is `unknown`. Symfony uses its own `Kernel::END_OF_MAINTENANCE` and `END_OF_LIFE`.
- `tmonier_sylius_platform_health.environment.support_warning_days` (180 by default).
- Route `tmonier_platform_health_documentation`: action link of the environment probes (redirects to the README).
- English and French translations of the probes.
- `tmonier_sylius_platform_health.access.role` (US-290): role that the `PLATFORM_HEALTH_VIEW` attribute requires, `ROLE_ADMINISTRATION_ACCESS` by default (no change from v0.1.0). A value that does not start with `ROLE_` fails the compilation of the container. Production recommendation: `ROLE_PLATFORM_HEALTH_VIEW`, granted with `sylius:user:promote <email> ROLE_PLATFORM_HEALTH_VIEW --user-type=admin`.
- `PlatformHealthVoter` for the `PLATFORM_HEALTH_VIEW` attribute; the menu entry is shown only to who holds the right.
- `SensitiveValueMasker`: DSN credentials, `password=`-like pairs, long keys and absolute paths are masked in every summary and detail; an execution trace is replaced by a generic message and the faulty probe is logged by its identifier.
- Accessibility WCAG 2.2 AA of the page (US-291): status spelled out and drawn with an icon of distinct shape for each status (never colour alone), probes in a `<table>` with `<caption>` and `scope` inside a native `<details>`, `role="status"` region announcing the update, `aria-busy` while the probes run, action links named after the probe, `prefers-reduced-motion` honoured.
- Colour tokens `--tph-*` that follow `data-bs-theme` (light and dark theme of the Sylius 2 administration), in `templates/admin/_tokens.html.twig`; a unit test computes the WCAG contrast ratio of every pair (4.5:1 for text, 3:1 for icons and borders) in both themes.
- Translations (EN, FR) of the table headers, the caption and the update announcement.

### Changed
- The status badges and the Refresh button use the plugin's own tokens instead of the Bootstrap utility classes (`bg-success`, `btn-outline-primary`...), whose contrast was below 4.5:1 in the light theme.
- `symfony/security-core` and `symfony/security-http` are now required (voter and `#[IsGranted]` guards).

### Security
- The page, the Live Component endpoint (`/admin/{_locale}/_components/...`) and its refresh action are guarded by `PLATFORM_HEALTH_VIEW`: an administrator without the configured role gets a 403 (OWASP A01). With the default role the behaviour is unchanged.
- Secrets and execution traces a probe lets slip are masked on the page (OWASP A02, A10).

## [0.1.0] - 2026-11-22

### Added
- Plugin skeleton (bundle, configuration prefix `tmonier_sylius_platform_health`, test application, CI on Sylius 2.2 and 2.3). The plugin ships no real probe yet.
- Probe contract: `HealthCheckInterface`, `HealthStatus`, `HealthCategory`, `HealthResult`, `HealthActionLink`, discovered by an autoconfigured tag.
- `HealthCheckRunner` with passive safety: any `Throwable` is logged with its trace and becomes `unknown` with a generic summary; the time budget (`time_budget_ms`, 500 ms by default) is measured by the runner after the probe returns, an over-budget result becomes `unknown`.
- Compiler pass: two probes with the same identifier fail the container compilation, naming the identifier and both classes.
- English and French translations of the generic summaries.
- Administration page *Configuration > Platform health* (`/admin/platform-health`, Sylius admin firewall): summary by category (worst status, most severe category first, an `unknown` probe is "not measurable" and never critical), folded detail per probe, action link built from the route of the probe's `HealthActionLink` (no link, no error, for a route that does not exist), empty state when no probe is installed.
- Deferred render: Live Component with `loading="defer"`, the skeleton answers without running any probe.
- Shared short cache of the results (`cache_ttl`, 60 seconds by default, 0 disables it) and a "Refresh" action that ignores the cache and replaces it.
- English and French translations of the page, README section and screenshots (the demonstration probes live in the test application).

[Unreleased]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/thibmonier/sylius-platform-health-plugin/releases/tag/v0.1.0
