# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- Six environment probes (US-293), registered by the plugin: `environment.php_version`, `environment.symfony_version`, `environment.sylius_version`, `environment.php_extensions`, `environment.opcache`, `environment.app_debug`. Read-only, no network call, no environment variable value rendered.
- `EndOfSupportTable`: end-of-support dates of PHP and Sylius frozen in the code, version `2026-12` displayed by the probes; a table older than 12 months turns them to `warning`, a branch absent from it is `unknown`. Symfony uses its own `Kernel::END_OF_MAINTENANCE` and `END_OF_LIFE`.
- `tmonier_sylius_platform_health.environment.support_warning_days` (180 by default).
- Route `tmonier_platform_health_documentation`: action link of the environment probes (redirects to the README).
- English and French translations of the probes.

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

[Unreleased]: https://github.com/thibmonier/sylius-platform-health-plugin/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/thibmonier/sylius-platform-health-plugin/releases/tag/v0.1.0
