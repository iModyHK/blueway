import { s, content } from '../lib/site';

const form = (lang: 'ar' | 'en') => {
  const t = content(lang);
  const services = (t.services?.items ?? []).map((x: any) => x.title);
  return (t.contact?.fields ?? [])
    .filter((f: any) => f.name && f.type)
    .map((f: any) => ({ name: f.name, label: f.label, type: f.type, required: !!f.required, options: f.type === 'services' ? [...services, t.contact.service_other].filter(Boolean) : (f.options ?? []).map((o: any) => o.text) }));
};

export function GET() {
  return new Response(JSON.stringify({ to: s.email, name: s.name_en || s.name_ar, forms: { ar: form('ar'), en: form('en') } }));
}
