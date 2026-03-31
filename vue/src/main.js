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
      },
      'gruvbox-dark': {
        dark: true,
        colors: {
          primary: '#fabd2f',    // Yellow
          secondary: '#a89984',  // Gray
          surface: '#282828',    // Dark 1
          background: '#1d2021', // Dark 0 hard
          error: '#fb4934',      // Red
          info: '#83a598',       // Blue
          success: '#b8bb26',    // Green
          warning: '#fe8019',    // Orange
        }
      },
      'gruvbox-light': {
        dark: false,
        colors: {
          primary: '#af3a03',    // Orange
          secondary: '#7c6f64',  // Gray
          surface: '#f2e5bc',    // Light 1
          background: '#fbf1c7', // Light 0
          error: '#9d0006',      // Red
          info: '#076678',       // Blue
          success: '#79740e',    // Green
          warning: '#b57614',    // Yellow
        }
      },
      'solarized-dark': {
        dark: true,
        colors: {
          primary: '#268bd2',    // Blue
          secondary: '#586e75',  // Base01
          surface: '#073642',    // Base02
          background: '#002b36', // Base03
          error: '#dc322f',      // Red
          info: '#2aa198',       // Cyan
          success: '#859900',    // Green
          warning: '#b58900',    // Yellow
        }
      },
      'monokai': {
        dark: true,
        colors: {
          primary: '#a6e22e',    // Green
          secondary: '#93a1a1',
          surface: '#272822',    // Dark gray
          background: '#1e1f1c', // Slightly darker
          error: '#f92672',      // Pink
          info: '#66d9ef',       // Cyan
          success: '#a6e22e',    // Green
          warning: '#fd971f',    // Orange
        }
      },
      'dracula': {
        dark: true,
        colors: {
          primary: '#bd93f9',    // Purple
          secondary: '#6272a4',  // Comment
          surface: '#282a36',    // Background
          background: '#1e1f29', // Darker background
          error: '#ff5555',      // Red
          info: '#8be9fd',       // Cyan
          success: '#50fa7b',    // Green
          warning: '#ffb86c',    // Orange
        }
      },
      'material': {
        dark: true,
        colors: {
          primary: '#82aaff',    // Blue
          secondary: '#546e7a',  // Gray Blue
          surface: '#263238',    // Background
          background: '#1a2327', // Darker background
          error: '#f07178',      // Red
          info: '#89ddff',       // Cyan
          success: '#c3e88d',    // Green
          warning: '#ffcb6b',    // Yellow
        }
      },
      'mono-amber': {
        dark: true,
        colors: {
          primary: '#ffb000',    // Amber
          secondary: '#4d3b00',  // Dark Amber
          surface: '#1a1a1a',    // Dark surface
          background: '#000000', // Black
          error: '#ff5555',      
          info: '#ffb000',
          success: '#ffb000',
          warning: '#ffb000',
        }
      },
      'mono-green': {
        dark: true,
        colors: {
          primary: '#00ff00',    // Green
          secondary: '#004d00',  // Dark Green
          surface: '#1a1a1a',    // Dark surface
          background: '#000000', // Black
          error: '#ff5555',
          info: '#00ff00',
          success: '#00ff00',
          warning: '#00ff00',
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