# Sylius Platform Health Plugin

> Français : [README.fr.md](README.fr.md)

[![Build](https://github.com/thibmonier/sylius-platform-health-plugin/actions/workflows/build.yaml/badge.svg)](https://github.com/thibmonier/sylius-platform-health-plugin/actions/workflows/build.yaml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Sylius 2: a probe contract that you implement in a few lines, and a runner that a faulty probe can never break.**

## Status

The plugin ships the **contract, the runner, the administration page, a dashboard panel, eight environment probes, seven data probes and a heartbeat of the scheduled tasks and a console command (`tmonier:health:check`)** (PHP, Symfony and Sylius versions and end of support, PHP extensions, OPcache, debug mode, application cache, writable directories; database connection, pending migrations, schema drift, table growth, Messenger queues, age of the oldest message, failed messages; declared cron commands that no longer succeed), plus a dedicated, optional access role (`ROLE_PLATFORM_HEALTH_VIEW`). The screenshots below come from the test application: its page also lists five demonstration probes that live in `tests/`, not in the package. Languages: English and French.

## The administration page

*Configuration > Platform health* (`/admin/platform-health`, guarded by the `PLATFORM_HEALTH_VIEW` attribute, see [Access control](#access-control)).

![The platform health page, light theme: one card per category, the most severe first](docs/screenshots/platform-health-en-light.png)

![The same page in the dark theme](docs/screenshots/platform-health-en-dark.png)

The French page: [light](docs/screenshots/platform-health-fr-light.png), [dark](docs/screenshots/platform-health-fr-dark.png).

- **Summary by category.** Each category takes the worst status of its probes (`critical`, `warning`, `unknown`, `ok`): the category that holds a critical probe comes first. An `unknown` probe reads "Not measurable" with its short reason; it never counts as critical, and never lets a category read "OK".
- **Detail by probe, folded by default**: status (always spelled out, never carried by colour alone), summary, detail and the **action link** of the probe, built from the route name and parameters of its `HealthActionLink`. A probe that points to a route that does not exist gets no link, not an error.
- **Deferred render** (Symfony UX Live Component, `loading="defer"`): the page answers at once with a skeleton, the results arrive right after. No probe runs while the skeleton is built.
- **Shared short cache**: the results are kept for `cache_ttl` seconds (60 by default, `0` disables the cache), so three administrators looking at the page run the probes once. The **Refresh** button runs them again and replaces the cache.
- **No check installed**: the page says so and points to this documentation, it is neither empty nor an error.
- A failing probe never breaks the page and its exception message is never displayed (it goes to the application log).

```yaml
# config/routes/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    resource: "@TmonierSyliusPlatformHealthPlugin/config/routes.yaml"
```

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    time_budget_ms: 500   # per probe
    cache_ttl: 60         # seconds shared between administrators, 0 disables
    access:
        role: ROLE_ADMINISTRATION_ACCESS   # ROLE_PLATFORM_HEALTH_VIEW recommended in production
```

The menu entry and the page hooks are registered by the plugin itself, nothing else to import.

## The dashboard panel

The first page after the login answers "is everything fine?" without a click: a compact **Platform health** panel sits on the administration dashboard, between the statistics and the latest orders.

![The Platform health panel on the dashboard, light theme: one line per category with its status spelled out, and a "See the details" link](docs/screenshots/platform-health-dashboard-en-light.png)

![The same panel in the dark theme](docs/screenshots/platform-health-dashboard-en-dark.png)

Taken on the B2B demo, with its real probes: Symfony 8.1 nears its end of support, hence the warning. The French panel: [light](docs/screenshots/platform-health-dashboard-fr-light.png), [dark](docs/screenshots/platform-health-dashboard-fr-dark.png).

- **One line per category**, the most severe first, with its worst status **spelled out** ("OK", "Warning", "Critical", "Not measurable") and an icon of its own: the status is never carried by colour alone. The same rules and the same colour tokens as the page, light and dark theme.
- **The title says it** when something is critical: "1 critical category", "2 critical categories". Otherwise it reads "Platform health". A "See the details" link opens the page.
- **The dashboard never waits for a probe.** The panel is a deferred Live Component that reads the **same cache as the page** (`cache_ttl`): the dashboard answers with a skeleton, then the panel completes. With a warm cache no probe runs; with a cold cache the first read feeds the cache for the page, after the dashboard has been rendered.
- **Same right as the page**: the panel is rendered only for an administrator who passes `PLATFORM_HEALTH_VIEW` (see [Access control](#access-control)), and its component URL answers 403 to the others.
- **A panel that fails disappears quietly**: the exception is logged with its trace, nothing is displayed, the dashboard stays intact.
- Registered by the plugin through a Twig hook (`sylius_admin.dashboard.index.content`, in `config/twig_hooks/admin.yaml`): no Sylius template is overridden, nothing to configure. To remove it, set `enabled: false` on the `platform_health` entry of that hook in your own `sylius_twig_hooks` configuration.

## Access control

**By default every administrator sees the page** (`access.role` is `ROLE_ADMINISTRATION_ACCESS`, the behaviour of v0.1.0, so an upgrade breaks nothing). A diagnostic screen is a map of the installation (versions, paths, states of queues): **in production, set `access.role` to `ROLE_PLATFORM_HEALTH_VIEW`** and grant it one administrator at a time, with the command of Sylius itself:

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    access:
        role: ROLE_PLATFORM_HEALTH_VIEW
```

```bash
bin/console sylius:user:promote admin@example.com ROLE_PLATFORM_HEALTH_VIEW --user-type=admin
```

The administrator logs in again to carry the new role. The plugin ships no assignment command and no `role_hierarchy`: it does not rewrite the security of your application (a role you declared in your own `role_hierarchy` is honoured). A value that is not a role (it must start with `ROLE_`) fails the compilation of the container.

The guard is a voter for the `PLATFORM_HEALTH_VIEW` attribute, set on the route of the page **and** on the Live Component class (`#[IsGranted]`): the component is also served under `/admin/{_locale}/_components/...`, which the administration only protects with `ROLE_ADMINISTRATION_ACCESS`. An administrator without the role gets a **403** on the page, on the component URL and on the refresh action, and the menu entry is hidden.

### Sensitive values and execution traces

Probes are written by third parties, so the page masks what a probe lets slip, in every summary and detail (a probe should still never produce a sensitive value):

| Found in a summary or detail | Shown as |
|------------------------------|----------|
| DSN credentials (`mysql://shop:pass@db/sylius`) | `mysql://***@db/sylius` |
| `secret`, `password`, `passwd`, `pwd`, `token`, `key`, `dsn` pairs (`password=hunter2`, `API_KEY: abc`) | `password=***`, `API_KEY: ***` |
| 32+ character key containing a digit | `***` |
| Absolute path inside the project | path relative to the project |
| Other absolute file path | `.../file.ext` |
| Execution trace (`Stack trace:`, `#0 /path/File.php(12): ...`) | "The detail was hidden: see the application log.", and the probe is logged by its identifier (never the trace) |

## Accessibility

The page targets **WCAG 2.2 level AA** (the target of the administration theme, not AAA).

- **A status is never carried by colour alone**: each category and each probe spells it out ("OK", "Warning", "Critical", "Not measurable") next to an icon whose *shape* differs for each status (check, triangle, octagon, question mark). The icons are decorative (`aria-hidden`), the word carries the meaning.
- **The detail is a real table**: `<caption>`, `scope="col"` headers, one `scope="row"` header per probe, inside a native `<details>`/`<summary>` (keyboard and screen reader for free). An action link reads "Fix: <summary of the probe>", not "click here".
- **Refreshing is announced**: a `role="status"` region (`aria-live="polite"`) says "Results updated" with the time of the measure, the focus stays on the **Refresh** button, and the results carry `aria-busy="true"` while the probes run (set by the small script of the plugin's admin entrypoint). The loading skeleton honours `prefers-reduced-motion`.
- **Light and dark theme**: the colours are CSS variables (`--tph-*`) that follow `data-bs-theme` of the administration (Tabler), declared once in `templates/admin/_tokens.html.twig`; no template carries a colour. Every pair (text on surface, label on badge, icon on badge, border on surface) is **measured** by `tests/Unit/Accessibility/StatusContrastTest.php`, which fails with the token, the background and the ratio found when text drops under 4.5:1 or an icon or border under 3:1, in both themes. Change a token together with that test.

What is not covered: AAA, right-to-left, forced colours beyond a manual look. The tests prove the markup and the contrast; a screen reader pass remains a manual check.

## Environment probes

Eight probes of the `environment` category, registered by the plugin itself (nothing to declare), read-only, **no network call**, no value of an environment variable ever displayed. Each non-`ok` result links to this documentation (`Fix`).

| Identifier | Measures | Statuses |
|---|---|---|
| `environment.php_version` | PHP version and end of security support of its branch | `ok`; `warning` within 180 days of the end; `critical` the day after; `unknown` for a branch absent from the table |
| `environment.symfony_version` | `Kernel::END_OF_MAINTENANCE` and `Kernel::END_OF_LIFE`, supplied by Symfony itself (no table) | same thresholds; `warning` once the maintenance has ended |
| `environment.sylius_version` | Sylius version and end of support of its minor branch | same as PHP |
| `environment.php_extensions` | `intl`, `mbstring`, `ctype`, `iconv`, `xml`, `curl`, `pdo`, `gd` or `imagick` | `ok`; `critical` listing **only the missing** extensions |
| `environment.opcache` | OPcache active for the SAPI that serves the page | `ok`; `warning` if inactive; `unknown` if the API is restricted (`opcache.restrict_api`) |
| `environment.app_debug` | `kernel.debug` and `kernel.environment` | `ok` in production without debug; `critical` with debug in production; `warning` outside production |
| `environment.cache_pool` | a witness key of the plugin written, read back and deleted in `cache.app` | `ok`; `critical` when the cache loses it or throws (the cause, which may hold the DSN of the cache server, is never displayed) |
| `environment.writable_dirs` | `var/cache`, `var/log` and `public/media` (or its parent before the first upload) writable by PHP | `ok`; `critical` listing the directories by their **relative** name, never the absolute path of the server |

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    environment:
        support_warning_days: 180   # PHP, Symfony and Sylius probes warn this many days before the end of support
```

**The end-of-support table** (`EndOfSupportTable`) is frozen in the code, so the administration never calls the network. Its version (`2026-12`) is displayed by the PHP and Sylius probes ("Table of 2026-12"). It is **renewed with every release of the plugin**; twelve months after its version, those two probes turn to `warning` ("dates table to renew") instead of a false `ok`. A PHP or Sylius branch absent from the table is `unknown` ("branch absent from the table"), never `ok`. The probes read the date through the Symfony `clock` service: tests inject a fixed clock.

## Data probes

Seven probes of the `data` category, registered by the plugin (nothing to declare), **read-only**: none executes a migration, alters the schema, counts the rows of a table, or consumes, retries or removes a Messenger message. Only the default Doctrine connection is probed. Each non-`ok` result links to this documentation (`Fix`).

| Identifier | Measures | Statuses |
|---|---|---|
| `data.connection` | the default connection answers a trivial query (`SELECT 1`), and how fast | `ok` with the duration; `warning` beyond 500 ms; `critical` when the database does not answer (the cause goes to the log, **never the DSN, the host or the user** to the screen) |
| `data.pending_migrations` | Doctrine migrations of the code not executed, as `doctrine:migrations:status` reports them | `ok`; `critical` from one pending migration (run `bin/console doctrine:migrations:migrate`); `warning` for an executed migration the code no longer knows; `unknown` without Doctrine migrations |
| `data.schema_drift` | statements that would align the database with the Doctrine mapping (`doctrine:schema:update --dump-sql`) | `ok`; `warning` with the **number** of statements, never their SQL (run `bin/console doctrine:schema:validate` to see them); `unknown` without a Doctrine ORM |
| `data.table_growth` | estimated rows of the **declared** tables, read from the catalogue of the database (MySQL and MariaDB: `information_schema.TABLES`, PostgreSQL: `pg_class`), **never a `COUNT(*)`** | `ok` under the threshold; `warning` beyond it; `critical` beyond the double; `unknown` for a missing table or another database |
| `data.messenger_pending` | waiting messages of each Messenger transport a worker consumes (failure transports, `sync://` and `in-memory://` set aside) | `ok`; `warning` naming the fullest transport beyond 100 messages; `unknown` for a transport that cannot count |
| `data.messenger_oldest` | how long the oldest message has been waiting, read with `MIN(available_at)` on the table of a **Doctrine** transport | `ok`; `warning` beyond 15 min; `critical` beyond 1 h (no worker consumes the queue); `unknown` without a Doctrine transport |
| `data.messenger_failed` | messages in the failure transports, and the transports that have none | `ok`; `warning` from one failed message (critical beyond 100); `warning` naming each transport **without a failure transport**, whose failed messages are lost |

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    data:
        connection_warning_ms: 500      # data.connection warns beyond this answer time
        messenger:
            pending_warning: 100        # waiting messages of a transport from which data.messenger_pending warns
            oldest_warning_minutes: 15  # age of the oldest message from which data.messenger_oldest warns
            oldest_critical_minutes: 60 # and from which it is critical; must be above the warning
            failed_critical: 100        # failed messages beyond which data.messenger_failed is critical
        table_growth:                   # no table is watched by default
            tmonier_audit_log: 100000   # estimated rows from which the table warns (critical beyond 200000)
            sylius_order: 2000000
```

Declare the tables that a scheduled purge keeps small (logs, journals, sent messages): when the purge stops, the estimate grows past its threshold. The estimate is the one the database keeps for its query planner; it lags behind the real count (PostgreSQL reads `0` for a table never analysed), which is enough for an order of magnitude. Computing the schema drift loads the whole mapping: it fits in the time budget of the runner with the production metadata cache; if it does not, the probe reads `unknown` ("time budget exceeded"), like any slow probe.

## Scheduled tasks heartbeat

A cron that stops writes nothing in any log. The `scheduled_tasks.heartbeat` probe detects **the absence of a success**: each declared console command records the end of its runs, and the probe compares the last success with the rhythm declared.

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    scheduled_tasks:
        heartbeats:                              # no command is watched by default
            'tmonier:feeds:generate': '15m'      # the rhythm of its cron: "30s", "15m", "1h", "1d"
            'sylius:remove-expired-carts': '1d'
            'messenger:consume': '5m'
        warning_beats: 2                         # missed rhythms after which a command warns
        critical_beats: 4                        # and after which it is critical
```

The rhythm is declared in configuration, never by an attribute on the command, so that a command of another plugin can be watched without touching it. Names and rhythms are validated when the container is compiled.

**Recording.** A `ConsoleEvents::TERMINATE` listener updates **one row per declared command** in the `tmonier_platform_health_heartbeat` table (first record, last run, last exit code, last success); an undeclared command is never recorded. The row is written through DBAL at the end of the command, never through the entity manager: nothing the command left unflushed is written with it. A failure to record goes to the log; **the command keeps its exit code**.

**The table** is the first entity of the plugin. Its migration ships with the plugin (Sylius Labs migrations extra, after the Sylius migrations): run `bin/console doctrine:migrations:migrate` after the update.

| State of a declared command | Status |
|---|---|
| last success within `warning_beats` rhythms | `ok`, with the time of the last success |
| last run failed (non-zero exit code) | `warning` "last run failed", with the code |
| no success for more than `warning_beats` rhythms | `warning` |
| no success for more than `critical_beats` rhythms, or runs that never succeeded | `critical` |
| never run, and the recording started less than `warning_beats` rhythms ago | `unknown` "no heartbeat yet" |
| never run, while the recording started earlier | `warning` |
| table unreadable (migration not run) | `unknown`, the cause in the log only |

The probe reads the worst command as its summary and gives one line per declared command in the detail. It only reads: it never writes during the rendering of the page. The name of the command is shown, never its arguments nor its output.

## The console command

`php bin/console tmonier:health:check` runs the probes from a shell, without opening the administration. It goes through the same runner as the page (same time budget per probe, same masking of what is sensitive) and **never reads the cache shared with the page**: a diagnostic in a console is fresh. It writes nothing.

```bash
php bin/console tmonier:health:check                                        # text, grouped by category
php bin/console tmonier:health:check --format=json                          # JSON for a script
php bin/console tmonier:health:check --category=data --category=shop        # repeatable
php bin/console tmonier:health:check --only=data.connection --only=data.schema_drift
```

The text output has one line per probe (status spelled out, identifier, summary) under the heading of its category, then a summary line. The texts are in the default locale of the application. The JSON output is `{"status": "...", "generated_at": "...", "checks": [{"identifier", "category", "status", "summary"}]}`: raw values (`ok`, `warning`, `critical`, `unknown`; `environment`, `data`, `scheduled_tasks`, `shop`, `plugins`), no detail and no link.

### Exit codes

The code is computed on the probes retained by the filters.

| Retained probes | Code |
|---|---|
| all `ok` | **0** |
| at least one `warning`, no `critical` | **1** |
| at least one `unknown`, no `critical` (a probe that does not measure does not pass a CI gate in silence) | **1** |
| at least one `critical`, whatever the others | **2** |
| invalid option: unknown `--category`, `--only` that designates no probe, unknown `--format` | **3**, with a message that names the refused value |
| no probe after valid filters (a known category without any probe) | **0**, with the message "No check matches the selection." |

3 is distinct from 2 on purpose: a typo in a CI job must not read as "critical". A probe that throws is `unknown` (so 1): the exception goes to the application log, never to the output.

### As a gate in CI after a deployment

```bash
# any non-zero code fails the job; 1 (warning, unknown) can be tolerated explicitly
php bin/console tmonier:health:check --format=json > health.json || [ $? -eq 1 ]
```

To fail only on `critical`, tolerate 1 as above. To fail on anything that is not `ok`, drop the `|| [ $? -eq 1 ]`.

### As a cron

```cron
*/15 * * * * cd /var/www/shop && php bin/console tmonier:health:check --category=data --category=scheduled_tasks > /dev/null || echo "Platform health: code $?" | mail -s "Platform health" ops@example.com
```

### From the console of the host

On a host that only gives you a shell, for example Railway:

```bash
railway ssh
php bin/console tmonier:health:check
```

## The contract

```php
interface HealthCheckInterface
{
    public function identifier(): string;        // 'messenger.failed_transport'
    public function category(): HealthCategory;  // environment, data, scheduled_tasks, shop, plugins
    public function run(): HealthResult;
}
```

- `HealthStatus`: `ok`, `warning`, `critical`, `unknown`.
- `HealthResult`: a status, a one-line translatable summary, an optional translatable detail, an optional action link (`HealthActionLink`: a route name and its parameters, never a hard-coded URL).
- Discovery by **autoconfigured tag**: a service whose class implements `HealthCheckInterface` is registered without a line of configuration.
- The contract and the `HealthCheckRunner` depend on no Sylius class (Symfony and PSR only): a probe is testable without a Sylius kernel.

## Your own probe in fifteen lines

```php
final class PendingImportsCheck implements HealthCheckInterface
{
    public function __construct(private readonly ImportRepository $imports) {}

    public function identifier(): string { return 'imports.pending'; }

    public function category(): HealthCategory { return HealthCategory::Data; }

    public function run(): HealthResult
    {
        $pending = $this->imports->countPending();

        return $pending > 100
            ? HealthResult::warning(new TranslatableMessage('app.health.imports.backlog', ['%count%' => $pending]), action: new HealthActionLink('app_admin_import_index'))
            : HealthResult::ok(new TranslatableMessage('app.health.imports.ok'));
    }
}
```

Declare it as a service with autoconfiguration (the default of a Symfony application): that is all.

## Provide a probe from your plugin

A plugin such as a webhook outbox can ship its own probe **without making the health plugin a dependency**: a shop that installs the webhooks plugin gets no health screen it did not ask for, and the probe shows up as soon as the health plugin is added. The pattern has four parts.

**1. `suggest` and `require-dev`, never `require`.** The health plugin is suggested to the integrator and installed for your own tests only:

```json
{
    "require": {
        "php": "^8.2",
        "sylius/sylius": "^2.2.9 || ^2.3"
    },
    "require-dev": {
        "tmonier/sylius-platform-health-plugin": "^0.5"
    },
    "suggest": {
        "tmonier/sylius-platform-health-plugin": "Shows the health probe of this plugin on the administration page and dashboard"
    }
}
```

**2. The probes in a services file of their own**, `config/health_checks.php`, apart from the other services of the plugin. Autoconfiguration gives them their tag, never tag them by hand:

```php
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('acme_webhooks.health.outbox', OutboxCheck::class)
        ->autoconfigure()
    ;
};
```

**3. An extension that loads that file only if the interface exists.** `HealthCheckInterface::class` is a plain string, it does not load the interface: without the health plugin the probe class (which implements it) is never autoloaded, so there is no fatal error on an absent interface. The check is a `\Closure` passed to the constructor, `interface_exists(...)` by default, so that a test can answer "absent":

```php
final class AcmeWebhooksExtension extends Extension
{
    /** @var \Closure(string): bool */
    private readonly \Closure $interfaceExists;

    /**
     * @param (\Closure(string): bool)|null $interfaceExists
     */
    public function __construct(?\Closure $interfaceExists = null)
    {
        $this->interfaceExists = $interfaceExists ?? interface_exists(...);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config')))->load('services.php');

        if (($this->interfaceExists)(HealthCheckInterface::class)) {
            (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config')))->load('health_checks.php');
        }
    }
}
```

The bundle takes the same optional closure and hands it to its extension (the application builds it without argument, a test passes `static fn (string $interface): bool => false` to play a shop without the health plugin):

```php
final class AcmeWebhooksBundle extends Bundle
{
    /**
     * @param (\Closure(string): bool)|null $interfaceExists
     */
    public function __construct(private readonly ?\Closure $interfaceExists = null)
    {
    }

    protected function createContainerExtension(): ExtensionInterface
    {
        return new AcmeWebhooksExtension($this->interfaceExists);
    }
}
```


**4. The probe**, as in the section above (`identifier()`, `category()`, `run()`), in the `plugins` category or the one that fits. A probe of yours that throws is a probe like any other: `unknown`, the cause logged, the other probes still answer.

Without any probe, a category is **hidden**: neither on the page nor in the dashboard panel, never shown empty or in error.

The repository applies the pattern in its test application: the demonstration plugin in `tests/Fixture/DemoWebhooks` (bundle, extension, `config/health_checks.php`, a `composer.json`), and `tests/Fixture/WithoutHealthPluginKernel.php`, a kernel that builds it with the check answering "absent".

## What the runner guarantees

- **An exception never reaches the caller.** Any `\Throwable` is logged with its trace (PSR-3 `exception` context) and the probe answers `unknown` with a generic summary: neither the trace nor the raw message is rendered.
- **Time budget per probe**, 500 ms by default (`tmonier_sylius_platform_health.time_budget_ms`). **Reserve:** PHP is synchronous, the runner cannot interrupt a probe that is running. It measures the duration *after* the probe returns and, over budget, replaces the result with `unknown` ("time budget exceeded"). The other probes still answer. A probe that does I/O must therefore apply its own timeout (connection timeout, HTTP client `timeout`, bounded query).
- **Unique identifiers, checked at container compilation** (so at `cache:warmup`, in CI). Two probes with the same identifier fail the compilation, naming the identifier and both classes. For that, `identifier()` must return a constant and not depend on the constructor.
- **Read-only.** A probe writes nothing and shows no secret.

## What the plugin is not

- **Not an APM**: no agent, no trace, no profile. Use Sentry or Blackfire for that.
- **Not a log aggregator** nor a continuous metrics collector: it measures at the moment you look.
- **Not a security scanner**: it will read a `composer audit` report produced elsewhere, it does not scan.
- **No outgoing telemetry**: by default no probe calls the outside world.

## Installation and tests

```bash
composer require tmonier/sylius-platform-health-plugin
```

Register `Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin` in `config/bundles.php` and import the routes (see above). Every development command runs in Docker (`make install`, `make phpstan`, `make ecs`, `make rector`, `make unit`, `make non-unit`); the database is the MySQL of `sylius-b2b-demo`, in a database named after the plugin.

## B2B demo

This plugin is part of the work shown in the [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo): a Sylius 2 shop with fictitious sample data where the B2B plugins work together. The demo installs it from v0.2.0: its public administrator accounts hold `ROLE_PLATFORM_HEALTH_VIEW`, so the page shows the probes of the real demo image.

- Online demo: [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## License

MIT.
