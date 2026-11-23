import { readdirSync, copyFileSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import en from 'decap-cms-locales/dist/esm/en/index.js';

const src = 'node_modules/decap-cms/dist';
const out = 'public/admin';
mkdirSync(out, { recursive: true });
for (const f of readdirSync(src)) {
  if (/(^|\.)decap-cms\.js$/.test(f) || f.endsWith('.wasm')) copyFileSync(join(src, f), join(out, f));
}

const ar = structuredClone(en);
for (const [path, value] of Object.entries(JSON.parse(readFileSync('scripts/decap-ar.json', 'utf8')))) {
  const keys = path.split('.');
  const last = keys.pop();
  keys.reduce((o, k) => o[k], ar)[last] = value;
}
writeFileSync(join(out, 'ar.js'), `CMS.registerLocale('ar', ${JSON.stringify(ar)});\n`);
