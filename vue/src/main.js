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
    defaultTheme: 'light',
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