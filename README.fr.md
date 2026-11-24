# Sylius Platform Health Plugin

> English: [README.md](README.md)

[![Build](https://github.com/thibmonier/sylius-platform-health-plugin/actions/workflows/build.yaml/badge.svg)](https://github.com/thibmonier/sylius-platform-health-plugin/actions/workflows/build.yaml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Sylius 2 : un contrat de sonde que l'on implémente en quelques lignes, et un exécuteur qu'une sonde fautive ne peut pas casser.**

## État

Le plugin livre le **contrat, l'exécuteur, la page d'administration, un encart du tableau de bord et six sondes d'environnement** (versions et fin de support de PHP, Symfony et Sylius, extensions PHP, OPcache, mode debug), ainsi qu'un rôle d'accès dédié et facultatif (`ROLE_PLATFORM_HEALTH_VIEW`). Les captures ci-dessous viennent de l'application de test : sa page liste aussi cinq sondes de démonstration qui vivent dans `tests/`, pas dans le paquet. Langues : français et anglais.

## La page d'administration

*Configuration > Santé de la plateforme* (`/admin/platform-health`, gardée par l'attribut `PLATFORM_HEALTH_VIEW`, voir [Contrôle d'accès](#contrôle-daccès)).

![La page Santé de la plateforme, thème clair : une carte par catégorie, la plus grave en premier](docs/screenshots/platform-health-fr-light.png)

![La même page en thème sombre](docs/screenshots/platform-health-fr-dark.png)

La page en anglais : [clair](docs/screenshots/platform-health-en-light.png), [sombre](docs/screenshots/platform-health-en-dark.png).

- **Synthèse par catégorie.** Chaque catégorie prend le pire statut de ses sondes (`critical`, `warning`, `unknown`, `ok`) : la catégorie qui contient une sonde critique passe en premier. Une sonde `unknown` s'affiche « Non mesurable » avec son motif court ; elle ne compte jamais comme critique, et ne laisse jamais une catégorie « OK ».
- **Détail par sonde, replié par défaut** : statut (toujours écrit en toutes lettres, jamais porté par la seule couleur), résumé, détail et **lien d'action** de la sonde, construit depuis le nom de route et les paramètres de son `HealthActionLink`. Une sonde qui pointe vers une route inexistante n'a pas de lien, pas d'erreur.
- **Rendu différé** (Live Component Symfony UX, `loading="defer"`) : la page répond tout de suite avec un squelette, les résultats arrivent juste après. Aucune sonde ne s'exécute pendant la construction du squelette.
- **Cache court mutualisé** : les résultats sont conservés `cache_ttl` secondes (60 par défaut, `0` désactive le cache) : trois administrateurs devant la page n'exécutent les sondes qu'une fois. Le bouton **Rafraîchir** les exécute à nouveau et remplace le cache.
- **Aucun contrôle installé** : la page le dit et renvoie à cette documentation, elle n'est ni vide ni en erreur.
- Une sonde défaillante ne casse jamais la page et le message de son exception n'est jamais affiché (il va au journal de l'application).

```yaml
# config/routes/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    resource: "@TmonierSyliusPlatformHealthPlugin/config/routes.yaml"
```

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    time_budget_ms: 500   # par sonde
    cache_ttl: 60         # secondes mutualisées entre administrateurs, 0 désactive
    access:
        role: ROLE_ADMINISTRATION_ACCESS   # ROLE_PLATFORM_HEALTH_VIEW recommandé en production
```

L'entrée de menu et les hooks de la page sont enregistrés par le plugin lui-même, rien d'autre à importer.

## L'encart du tableau de bord

La première page après la connexion répond à « tout va bien ? » sans un clic : un encart compact **Santé de la plateforme** s'affiche sur le tableau de bord de l'administration, entre les statistiques et les dernières commandes.

- **Une ligne par catégorie**, la plus grave en premier, avec son pire statut **écrit en toutes lettres** (« OK », « Attention », « Critique », « Non mesurable ») et une icône propre : le statut n'est jamais porté par la seule couleur. Mêmes règles et mêmes jetons de couleur que la page, thèmes clair et sombre.
- **Le titre le dit** quand quelque chose est critique : « 1 catégorie critique », « 2 catégories critiques ». Sinon il affiche « Santé de la plateforme ». Un lien « Voir le détail » ouvre la page.
- **Le tableau de bord n'attend jamais une sonde.** L'encart est un Live Component différé qui lit le **même cache que la page** (`cache_ttl`) : le tableau de bord répond avec un squelette, puis l'encart se complète. Cache chaud : aucune sonde ne s'exécute ; cache froid : la première lecture alimente le cache de la page, après le rendu du tableau de bord.
- **Même droit que la page** : l'encart n'est rendu que pour un administrateur qui passe `PLATFORM_HEALTH_VIEW` (voir [Contrôle d'accès](#contrôle-daccès)), et l'URL de son composant répond 403 aux autres.
- **Un encart qui échoue disparaît sans bruit** : l'exception est journalisée avec sa trace, rien n'est affiché, le tableau de bord reste intact.
- Déclaré par le plugin via un Twig hook (`sylius_admin.dashboard.index.content`, dans `config/twig_hooks/admin.yaml`) : aucun gabarit de Sylius n'est surchargé, rien à configurer. Pour le retirer, mettez `enabled: false` sur l'entrée `platform_health` de ce hook dans votre propre configuration `sylius_twig_hooks`.

## Contrôle d'accès

**Par défaut, tout administrateur voit la page** (`access.role` vaut `ROLE_ADMINISTRATION_ACCESS`, le comportement de la v0.1.0 : une mise à jour ne casse rien). Un écran de diagnostic est une carte de l'installation (versions, chemins, états des files) : **en production, réglez `access.role` sur `ROLE_PLATFORM_HEALTH_VIEW`** et accordez-le administrateur par administrateur, avec la commande de Sylius lui-même :

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    access:
        role: ROLE_PLATFORM_HEALTH_VIEW
```

```bash
bin/console sylius:user:promote admin@example.com ROLE_PLATFORM_HEALTH_VIEW --user-type=admin
```

L'administrateur se reconnecte pour porter le nouveau rôle. Le plugin ne fournit ni commande d'attribution ni `role_hierarchy` : il ne réécrit pas la sécurité de votre application (un rôle déclaré dans votre propre `role_hierarchy` est respecté). Une valeur qui n'est pas un rôle (elle doit commencer par `ROLE_`) fait échouer la compilation du conteneur.

Le garde est un *voter* de l'attribut `PLATFORM_HEALTH_VIEW`, posé sur la route de la page **et** sur la classe du Live Component (`#[IsGranted]`) : le composant est aussi servi sous `/admin/{_locale}/_components/...`, que l'administration ne protège qu'avec `ROLE_ADMINISTRATION_ACCESS`. Un administrateur sans le rôle reçoit un **403** sur la page, sur l'URL du composant et sur l'action de rafraîchissement, et l'entrée de menu est masquée.

### Valeurs sensibles et traces d'exécution

Les sondes sont écrites par des tiers : la page masque ce qu'une sonde laisse échapper, dans chaque résumé et détail (une sonde ne doit pourtant jamais produire de valeur sensible) :

| Trouvé dans un résumé ou un détail | Affiché |
|------------------------------------|---------|
| Identifiants d'un DSN (`mysql://shop:pass@db/sylius`) | `mysql://***@db/sylius` |
| Paires `secret`, `password`, `passwd`, `pwd`, `token`, `key`, `dsn` (`password=hunter2`, `API_KEY: abc`) | `password=***`, `API_KEY: ***` |
| Clé de 32 caractères ou plus contenant un chiffre | `***` |
| Chemin absolu dans le projet | chemin relatif au projet |
| Autre chemin absolu de fichier | `.../fichier.ext` |
| Trace d'exécution (`Stack trace:`, `#0 /chemin/File.php(12): ...`) | « Le détail a été masqué : voir le journal de l'application. », et la sonde est journalisée par son identifiant (jamais la trace) |

## Accessibilité

La page vise **WCAG 2.2 niveau AA** (la cible du thème de l'administration, pas AAA).

- **Un statut n'est jamais porté par la seule couleur** : chaque catégorie et chaque sonde l'écrit en toutes lettres (« OK », « Avertissement », « Critique », « Non mesurable ») à côté d'une icône dont la *forme* diffère selon le statut (coche, triangle, octogone, point d'interrogation). Les icônes sont décoratives (`aria-hidden`), le mot porte le sens.
- **Le détail est un vrai tableau** : `<caption>`, en-têtes `scope="col"`, un en-tête `scope="row"` par sonde, dans un `<details>`/`<summary>` natif (clavier et lecteur d'écran sans rien ajouter). Un lien d'action se lit « Corriger : <résumé de la sonde> », jamais « cliquer ici ».
- **Le rafraîchissement est annoncé** : une région `role="status"` (`aria-live="polite"`) dit « Résultats mis à jour » avec l'heure de la mesure, le focus reste sur le bouton **Rafraîchir**, et les résultats portent `aria-busy="true"` pendant le calcul (posé par le petit script de l'entrypoint admin du plugin). Le squelette de chargement respecte `prefers-reduced-motion`.
- **Thèmes clair et sombre** : les couleurs sont des variables CSS (`--tph-*`) qui suivent `data-bs-theme` de l'administration (Tabler), déclarées une fois dans `templates/admin/_tokens.html.twig` ; aucun gabarit ne porte de couleur. Chaque paire (texte sur fond, libellé sur pastille, icône sur pastille, bordure sur fond) est **mesurée** par `tests/Unit/Accessibility/StatusContrastTest.php`, qui échoue en nommant le jeton, le fond et le rapport trouvé quand un texte passe sous 4,5:1 ou une icône ou une bordure sous 3:1, dans les deux thèmes. Un jeton se change avec ce test.

Hors périmètre : AAA, droite à gauche, couleurs forcées au-delà d'un contrôle manuel. Les tests prouvent le balisage et le contraste ; un passage au lecteur d'écran reste un contrôle manuel.

## Sondes d'environnement

Six sondes de la catégorie `environment`, enregistrées par le plugin lui-même (rien à déclarer), en lecture seule, **sans appel réseau**, sans jamais afficher la valeur d'une variable d'environnement. Chaque résultat qui n'est pas `ok` renvoie vers cette documentation (`Corriger`).

| Identifiant | Mesure | Statuts |
|---|---|---|
| `environment.php_version` | Version de PHP et fin du support de sécurité de sa branche | `ok` ; `warning` à moins de 180 jours de la fin ; `critical` le lendemain ; `unknown` pour une branche absente de la table |
| `environment.symfony_version` | `Kernel::END_OF_MAINTENANCE` et `Kernel::END_OF_LIFE`, fournies par Symfony lui-même (pas de table) | mêmes seuils ; `warning` une fois la maintenance terminée |
| `environment.sylius_version` | Version de Sylius et fin de support de sa branche mineure | comme PHP |
| `environment.php_extensions` | `intl`, `mbstring`, `ctype`, `iconv`, `xml`, `curl`, `pdo`, `gd` ou `imagick` | `ok` ; `critical` qui liste **seulement les manquantes** |
| `environment.opcache` | OPcache actif pour le SAPI qui sert la page | `ok` ; `warning` si inactif ; `unknown` si l'API est restreinte (`opcache.restrict_api`) |
| `environment.app_debug` | `kernel.debug` et `kernel.environment` | `ok` en prod sans debug ; `critical` avec debug en prod ; `warning` hors prod |

```yaml
# config/packages/tmonier_sylius_platform_health.yaml
tmonier_sylius_platform_health:
    environment:
        support_warning_days: 180   # les sondes PHP, Symfony et Sylius alertent ce nombre de jours avant la fin du support
```

**La table des fins de support** (`EndOfSupportTable`) est figée dans le code : l'administration n'appelle jamais le réseau. Sa version (`2026-12`) est affichée par les sondes PHP et Sylius (« Table du 2026-12 »). Elle est **renouvelée à chaque version du plugin** ; douze mois après sa version, ces deux sondes passent en `warning` (« table de dates à renouveler ») au lieu d'un faux `ok`. Une branche de PHP ou de Sylius absente de la table est `unknown` (« branche absente de la table »), jamais `ok`. Les sondes lisent la date par le service `clock` de Symfony : les tests injectent une horloge fixe.

## Le contrat

```php
interface HealthCheckInterface
{
    public function identifier(): string;        // 'messenger.failed_transport'
    public function category(): HealthCategory;  // environment, data, scheduled_tasks, shop, plugins
    public function run(): HealthResult;
}
```

- `HealthStatus` : `ok`, `warning`, `critical`, `unknown`.
- `HealthResult` : un statut, un résumé d'une ligne traduisible, un détail traduisible facultatif, un lien d'action facultatif (`HealthActionLink` : un nom de route et ses paramètres, jamais une URL en dur).
- Découverte par **tag autoconfiguré** : un service dont la classe implémente `HealthCheckInterface` est enregistré sans une ligne de configuration.
- Le contrat et le `HealthCheckRunner` ne dépendent d'aucune classe Sylius (Symfony et PSR seulement) : une sonde se teste sans noyau Sylius.

## Votre propre sonde en quinze lignes

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

Déclarez-la comme service avec l'autoconfiguration (le défaut d'une application Symfony) : c'est tout.

## Ce que l'exécuteur garantit

- **Aucune exception ne remonte à l'appelant.** Tout `\Throwable` est journalisé avec sa trace (contexte PSR-3 `exception`) et la sonde répond `unknown` avec un résumé générique : ni la trace ni le message brut ne sont affichés.
- **Budget de temps par sonde**, 500 ms par défaut (`tmonier_sylius_platform_health.time_budget_ms`). **Réserve :** PHP est synchrone, l'exécuteur ne peut pas interrompre une sonde en cours. Il mesure la durée *après* le retour de la sonde et, au-delà du budget, remplace le résultat par `unknown` (« budget de temps dépassé »). Les autres sondes répondent normalement. Une sonde qui fait des entrées-sorties doit donc appliquer son propre délai (délai de connexion, `timeout` du client HTTP, requête bornée).
- **Identifiants uniques, vérifiés à la compilation du conteneur** (donc au `cache:warmup`, en CI). Deux sondes de même identifiant font échouer la compilation, en nommant l'identifiant et les deux classes. Pour cela, `identifier()` doit rendre une constante et ne pas dépendre du constructeur.
- **Lecture seule.** Une sonde n'écrit rien et n'affiche aucun secret.

## Ce que le plugin n'est pas

- **Pas un APM** : ni agent, ni trace, ni profil. Pour cela, Sentry ou Blackfire.
- **Pas un agrégateur de logs** ni un collecteur de métriques continues : il mesure à l'instant où on le regarde.
- **Pas un scanner de sécurité** : il lira un rapport `composer audit` produit ailleurs, il ne scanne pas.
- **Aucune télémétrie sortante** : par défaut, aucune sonde n'appelle l'extérieur.

## Installation et tests

```bash
composer require tmonier/sylius-platform-health-plugin
```

Enregistrez `Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin` dans `config/bundles.php` et importez les routes (voir plus haut). Toutes les commandes de développement passent par Docker (`make install`, `make phpstan`, `make ecs`, `make rector`, `make unit`, `make non-unit`) ; la base est le MySQL de `sylius-b2b-demo`, dans une base au nom du plugin.

## Démo B2B

Ce plugin fait partie du travail présenté dans la [démo B2B Sylius](https://github.com/thibmonier/sylius-b2b-demo) : une boutique Sylius 2 avec des données d'exemple fictives, où les plugins B2B fonctionnent ensemble. La démo l'installe depuis la v0.2.0 : ses comptes administrateur publics ont le rôle `ROLE_PLATFORM_HEALTH_VIEW`, et la page montre les sondes de l'image réelle de la démo.

- Démo en ligne : [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Auteur : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## Licence

MIT.
