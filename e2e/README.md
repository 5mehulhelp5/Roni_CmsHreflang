# End-to-end tests

Playwright tests that run against a real Magento store with the module installed and the scenario of `scenario/` seeded. They
are not part of the CI workflow: they need a store. They run twice, once with "Add Store Code to Urls" on and once off.

```bash
npm i -D @playwright/test           # or use an existing install; browsers: `npx playwright install chromium`,
                                    # or point QA_BROWSER at a Chromium-based browser you already have
QA_URL_MODE=off QA_BASE_URL=http://localhost QA_ADMIN_USER=... QA_ADMIN_PASSWORD=... \
QA_FLUSH_COMMAND="docker exec m2-phpfpm-1 bin/magento cache:flush" \
  npx playwright test --config e2e/playwright.config.js
```

| Variable | Meaning |
|---|---|
| `QA_URL_MODE` | `on` or `off`: the "Add Store Code to Urls" setting the store runs with (default `off`) |
| `QA_BASE_URL` | Store URL used for the admin (default `http://localhost`) |
| `QA_BROWSER` | Browser executable; unset = Playwright's own Chromium |
| `QA_ADMIN_USER`, `QA_ADMIN_PASSWORD` | Admin login, for the configuration test (it is skipped without them) |
| `QA_FLUSH_COMMAND` | Command that flushes the Magento cache (the full page cache is not cleared by a configuration save) |

## The scenario

`scenario/` builds the store the tests expect:

1. `php scenario/seed-stores.php` (inside the php-fpm container) creates the second website `b_site`, the store views
   `pt`, `es`, `fr` (disabled), `b_en`, `b_pt` and the CMS pages: `about-us` (All Store Views, from the sample data),
   `partial` (default + pt), `pt-only`, `inactive-page` (disabled), `only-b` and three pages that share the identifier
   `contact-us`, one per language.
2. `scenario/configure.sh on|off` sets the locales (default en_US, pt pt_BR, es es_ES, fr fr_FR, b_en en_US, b_pt pt_PT)
   and the base URLs. With `off` every store view has its own host (`pt.localhost`, `es.localhost`, `hreflang-b.localhost`,
   `b-pt.localhost`); with `on` they share the host of their website (`http://localhost/pt/`, ...).
3. The hosts must reach the store view they name. `scenario/nginx-hosts.conf` maps the host to `MAGE_RUN_CODE` and
   `MAGE_RUN_TYPE`; add it to nginx and add `fastcgi_param MAGE_RUN_CODE $MAGE_RUN_CODE;` and
   `fastcgi_param MAGE_RUN_TYPE $MAGE_RUN_TYPE;` to `fastcgi_params`. `*.localhost` resolves to the loopback in
   Chromium based browsers.

The expected links are fixed in `hreflang.spec.js` and `helpers.js` (`BASE`), written by hand for that scenario and never
built from the module's output. The screenshots of the README come from `test-results/screenshots/<mode>/`.
