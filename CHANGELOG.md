# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

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
