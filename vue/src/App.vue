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
  <div class="app-container">
    <RouterView />
  </div>
</template>

<style scoped>
.app-container {
  font-family: Arial, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
</style>