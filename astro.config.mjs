import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  output: 'static',
  build: { format: 'directory' },
  cacheDir: './.astro-cache',
  vite: { cacheDir: './.vite-cache', plugins: [tailwindcss()] },
});
