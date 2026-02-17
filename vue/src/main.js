import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import api from './axios' // Import the custom axios instance

import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Make axios instance globally available
app.config.globalProperties.$api = api;

app.mount('#app')