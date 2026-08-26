#!/usr/bin/env node
/**
 * Compare a rebuilt site against the upstream demo it was derived from.
 *
 * The classes in the Divi exports were renamed from the vendor prefix to mdw-,
 * and if any of them fails to resolve the page still returns HTTP 200 -- it
 * just renders unstyled or with whole sections missing. Eyeballing a screenshot
 * does not reliably catch that, so this diffs the two DOMs structurally.
 *
 *   node tools/check-parity.js
 *   LOCAL=http://example.local node tools/check-parity.js
 *
 * Exits non-zero if the local build is missing anything the upstream renders.
 */
const VENDOR = process.env.VENDOR || 'https://lightyellow-mole-660888.hostingersite.com';
const LOCAL = process.env.LOCAL || 'http://mrdemonwolf-wp-theme.local';
const PAGES = (process.env.PAGES || '/,/about-us/,/portfolio/,/services/,/contact/').split(',');

// Identifiers derived from the theme directory name or script handles differ by
// design and are not parity failures.
const IGNORE = new Set(['multipurpose-divi-child-theme', 'script-js']);

async function grab(url) {
  try {
    const r = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' }, redirect: 'follow' });
    return { ok: r.ok, status: r.status, html: await r.text() };
  } catch (e) {
    return { ok: false, status: 0, html: '', err: String(e) };
  }
}

const suffixes = (html, prefix) =>
  new Set((html.match(new RegExp(prefix + '[a-z0-9-]+', 'gi')) || [])
    .map((s) => s.toLowerCase().slice(prefix.length)));

const count = (html, re) => (html.match(re) || []).length;

(async () => {
  let failures = 0;
  console.log(`vendor ${VENDOR}\nlocal  ${LOCAL}\n`);
  console.log('page             sections   rows      images    missing classes');

  for (const page of PAGES) {
    const [v, l] = await Promise.all([grab(VENDOR + page), grab(LOCAL + page)]);
    if (!v.ok || !l.ok) {
      console.log(`${page.padEnd(16)} FETCH FAILED  vendor=${v.status} local=${l.status}`);
      failures++;
      continue;
    }

    const missing = [...suffixes(v.html, 'nexus-')]
      .filter((c) => !suffixes(l.html, 'mdw-').has(c) && !IGNORE.has(c))
      .sort();

    const pair = (re) => `${count(v.html, re)}/${count(l.html, re)}`;
    const sec = pair(/class="[^"]*et_pb_section[^"]*"/g);
    const row = pair(/class="[^"]*et_pb_row[^"]*"/g);
    const img = pair(/<img[^>]+src=/g);

    console.log(
      `${page.padEnd(16)} ${sec.padEnd(10)} ${row.padEnd(9)} ${img.padEnd(9)} ` +
      (missing.length ? missing.join(', ') : '-')
    );
    if (missing.length) failures++;

    const stale = count(l.html, /nexus/gi);
    if (stale) {
      console.log(`${''.padEnd(16)} !! ${stale} upstream reference(s) still in the local HTML`);
      failures++;
    }
  }

  // Broken images are the other silent failure: the page renders, the asset 404s.
  let checked = 0;
  const broken = [];
  for (const page of PAGES) {
    const l = await grab(LOCAL + page);
    if (!l.ok) continue;
    const srcs = [...new Set([...l.html.matchAll(/<img[^>]+src="([^"]+)"/g)].map((m) => m[1]))]
      .filter((s) => /^https?:/.test(s));
    for (const src of srcs) {
      checked++;
      try {
        const r = await fetch(src, { method: 'HEAD' });
        if (!r.ok) broken.push(`${r.status} ${page} ${src}`);
      } catch {
        broken.push(`ERR ${page} ${src}`);
      }
    }
  }
  console.log(`\nimages checked ${checked}, broken ${broken.length}`);
  broken.slice(0, 20).forEach((b) => console.log('  ' + b));
  if (broken.length) failures++;

  console.log(failures ? `\nPARITY FAILED (${failures})` : '\nparity ok');
  process.exit(failures ? 1 : 0);
})();
