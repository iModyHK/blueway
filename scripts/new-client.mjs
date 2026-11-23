import { cpSync, readFileSync, writeFileSync, existsSync, mkdirSync, rmSync, readdirSync } from 'node:fs';
import { join, resolve, relative, basename } from 'node:path';
import { parseArgs } from 'node:util';

const { values: o, positionals } = parseArgs({
  allowPositionals: true,
  options: {
    'name-ar': { type: 'string', default: 'اسم الشركة' },
    'name-en': { type: 'string', default: 'Company Name' },
    primary: { type: 'string', default: '#1e56a8' },
    accent: { type: 'string', default: '#6cb2f5' },
    dark: { type: 'string', default: '#0b1a4a' },
    email: { type: 'string', default: '' },
    phone: { type: 'string', default: '' },
    whatsapp: { type: 'string', default: '' },
    city: { type: 'string', default: 'Riyadh' },
    address: { type: 'string', default: 'الرياض' },
    url: { type: 'string', default: 'https://example.com' },
    repo: { type: 'string', default: 'OWNER/REPO' },
  },
});

const target = positionals[0];
if (!target) {
  console.error('Usage: npm run new-client -- ../client-folder --name-ar "..." --name-en "..." --primary "#hex" --email x@y.com --phone "05..." --whatsapp 9665... --url https://domain --repo owner/repo');
  process.exit(1);
}
const src = resolve('.');
const dest = resolve(target);
if (existsSync(dest) && readdirSync(dest).length) {
  console.error(`Target ${dest} is not empty`);
  process.exit(1);
}

const skip = ['node_modules', 'dist', '.astro', '.git', 'public/media', 'public/uploads', 'public/clients', 'site-config.php'];
const skipFiles = ['public/brand/mark.png', 'public/brand/logo-full.png', 'public/brand/logo-full-white.png', 'public/brand/og.jpg', 'public/favicon.png'];
cpSync(src, dest, {
  recursive: true,
  filter: (p) => {
    const r = relative(src, p).replace(/\\/g, '/');
    if (!r) return true;
    if (skip.some((s) => r === s || r.startsWith(s + '/'))) return false;
    if (skipFiles.includes(r)) return false;
    if (/^[^/]+\.(png|zip)$/.test(r)) return false;
    if (/^public\/admin\/.+\.(js|wasm)$/.test(r) && !r.endsWith('ar.js')) return false;
    if (r === 'public/admin/ar.js') return false;
    return true;
  },
});
mkdirSync(join(dest, 'public/uploads'), { recursive: true });
mkdirSync(join(dest, 'src/content/news'), { recursive: true });
for (const f of readdirSync(join(dest, 'src/content/news'))) if (f.endsWith('.json')) rmSync(join(dest, 'src/content/news', f));
writeFileSync(join(dest, 'src/content/news/.gitkeep'), '');
writeFileSync(join(dest, 'public/uploads/.gitkeep'), '');
writeFileSync(join(dest, 'src/content/media-map.json'), '{}\n');

const settings = JSON.parse(readFileSync('src/content/settings.json', 'utf8'));
Object.assign(settings, {
  name_ar: o['name-ar'], name_en: o['name-en'], logo: '', logo_white: '', og_image: '',
  primary: o.primary, accent: o.accent, dark: o.dark,
  email: o.email, phone: o.phone, phone_intl: '', whatsapp: o.whatsapp, cr: '', founded: '',
  site_url: o.url, city: o.city, map_query: o.address, map_embed: '', geo_lat: '', geo_lng: '', social: [], turnstile_site_key: '',
  analytics: { provider: 'none', src: '', id: '' },
  sections: { stats: false, about: true, services: true, projects: false, certificates: false, clients: false, testimonials: false, faq: false, news: false, blocks: false, map: true, contact: true, service_pages: true, whatsapp: !!o.whatsapp, profile_download: true, lang_switch: true, credit: true },
});
writeFileSync(join(dest, 'src/content/settings.json'), JSON.stringify(settings, null, 2) + '\n');

const blank = (lang) => {
  const c = JSON.parse(readFileSync(`src/content/${lang}.json`, 'utf8'));
  const A = lang === 'ar';
  const name = A ? o['name-ar'] : o['name-en'];
  c.seo = { title: name, description: '', keywords: '' };
  c.tagline = A ? 'وصف مختصر لنشاط الشركة' : 'Short description of the business';
  c.hero = { ...c.hero, badge: '', title: A ? 'عنوان رئيسي جذاب' : 'A clear headline', text: A ? 'فقرة قصيرة تشرح ماذا تقدم الشركة ولمن.' : 'A short paragraph on what the company offers and to whom.', image: '' };
  c.stats = [];
  c.about = { ...c.about, title: A ? 'من نحن' : 'About us', text: A ? 'نبذة عن الشركة.' : 'About the company.', image: '', points: [] };
  c.services = { ...c.services, title: A ? 'خدماتنا' : 'Our services', intro: '', items: [{ slug: 'service-1', icon: 'building', title: A ? 'الخدمة الأولى' : 'First service', text: A ? 'وصف الخدمة.' : 'Service description.', image: '', details: [], body: '', seo_title: '', seo_description: '' }] };
  c.testimonials = { ...c.testimonials, items: [] };
  c.faq = { ...c.faq, items: [] };
  c.blocks = { items: [] };
  c.contact.form_title = '';
  c.contact.fields = c.contact.fields.filter((f) => ['name', 'email', 'phone', 'message'].includes(f.name));
  c.projects = { ...c.projects, items: [] };
  c.certificates = { ...c.certificates, items: [] };
  c.clients = { ...c.clients, items: [] };
  c.contact = { ...c.contact, address: o.address };
  return c;
};
for (const l of ['ar', 'en']) writeFileSync(join(dest, `src/content/${l}.json`), JSON.stringify(blank(l), null, 2) + '\n');

const cfgPath = join(dest, 'public/admin/config.yml');
writeFileSync(cfgPath, readFileSync(cfgPath, 'utf8').replace(/repo: .*/, `repo: ${o.repo}`).replaceAll('https://example.com', o.url));
const pkgPath = join(dest, 'package.json');
const pkg = JSON.parse(readFileSync(pkgPath, 'utf8'));
pkg.name = basename(dest).toLowerCase().replace(/[^a-z0-9-]/g, '-');
writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + '\n');

console.log(`Created ${dest}\nNext:\n  cd ${relative(process.cwd(), dest) || '.'}\n  npm install\n  npm run dev   (and in another terminal: npm run cms)\n  Add logo, favicon.png and og image from /admin`);
