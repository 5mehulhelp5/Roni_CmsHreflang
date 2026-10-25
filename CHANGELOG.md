# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-10-25

Rebuilt release. 1.0.0 emitted links that answered 404 as soon as "Add Store Code to Urls" was on, left the CMS home page
without any link, repeated locales across websites, listed disabled store views and linked pages that share an identifier
across languages to the wrong page. 2.0.0 fixes all of that, and brings the module to the structure, tests and checks of a
Magento 2 module: a service contract, a ViewModel, a template, unit tests with 100% line coverage, static analysis and
end-to-end tests in a real browser with the store code in the URL on and off.

### Breaking changes

**What the module emits**

- The URLs change: 1.0.0 added the store code a second time when "Add Store Code to Urls" is on
  (`/pt/pt/about-us`, a 404). 2.0.0 emits `/pt/about-us`. Nothing else than 1.0.0's broken links is replaced, but caches,
  sitemaps or tests that expected the doubled code must follow.
- The set of links changes. Only the store views of the **current website** are listed (1.0.0 listed every website);
  the other websites need the new setting "Include store views of other websites". Disabled store views are never listed,
  and each hreflang value appears once.
- `x-default` is now emitted (the default store view of the current website).
- The CMS home page now gets links (1.0.0 emitted none on `/`).
- A page is looked up by its identifier in every store view: pages that share an identifier across languages now link to
  each other (1.0.0 linked a page of another language to the page of the default store view).

**PHP and layout**

- `Roni\CmsHreflang\Block\Hreflang` no longer builds the links and its constructor changed: it extends
  `Magento\Framework\View\Element\Template` and only returns cache tags. The logic moved to
  `Roni\CmsHreflang\Api\HreflangProviderInterface` (implemented by `Model\HreflangProvider`, wired by a preference), used by
  `Roni\CmsHreflang\ViewModel\Hreflang`. Code that extended or instantiated the 1.0.0 block, or called its methods, must use the
  provider.
- The layout block was renamed from `cms.hreflang` to `roni.cms.hreflang` and renders `Roni_CmsHreflang::hreflang.phtml`
  (the handle is still `cms_page_view`, the container still `head.additional`). A layout update that referenced the old name
  must follow.
- The block reads no configuration, SQL or repositories any more: the direct query on `cms_page_store` was removed in favour of
  the CMS page repository.

**Requirements**

- PHP **8.2 – 8.3** (1.0.0 declared `^7.4 || ^8.1`; 7.4 cannot run Magento 2.4.7+) and Magento Open Source / Adobe Commerce
  **2.4.7+** (`magento/framework ^103.0.7`). `magento/module-cms`, `module-store`, `module-config` and `module-backend` are now
  required with real version constraints (1.0.0 required `magento/framework: *`).
- `setup_version` was removed from `etc/module.xml` (deprecated) and the module declares its `<sequence>`
  (`Magento_Cms`, `Magento_Config`, `Magento_Store`).
- The hard-coded `"version"` was removed from `composer.json`: the version is the git tag.

### Added

- Setting `Stores > Configuration > Roni > CMS Hreflang > General > Include store views of other websites` (website scope,
  default No): list the store views of the other websites too, one tag per locale, the current website first.
- `x-default`, and links on the CMS home page (the base URL of each store view).
- Cache tags of the pages behind the links (`cms_p_<id>`), so editing any of them refreshes the cached page.
- Unit tests (100% line coverage), phpcs (Magento2, warnings block), phpmd (Magento rule set), phpcpd, phpstan level 9,
  conventions checks, git hooks that run the PHP tools in the Docker container, CI on PHP 8.2 and 8.3, and Playwright
  end-to-end tests with the scenario they need (`e2e/`).
- English and Brazilian Portuguese translations of the admin labels.
- MIT licence, `CLAUDE.md`, code review guide and pull request template.

### Fixed

- The store code was added twice to every URL when "Add Store Code to Urls" was on; every link answered 404.
- The CMS home page had no links; `/home` got URLs that are not the home page.
- A disabled store view was listed (its link answered an error).
- The same locale was listed more than once when two websites share it.
- The page of another language that shares the identifier was linked to the wrong page, and a page did not reference itself.
- The 404 page and other non-page requests never get links.

## [1.0.0]

First version: hreflang links for CMS pages shared across store views. It started as a technical challenge of a hiring
process.
