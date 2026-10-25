# CLAUDE.md — Roni_CmsHreflang

Magento 2 / Adobe Commerce module (`roni/module-cms-hreflang`, MIT). It adds `<link rel="alternate" hreflang>` links (and
`x-default`) to CMS pages and to the CMS home page, one per active store view that serves the page.
Declared for Magento 2.4.7+ (`magento/framework ^103.0.7`) and PHP 8.2 – 8.3.

## Layout

| Path | What |
|---|---|
| `Api/HreflangProviderInterface.php`, `Api/Data/AlternateInterface.php` | Service contract (`@api`); wired by a `<preference>` in `etc/di.xml` |
| `Model/HreflangProvider.php` | The calculation: one alternate per hreflang value, then x-default |
| `Model/StoreSelector.php` | Which store views may be listed (active, current website, other websites on request) and in what order |
| `Model/PageResolver.php` | The CMS page that answers at an identifier in each store view (repository, one query per identifier) |
| `Model/Config.php` | Reads the configuration; **always passes the scope** |
| `Model/Alternate.php` | Immutable value object |
| `ViewModel/Hreflang.php` | Data for the template; tells a page, the CMS home page and the 404 page apart by the full action name |
| `Block/Hreflang.php` | No logic: hands the cache tags (`cms_p_<id>`) of the ViewModel to the full page cache |
| `view/frontend/templates/hreflang.phtml` | Markup only |
| `view/frontend/layout/cms_page_view.xml` | One block in `head.additional`; the CMS home page and the 404 page add this handle too |
| `etc/adminhtml/system.xml` | "Include store views of other websites" (website scope, default No) |
| `Test/Unit` | PHPUnit, mirrors the structure (`Foo/Bar.php` → `Test/Unit/Foo/BarTest.php`) |
| `e2e/` | Playwright tests and the scenario (`e2e/scenario/`) they run against |
| `dev/ci`, `dev/phpmd` | CI checks (PHP scripts) and the vendored Magento PHPMD rule set |

## Rules of the calculation

- URLs come from `Store::getBaseUrl()`, which already contains the store code when "Add Store Code to Urls" is on: never add the
  code again. The CMS home page is the base URL; any other page is the base URL plus its identifier.
- Only **active** store views. One entry per hreflang value: the current website first, then by website, sort order and id.
- `x-default` is the default store view of the **current website**, and only when it serves the page.
- Every store view looks the page up by identifier (a page for the store view wins over one for All Store Views), so pages
  that share an identifier across languages link to each other. Pages with different identifiers are not linked (see README).
- The other websites are listed only when the website setting asks for it.

## Commands

```bash
./setup-dev.sh                # enables the git hooks (core.hooksPath = .githooks)
composer check                # blacklist, phpcs, phpmd, phpcpd, phpstan, tests-exist, multi-store, coverage
composer test                 # PHPUnit only
composer coverage             # PHPUnit with xdebug + the 70% per-file gate (prints the percentage of every file)
composer phpcs | phpmd | phpcpd | phpstan | blacklist | check-tests-exist | check-scope
```

The dependencies come from a public mirror of the original Magento packages, so no `repo.magento.com` keys are needed:
`composer config -g repositories.magento-mirror composer https://mirror.mage-os.org/`.

The git hooks (`.githooks`) run the PHP tools **inside the Docker container** of the Magento dev environment (PHP 8.3 + xdebug)
through `dev/ci/in-container.sh` (`QA_PHP_CONTAINER`, default `m2-phpfpm-1`) and skip them with a warning when the container is not
running.

## Quality rules (enforced in CI and by the git hooks)

- Blacklist: no `var_dump(`, `print_r(`, `console.log(`, `die`, `exit`, `eval(`, `phpinfo`, `ObjectManager::getInstance`,
  `DebuggerUtility`, `localhost`, `$_GET[`/`$_POST[`/`$_REQUEST[`, merge markers in php/js/phtml/html (not in `Test/`, `e2e/`).
- phpcs Magento2 on php + phtml with **warnings blocking** (`warning-severity` 1). Documented exclusions: the BEM `__` class name
  sniff (kept for consistency with the other modules) and `dev/ci` + `dev/phpmd` (tooling / vendored code).
- phpmd with the Magento rule set (`dev/phpmd/ruleset.xml`), phpcpd (min 5 lines / 70 tokens; the phar is checked against a pinned
  SHA-256 because it cannot be installed with Composer next to PHPUnit 10), phpstan level 9, no baseline.
- PHPUnit with coverage of at least 70% **per file**; every production PHP file (except `registration.php`, `Api/`,
  interfaces) has its `Test/Unit` counterpart; every `scopeConfig->getValue()/isSetFlag()` passes a scope.
- No logic in blocks or templates; no `ObjectManager`; no direct SQL when a core API does the job.
- `composer validate --strict` passes.

## End-to-end tests

They need a Magento store with the module installed and the scenario seeded (see `e2e/README.md`), and run with
"Add Store Code to Urls" on **and** off. `QA_BROWSER` can point to a Chromium-based browser you already have, so no browser
download is needed. The expected links are written by hand; never derive them from the module's output.
