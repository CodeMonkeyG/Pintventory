<script setup>
import { RouterView, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  await authStore.fetchUser()
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