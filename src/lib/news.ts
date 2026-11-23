import { marked } from 'marked';
import type { Lang } from './site';

const files = import.meta.glob('../content/news/*.json', { eager: true, import: 'default' }) as Record<string, any>;

export const posts = () =>
  Object.values(files)
    .filter((p) => p && p.slug && p.published !== false)
    .sort((a, b) => String(b.date).localeCompare(String(a.date)));

export const field = (p: any, key: string, lang: Lang) => p[`${key}_${lang}`] || p[`${key}_${lang === 'ar' ? 'en' : 'ar'}`] || '';
export const html = (md: string) => marked.parse(md || '', { async: false }) as string;
export const fmtDate = (d: string, lang: Lang) => {
  const dt = new Date(d);
  return isNaN(+dt) ? d : dt.toLocaleDateString(lang === 'ar' ? 'ar-SA-u-ca-gregory-nu-latn' : 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' });
};
