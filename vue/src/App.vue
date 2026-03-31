<script setup>
import { RouterView, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useTheme } from 'vuetify'

const authStore = useAuthStore()
const router = useRouter()
const theme = useTheme()

onMounted(async () => {
  await authStore.fetchUser()
  
  // Set theme from user preferences if available
  if (authStore.user?.preferences?.theme) {
    theme.global.name.value = authStore.user.preferences.theme
  }

  if (!authStore.loggedIn && router.currentRoute.value.name !== 'login') {
    router.push('/login')
  }
})
</script>

<template>
  <v-app>
    <RouterView />
  </v-app>
</template>