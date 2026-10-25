import { expect, test } from '@playwright/test';

import { BASE, MODE, collectPageErrors, flushCache, follow, loginAdmin, readAlternates, shot, shotAlternates } from './helpers.js';

/**
 * Hreflang links expected on the pages of the main website, calculated by hand from the scenario (see README.md):
 * default en-US, pt pt-BR, es es-ES; fr (fr-FR) is a disabled store view and never appears; x-default is the default
 * store view. `path` is the page identifier, empty for the CMS home page.
 */
const mainWebsite = (path) => [
  ['en-US', `${BASE.default}${path}`],
  ['pt-BR', `${BASE.pt}${path}`],
  ['es-ES', `${BASE.es}${path}`],
  ['x-default', `${BASE.default}${path}`],
];

const withoutAlternates = async (page, url) => {
  const { status, alternates } = await readAlternates(page, url);
  expect({ status, alternates }, url).toEqual({ status: 404, alternates: [] });
};

test.describe(`"Add Store Code to Urls" = ${MODE}`, () => {
  test.describe('page for All Store Views', () => {
    for (const store of ['default', 'pt', 'es']) {
      test(`lists the three store views and x-default, seen from the ${store} store view`, async ({ page }) => {
        const errors = collectPageErrors(page);
        const { status, alternates } = await readAlternates(page, `${BASE[store]}about-us`);

        expect(status).toBe(200);
        expect(alternates).toEqual(mainWebsite('about-us'));
        expect(errors).toEqual([]);
      });
    }

    test('lists only its own website by default: site B has its own set', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.b_pt}about-us`);

      expect(alternates).toEqual([
        ['en-US', `${BASE.b_en}about-us`],
        ['pt-PT', `${BASE.b_pt}about-us`],
        ['x-default', `${BASE.b_en}about-us`],
      ]);
    });

    test('every alternate answers 200 and lists the same set (reciprocal links)', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.pt}about-us`);
      const answers = [];
      for (const [, href] of alternates) {
        answers.push(await follow(page.context(), href));
      }

      expect(answers.map(({ status }) => status)).toEqual([200, 200, 200, 200]);
      for (const [, href] of alternates) {
        expect((await readAlternates(page, href)).alternates, href).toEqual(alternates);
      }
      await shotAlternates(page, '01-about-us-all-store-views', `${BASE.pt}about-us`, alternates, answers);
    });

    test('never repeats a hreflang value and has exactly one x-default', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.default}about-us`);
      const values = alternates.map(([hreflang]) => hreflang);

      expect(new Set(values).size).toBe(values.length);
      expect(values.filter((value) => value === 'x-default')).toHaveLength(1);
    });
  });

  test.describe('page assigned to some store views', () => {
    for (const store of ['default', 'pt']) {
      test(`partial: lists the two store views that have it, seen from the ${store} store view`, async ({ page }) => {
        const { alternates } = await readAlternates(page, `${BASE[store]}partial`);

        expect(alternates).toEqual([
          ['en-US', `${BASE.default}partial`],
          ['pt-BR', `${BASE.pt}partial`],
          ['x-default', `${BASE.default}partial`],
        ]);
      });
    }

    test('partial: the store view without the page answers 404 and lists nothing', async ({ page }) => {
      await withoutAlternates(page, `${BASE.es}partial`);
    });

    test('pt-only: only its own store view, and no x-default because the default store view does not have it', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.pt}pt-only`);

      expect(alternates).toEqual([['pt-BR', `${BASE.pt}pt-only`]]);
      await withoutAlternates(page, `${BASE.default}pt-only`);
    });

    test('a disabled page answers 404 and lists nothing', async ({ page }) => {
      await withoutAlternates(page, `${BASE.default}inactive-page`);
    });

    test('a page of site B is not found on the main website, and the two store views of B list each other', async ({ page }) => {
      await withoutAlternates(page, `${BASE.default}only-b`);
      const { alternates } = await readAlternates(page, `${BASE.b_en}only-b`);

      expect(alternates).toEqual([
        ['en-US', `${BASE.b_en}only-b`],
        ['pt-PT', `${BASE.b_pt}only-b`],
        ['x-default', `${BASE.b_en}only-b`],
      ]);
    });
  });

  test.describe('one page per language with the same identifier (contact-us)', () => {
    const TITLES = { default: 'Contact us (EN)', pt: 'Fale conosco (PT)', es: 'Contáctenos (ES)' };

    for (const store of ['default', 'pt', 'es']) {
      test(`${store}: lists the pages of the three languages, itself included`, async ({ page }) => {
        const { status, title, alternates } = await readAlternates(page, `${BASE[store]}contact-us`);

        expect(status).toBe(200);
        expect(title).toBe(TITLES[store]);
        expect(alternates).toEqual(mainWebsite('contact-us'));
      });
    }

    test('each alternate is the page of its own language', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.pt}contact-us`);
      const answers = [];
      for (const [, href] of alternates) {
        answers.push(await follow(page.context(), href));
      }

      expect(answers.map(({ title }) => title)).toEqual([TITLES.default, TITLES.pt, TITLES.es, TITLES.default]);
      await shotAlternates(page, '02-contact-us-one-page-per-language', `${BASE.pt}contact-us`, alternates, answers);
    });
  });

  test.describe('CMS home page', () => {
    for (const store of ['default', 'pt', 'es']) {
      test(`the base URL of the ${store} store view lists the home page of every store view`, async ({ page }) => {
        const { status, alternates } = await readAlternates(page, BASE[store]);

        expect(status).toBe(200);
        expect(alternates).toEqual(mainWebsite(''));
      });
    }

    test('every home alternate answers 200 and lists the same set', async ({ page }) => {
      const { alternates } = await readAlternates(page, BASE.pt);
      const answers = [];
      for (const [, href] of alternates) {
        answers.push(await follow(page.context(), href));
      }

      expect(answers.map(({ status }) => status)).toEqual([200, 200, 200, 200]);
      for (const [, href] of alternates) {
        expect((await readAlternates(page, href)).alternates, href).toEqual(alternates);
      }
      await shotAlternates(page, '03-cms-home-page', BASE.pt, alternates, answers);
    });

    test('the same page at /home is an ordinary page: its own URLs, listed on itself', async ({ page }) => {
      const { alternates } = await readAlternates(page, `${BASE.pt}home`);

      expect(alternates).toEqual(mainWebsite('home'));
    });

    test('no JavaScript error on the home page', async ({ page }) => {
      const errors = collectPageErrors(page);
      await page.goto(BASE.default, { waitUntil: 'networkidle' });

      expect(errors).toEqual([]);
    });
  });
});

test.describe('admin: include store views of other websites', () => {
  const FIELD = 'select[name="groups[general][fields][include_other_websites][value]"]';
  const INHERIT = 'input[name="groups[general][fields][include_other_websites][inherit]"]';

  test.skip(!process.env.QA_ADMIN_USER || !process.env.QA_ADMIN_PASSWORD, 'set QA_ADMIN_USER and QA_ADMIN_PASSWORD');

  /** Save the setting of website 1 (the main website) and flush the cache, as a merchant would. */
  async function saveSetting(page, key, value) {
    await page.goto(`/admin/admin/system_config/edit/section/roni_cms_hreflang/website/1/key/${key}/`);
    if (await page.locator(INHERIT).isChecked()) {
      await page.locator(INHERIT).uncheck();
    }
    await page.locator(FIELD).selectOption(value);
    await page.locator('#save').click();
    await expect(page.locator('.message-success')).toBeVisible();
    flushCache();
  }

  test('is off by default; turned on, the other website is listed once per locale after the main one', async ({ page, browser }) => {
    test.skip(MODE !== 'off', 'the setting does not depend on the URL mode; run it once');
    await page.setViewportSize({ width: 1500, height: 900 });
    const key = await loginAdmin(page);

    await page.goto(`/admin/admin/system_config/edit/section/roni_cms_hreflang/website/1/key/${key}/`);
    await expect(page.locator(FIELD)).toHaveValue('0');
    await shot(page, '04-admin-configuration', page.locator('#roni_cms_hreflang_general'));

    try {
      await saveSetting(page, key, '1');
      const visitor = await (await browser.newContext()).newPage();
      const { alternates } = await readAlternates(visitor, `${BASE.default}about-us`);

      // en-US and pt-BR of site B repeat locales of the main website and are left out; pt-PT is new
      expect(alternates).toEqual([
        ['en-US', `${BASE.default}about-us`],
        ['pt-BR', `${BASE.pt}about-us`],
        ['es-ES', `${BASE.es}about-us`],
        ['pt-PT', `${BASE.b_pt}about-us`],
        ['x-default', `${BASE.default}about-us`],
      ]);
    } finally {
      await saveSetting(page, key, '0');
    }
    const { alternates } = await readAlternates(await (await browser.newContext()).newPage(), `${BASE.default}about-us`);
    expect(alternates).toEqual(mainWebsite('about-us'));
  });
});
