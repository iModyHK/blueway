import { abs, siteUrl, content, on } from '../lib/site';
import { posts } from '../lib/news';

export function GET() {
  const pairs: [string, string, string][] = [['/', '/en/', '1.0']];
  if (on('service_pages')) {
    const en = new Set(content('en').services.items.map((x: any) => x.slug));
    for (const x of content('ar').services.items) if (x.slug) pairs.push([`/services/${x.slug}/`, en.has(x.slug) ? `/en/services/${x.slug}/` : '', '0.8']);
  }
  if (on('news')) {
    const list = posts();
    if (list.length) pairs.push(['/news/', '/en/news/', '0.6']);
    for (const p of list) pairs.push([`/news/${p.slug}/`, `/en/news/${p.slug}/`, '0.5']);
  }
  const url = (loc: string, ar: string, en: string, pr: string) =>
    `<url><loc>${abs(loc)}</loc>${ar && en ? `<xhtml:link rel="alternate" hreflang="ar" href="${abs(ar)}"/><xhtml:link rel="alternate" hreflang="en" href="${abs(en)}"/>` : ''}<priority>${pr}</priority></url>`;
  const body = siteUrl()
    ? pairs.flatMap(([ar, en, pr]) => [url(ar, ar, en, pr), ...(en ? [url(en, ar, en, pr)] : [])]).join('')
    : '';
  return new Response(`<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">${body}</urlset>`, { headers: { 'Content-Type': 'application/xml' } });
}
