import { readFileSync, writeFileSync, existsSync, mkdirSync, statSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { join } from 'node:path';
import sharp from 'sharp';

const files = ['src/content/settings.json', 'src/content/ar.json', 'src/content/en.json'];
const keys = new Set(['image', 'logo', 'logo_white', 'credit_logo']);
const outDir = 'public/media';
const mapFile = 'src/content/media-map.json';
mkdirSync(outDir, { recursive: true });

const found = new Set();
const walk = (v, k) => {
  if (Array.isArray(v)) v.forEach((x) => walk(x, k));
  else if (v && typeof v === 'object') Object.entries(v).forEach(([kk, vv]) => walk(vv, kk));
  else if (typeof v === 'string' && keys.has(k) && (/^https?:\/\//.test(v) || /^\/uploads\/.+\.(jpe?g|png)$/i.test(v))) found.add(v);
};
files.forEach((f) => walk(JSON.parse(readFileSync(f, 'utf8'))));

const map = {};
const hash = (s) => createHash('sha1').update(s).digest('hex').slice(0, 16);

for (const src of found) {
  const name = `${hash(src)}.webp`;
  const out = join(outDir, name);
  try {
    if (!existsSync(out)) {
      let input;
      if (src.startsWith('/uploads/')) {
        const local = join('public', src);
        if (statSync(local).size < 60 * 1024) continue;
        input = readFileSync(local);
      } else {
        const res = await fetch(src, { redirect: 'follow', signal: AbortSignal.timeout(20000) });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        input = Buffer.from(await res.arrayBuffer());
      }
      await sharp(input).rotate().resize({ width: 1600, withoutEnlargement: true }).webp({ quality: 78 }).toFile(out);
    }
    map[src] = `/media/${name}`;
    console.log(`media: ${src} -> /media/${name}`);
  } catch (e) {
    console.warn(`media: skipped ${src} (${e.message})`);
  }
}

writeFileSync(mapFile, JSON.stringify(map, null, 2) + '\n');
