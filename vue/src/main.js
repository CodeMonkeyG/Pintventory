import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'

import App from './App.vue'
import router from './router'
import api from './axios'

const app = createApp(App)

// Create Vuetify instance
const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
  },
  theme: {
    defaultTheme: 'dark',
    themes: {
      light: {
        colors: {
          primary: '#1976d2',
          secondary: '#424242',
          accent: '#82b1ff',
          error: '#ff5252',
          warning: '#fb8c00',
          info: '#2196f3',
          success: '#4caf50'
        }
      },
      dark: {
        dark: true,
        colors: {
          primary: '#06b6d4',    // Cyan 500
          secondary: '#94a3b8',  // Slate 400
          surface: '#1e293b',    // Slate 800
          background: '#0f172a', // Slate 900
          error: '#ef4444',      // Red 500
          info: '#3b82f6',       // Blue 500
          success: '#10b981',    // Emerald 500
          warning: '#f59e0b',    // Amber 500
        }
      }
    }
  }
})

app.use(createPinia())
app.use(router)
app.use(vuetify)

// Make axios instance globally available
app.config.globalProperties.$api = api;

app.mount('#app')