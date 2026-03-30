import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    allowedHosts: ['pintventory.com'],
    proxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
      '/sanctum': {
        target: 'http://nginx',
        changeOrigin: true,
      },
      '/storage': {
        target: 'http://nginx',
        changeOrigin: true,
      },
    },
  },
})
