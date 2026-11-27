# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- Public HTTP entry point `GET /health` (US-308), inactive until `http.token` is configured. The token is read from `Authorization: Bearer` (never from the URL) and compared with `hash_equals`; a missing or wrong token answers 404 like an absent page. `http.token` gives `{status, generated_at}`, `http.detail_token` adds the `identifier`, `category` and `status` of each probe (never a summary, a detail, a link, a version, a path or an exception message). 503 only when a probe is `critical`. `http.liveness` (off by default): a request without header gets 200 or 503 with an empty body, for a host health check that sends no header (Railway documents none). Results come from the cached snapshot; `http.rate_limit` (30 per minute and per IP address by default) answers 429 before anything is measured. New dependency: `symfony/rate-limiter` (`^6.4 || ^7.4 || ^8.0`). README (English and French) "Expose the health over HTTP".

## [0.5.0] - 2026-11-27

### Added
- Console command `tmonier:health:check` (US-307): runs the probes through the runner (same time budget and same masking as the page), never through the cache shared with the page, and writes nothing. Text output grouped by category with the status spelled out, or `--format=json` (`status`, `generated_at`, `checks` with `identifier`, `category`, `status`, `summary`); `--category` and `--only` are repeatable. Exit code 0 (all `ok`), 1 (a `warning` or an `unknown`, no `critical`), 2 (a `critical`), 3 (invalid option: unknown category, format or probe identifier, the refused value named); a known category without any probe exits 0 with a message. English and French translations (`tmonier_platform_health.console.*`); README (English and French) with the exit code table and examples for a CI gate after a deployment, a cron and the console of the host (`railway ssh`).
- Probes provided by another plugin, the health plugin staying optional for it (US-303): the plugin declares the health plugin in `suggest` and `require-dev` (never `require`), keeps its probes in a services file of their own (`config/health_checks.php`) and its extension loads that file only when `HealthCheckInterface` exists, through an injectable `\Closure` check (`interface_exists(...)` by default). README (English and French) section with a complete example; a demonstration plugin in `tests/Fixture/DemoWebhooks` and a kernel without the health plugin in the test application. A category without any probe is hidden on the page and in the dashboard panel (already the behaviour of the summary builder, now covered by tests).

### Fixed
- The `data.schema_drift` probe no longer counts the table where Doctrine Migrations records the executed versions (`sylius_migrations` in Sylius) as a table to drop (US-318). `doctrine:schema:update` hides that table only while it runs; on the page, outside any command, the probe read it as drift and could never be `ok`. The reader no longer counts that single `DROP TABLE` statement; the schema filter of the connection is left untouched.

## [0.4.0] - 2026-11-26

### Changed
- End-of-support table reviewed for this release (version `2026-12` kept): PHP 8.6 added (security support until 2030-12-31); the end of support of Sylius 2.3 (December 2027) is now the published date, no longer an estimate.

### Added
- Screenshots of the dashboard panel in the README, English and French, light and dark theme, taken on the B2B demo (US-289).
- Four data probes (US-294), registered by the plugin: `data.connection` (the default Doctrine connection answers, `warning` beyond `data.connection_warning_ms`, 500 ms by default; `critical` when it does not, the cause logged and never displayed), `data.pending_migrations` (`critical` from one pending Doctrine migration), `data.schema_drift` (number of statements between the mapping and the database, never their SQL) and `data.table_growth` (estimated rows of the tables declared under `data.table_growth`, read from the MySQL, MariaDB or PostgreSQL catalogue, never a `COUNT(*)`; `critical` beyond the double of the threshold). Read-only. English and French translations (`tmonier_platform_health.data.*`).
- Three Messenger probes (US-295), read-only, failure transports, `sync://` and `in-memory://` set aside: `data.messenger_pending` (the fullest transport beyond `data.messenger.pending_warning`, 100 by default), `data.messenger_oldest` (age of the oldest waiting message of a Doctrine transport, `MIN(available_at)`; `warning` beyond 15 min, `critical` beyond 1 h) and `data.messenger_failed` (messages in the failure transports, `critical` beyond 100, and every transport without a failure transport, named). The transports are collected at compile time by `MessengerTransportsPass`; no DSN is displayed.
- Two environment probes (US-295): `environment.cache_pool` (round trip of a witness key in `cache.app`, `critical` when it is lost, the cause never displayed) and `environment.writable_dirs` (`var/cache`, `var/log`, `public/media`, named by their relative path only).
- Heartbeat of the scheduled tasks (US-296): console commands declared under `scheduled_tasks.heartbeats` with their rhythm (`'tmonier:feeds:generate': '15m'`) record the end of each run (one row per command, table `tmonier_platform_health_heartbeat`, written through DBAL by a `ConsoleEvents::TERMINATE` listener; a failure to record is logged and never changes the exit code). Probe `scheduled_tasks.heartbeat`: `warning` beyond 2 missed rhythms or on a failed last run, `critical` beyond 4, `unknown` for a command not run yet; one line per command. Thresholds `warning_beats` and `critical_beats`.
- First entity of the plugin and its migration `Version20261126120000`: run `doctrine:migrations:migrate` after the update.

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

[Unreleased]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/thibmonier/sylius-platform-health-plugin/releases/tag/v0.1.0
