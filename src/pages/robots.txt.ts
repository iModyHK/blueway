import { abs, siteUrl } from '../lib/site';

export function GET() {
  const lines = ['User-agent: *', 'Allow: /', 'Disallow: /admin/', 'Disallow: /oauth/', 'Disallow: /lib/', 'Disallow: /profile/', 'Disallow: /en/profile/'];
  if (siteUrl()) lines.push('', `Sitemap: ${abs('/sitemap.xml')}`);
  return new Response(lines.join('\n') + '\n', { headers: { 'Content-Type': 'text/plain' } });
}
