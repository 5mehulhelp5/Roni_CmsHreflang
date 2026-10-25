import { execSync } from 'node:child_process';

import { expect } from '@playwright/test';

/** "on" / "off": the "Add Store Code to Urls" setting the store is running with (see scenario/configure.sh). */
export const MODE = process.env.QA_URL_MODE ?? 'off';

/**
 * Base URL of each store view of the scenario, in the two modes. Fixed by hand, never built from the module's output.
 * hreflang values: default en-US, pt pt-BR, es es-ES, b_en en-US, b_pt pt-PT (fr, fr-FR, is disabled).
 */
export const BASE = MODE === 'on'
  ? {
    default: 'http://localhost/default/',
    pt: 'http://localhost/pt/',
    es: 'http://localhost/es/',
    fr: 'http://localhost/fr/',
    b_en: 'http://hreflang-b.localhost/b_en/',
    b_pt: 'http://hreflang-b.localhost/b_pt/',
  }
  : {
    default: 'http://localhost/',
    pt: 'http://pt.localhost/',
    es: 'http://es.localhost/',
    fr: 'http://fr.localhost/',
    b_en: 'http://hreflang-b.localhost/',
    b_pt: 'http://b-pt.localhost/',
  };

/** Screenshot of the page, or of one element when `target` is given. */
export const shot = (page, name, target = page) =>
  target.screenshot({ path: `test-results/screenshots/${MODE}/${name}.png` });

/**
 * Open a URL and read its hreflang links from the HTML the server sent and from the DOM the browser built: they must be
 * the same (the module needs no JavaScript).
 */
export async function readAlternates(page, url) {
  const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
  const html = await response.text();
  const raw = [...html.matchAll(/<link rel="alternate"[^>]*hreflang="([^"]*)"[^>]*href="([^"]*)"/g)]
    .map(([, hreflang, href]) => [hreflang, href]);
  const rendered = await page.$$eval(
    'link[rel="alternate"][hreflang]',
    (links) => links.map((link) => [link.getAttribute('hreflang'), link.getAttribute('href')]),
  );
  expect(rendered, `the rendered links of ${url} differ from the HTML`).toEqual(raw);

  return { status: response.status(), title: await page.title(), alternates: rendered };
}

/** Open an alternate in a fresh page and report what answers there. */
export async function follow(context, url) {
  const page = await context.newPage();
  const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
  const result = { status: response.status(), title: await page.title(), finalUrl: page.url() };
  await page.close();

  return result;
}

/**
 * A picture of the links of a page and of what each one answers, for the README: the browser draws it from data it read
 * from the store, so it shows the real result.
 */
export async function shotAlternates(page, name, url, alternates, answers) {
  const escape = (text) => String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const rows = alternates.map(([hreflang, href], index) => {
    const ok = answers[index].status === 200;
    return `<tr class="${ok ? 'ok' : 'bad'}"><td>${escape(hreflang)}</td><td>${escape(href)}</td>`
      + `<td>${answers[index].status}</td><td>${escape(answers[index].title)}</td></tr>`;
  }).join('');
  const tags = alternates
    .map(([hreflang, href]) => escape(`<link rel="alternate" hreflang="${hreflang}" href="${href}"/>`)).join('\n');
  await page.setContent(`<!doctype html><meta charset="utf-8"><style>
    body{font:14px/1.5 -apple-system,Segoe UI,sans-serif;margin:24px;color:#1d2433}
    h1{font-size:17px;margin:0 0 12px} pre{background:#0f1722;color:#d7e3f4;padding:14px;border-radius:8px;font-size:13px}
    table{border-collapse:collapse;width:100%} td,th{border:1px solid #d5dbe5;padding:6px 10px;text-align:left;font-size:13px}
    tr.ok td:nth-child(3){color:#0a7a3d;font-weight:600} tr.bad td:nth-child(3){color:#b3261e;font-weight:700}
  </style><h1>${escape(url)}</h1><pre>${tags}</pre>
  <table><tr><th>hreflang</th><th>URL</th><th>HTTP</th><th>Page title</th></tr>${rows}</table>`);
  await page.setViewportSize({ width: 1100, height: 600 });
  await shot(page, name);
}

/** Flush the Magento cache with the command given in QA_FLUSH_COMMAND (the full page cache is not cleared by a config save). */
export function flushCache() {
  const command = process.env.QA_FLUSH_COMMAND;
  if (!command) {
    throw new Error('set QA_FLUSH_COMMAND to a command that flushes the Magento cache');
  }
  execSync(command, { stdio: 'ignore' });
}

/** Log in to the admin and return the secret key the configuration URLs need. */
export async function loginAdmin(page) {
  await page.goto('/admin');
  await page.locator('#username').fill(process.env.QA_ADMIN_USER);
  await page.locator('#login').fill(process.env.QA_ADMIN_PASSWORD);
  await page.locator('.action-login').click();
  await expect(page.locator('.page-title, .dashboard-container').first()).toBeVisible();
  const link = await page.locator('a[href*="/system_config/edit/"]').first().getAttribute('href');

  return link.match(/\/key\/([0-9a-f]+)/)[1];
}

/** Every JavaScript problem of the page; network failures (a sample-data image) and Magento's own Braintree module are left out. */
export function collectPageErrors(page) {
  const errors = [];
  page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
  page.on('console', (message) => {
    const thirdParty = /\/PayPal_Braintree\//.test(message.location().url);
    if (message.type() === 'error' && !/Failed to load resource/.test(message.text()) && !thirdParty) {
      errors.push(`console.error: ${message.text()}`);
    }
  });
  return errors;
}
