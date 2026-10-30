import { fileURLToPath, URL } from 'node:url'
import preact from '@preact/preset-vite'
import tailwindcss from '@tailwindcss/vite'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

const local = (path: string) => fileURLToPath(new URL(path, import.meta.url))

export default defineConfig({
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app.tsx'], refresh: true }),
    // Our own React aliases below (instead of the preset's) so `react` can include the use() shim.
    preact({ reactAliasesEnabled: false }),
    tailwindcss(),
  ],
  resolve: {
    alias: [
      { find: /^react$/, replacement: local('./resources/js/lib/react-compat.js') },
      { find: /^react\/(jsx-runtime|jsx-dev-runtime)$/, replacement: 'preact/$1' },
      { find: /^react-dom$/, replacement: 'preact/compat' },
      { find: /^react-dom\/client$/, replacement: 'preact/compat/client' },
      { find: '@', replacement: local('./resources/js') },
    ],
  },
})
