import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'url';

const root = fileURLToPath(new URL('.', import.meta.url));
export default defineConfig({
  root,
  base: './',
  plugins: [vue()],
  resolve: { alias: { '@': root + 'src' } },
  build: { outDir: '../renderer-dist', emptyOutDir: true, chunkSizeWarningLimit: 1500, target: 'chrome120' },
  server: { port: 5178 },
});
