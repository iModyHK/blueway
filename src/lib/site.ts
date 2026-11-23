import settings from '../content/settings.json';
import ar from '../content/ar.json';
import en from '../content/en.json';
import media from '../content/media-map.json';

export type Lang = 'ar' | 'en';
export const s: any = settings;
export const sections: Record<string, boolean> = { ...(settings as any).sections };
export const content = (lang: Lang): any => (lang === 'ar' ? ar : en);
export const nameOf = (lang: Lang) => (lang === 'ar' ? s.name_ar : s.name_en);
export const homeOf = (lang: Lang) => (lang === 'ar' ? '/' : '/en/');
export const on = (key: string) => sections[key] !== false;

const map: Record<string, string> = media as any;
export const img = (src?: string) => (src ? map[src] ?? src : '');

export const siteUrl = () => String(s.site_url || '').replace(/\/+$/, '');
export const abs = (path: string) => (/^https?:/.test(path) ? path : siteUrl() + (path.startsWith('/') ? path : '/' + path));

export const icons: Record<string, string> = {
  building: 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1M9 13h1m4 0h1M9 17h1m4 0h1',
  eye: 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z',
  check: 'M9 11l3 3 8-8M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9',
  chart: 'M9 17v-6m4 6V7m4 10v-3M4 21h16',
  shield: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z',
  users: 'M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 11a4 4 0 100-8 4 4 0 000 8zM21 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8',
  tool: 'M14.7 6.3a4 4 0 00-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 005.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z',
  leaf: 'M11 20A7 7 0 014 13c0-6 7-10 16-10 0 9-4 16-10 16M4 21c3-6 7-9 12-11',
  bolt: 'M13 2L4 14h7l-1 8 9-12h-7l1-8z',
  cable: 'M4 4v5a4 4 0 004 4h8a4 4 0 014 4v3M2 4h4M18 20h4M12 13v0',
  tower: 'M12 10v11M8 21l4-11 4 11M9 17h6M7.8 6.8a6 6 0 018.4 0M5 4a10 10 0 0114 0M12 10a1 1 0 100-2 1 1 0 000 2z',
  drop: 'M12 3s7 7.6 7 12a7 7 0 01-14 0c0-4.4 7-12 7-12z',
  award: 'M12 15a6 6 0 100-12 6 6 0 000 12zM8.2 13.9L7 22l5-3 5 3-1.2-8.1',
  file: 'M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9l-6-6zM14 3v6h6M9 13h6M9 17h6',
  pin: 'M12 22s7-6.3 7-12a7 7 0 00-14 0c0 5.7 7 12 7 12zM12 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
  mail: 'M3 5h18v14H3zM3 7l9 6 9-6',
  phone: 'M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.4 1.8.7 2.7a2 2 0 01-.5 2.1L8 9.8a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.7.7a2 2 0 011.7 2z',
  download: 'M12 3v12M7 10l5 5 5-5M5 21h14',
};

export const social: Record<string, string> = {
  x: 'M4 4l16 16M20 4L4 20',
  linkedin: 'M4 9h4v11H4zM6 4a2 2 0 110 4 2 2 0 010-4zM10 9h4v1.5c.6-1 1.8-1.8 3.5-1.8 3 0 3.5 2 3.5 4.6V20h-4v-5.8c0-1.4 0-3-1.9-3s-2.1 1.4-2.1 2.9V20h-4z',
  instagram: 'M7 3h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7a4 4 0 014-4zM12 8a4 4 0 100 8 4 4 0 000-8zM17.5 6.5h.01',
  snapchat: 'M12 3c3 0 5 2.2 5 5v2.5l1.8-.5c.5 0 .8.6.4.9l-2 1.3c.5 2 2 3.3 3.8 3.8-.4.8-1.6 1-2.6 1.2l-.5 1.3c-1-.2-2-.3-3 .3-1 .7-1.8 1.2-2.9 1.2s-1.9-.5-2.9-1.2c-1-.6-2-.5-3-.3l-.5-1.3c-1-.2-2.2-.4-2.6-1.2 1.8-.5 3.3-1.8 3.8-3.8l-2-1.3c-.4-.3-.1-.9.4-.9L7 10.5V8c0-2.8 2-5 5-5z',
  tiktok: 'M14 3v11.5a3.5 3.5 0 11-3.5-3.5M14 3c.5 2.5 2.3 4 5 4.2',
  youtube: 'M22 8.5s-.2-1.6-.8-2.3c-.8-.8-1.7-.8-2.1-.9C16.2 5 12 5 12 5s-4.2 0-7.1.3c-.4.1-1.3.1-2.1.9-.6.7-.8 2.3-.8 2.3S2 10.3 2 12.2v1.6c0 1.8.2 3.7.2 3.7s.2 1.6.8 2.3c.8.8 1.9.8 2.4.9 1.7.2 6.6.2 6.6.2s4.2 0 7.1-.3c.4-.1 1.3-.1 2.1-.9.6-.7.8-2.3.8-2.3s.2-1.8.2-3.7v-1.6c0-1.8-.2-3.6-.2-3.6zM10 15.5v-6l5.2 3z',
  facebook: 'M15 3h-2.5A3.5 3.5 0 009 6.5V9H7v3.5h2V21h3.5v-8.5H15l.5-3.5h-3V7c0-.6.4-1 1-1H15z',
  whatsapp: 'M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z',
};

export const fallbackFor: Record<string, string> = { bolt: 'generator', cable: 'fiber', tower: 'tower', drop: 'oil', tool: 'projects' };
export const initials = (n: string) => n.replace(/^(شركة|مؤسسة)\s+/, '').trim().split(/\s+/).slice(0, 2).map((w) => w.replace(/^ال/, '')[0]).join('').toUpperCase();
export const phoneHref = () => `tel:${s.phone_intl || String(s.phone || '').replace(/\s/g, '')}`;

export const SECTION_KEYS = ['stats', 'about', 'services', 'projects', 'certificates', 'clients', 'testimonials', 'faq', 'news', 'map', 'contact'];

export const sectionOrder = (): string[] => {
  const listed = (s.section_order ?? []).map((x: any) => (typeof x === 'string' ? x : x?.section)).filter((k: string) => SECTION_KEYS.includes(k));
  return [...new Set([...listed, ...SECTION_KEYS])];
};

const newsFiles = import.meta.glob('../content/news/*.json', { eager: true, import: 'default' }) as Record<string, any>;

export const hasData = (key: string, lang: Lang): boolean => {
  if (!on(key)) return false;
  const t = content(lang);
  switch (key) {
    case 'stats': return (t.stats ?? []).length > 0;
    case 'services': return (t.services?.items ?? []).length > 0;
    case 'projects': return (t.projects?.items ?? []).length > 0;
    case 'certificates': return (t.certificates?.items ?? []).length > 0;
    case 'clients': return (t.clients?.items ?? []).length > 0;
    case 'testimonials': return (t.testimonials?.items ?? []).length > 0;
    case 'faq': return (t.faq?.items ?? []).length > 0;
    case 'news': return Object.values(newsFiles).some((p: any) => p?.slug && p.published !== false);
    case 'map': return !!(s.map_embed || s.map_query);
    default: return true;
  }
};

export const slugify = (v: string) => String(v || '').toLowerCase().trim().replace(/[^a-z0-9؀-ۿ]+/g, '-').replace(/^-+|-+$/g, '');
