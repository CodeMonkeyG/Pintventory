<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const loginWithGoogle = () => {
  window.location.href = '/auth/google/redirect';
};

onMounted(() => {
  if (authStore.loggedIn) {
    router.push('/inventory');
  }
});
</script>

<template>
  <div class="login-container">
    <h1>Thriftly</h1>
    <p>Please log in to continue.</p>
    <button @click="loginWithGoogle">Login with Google</button>
  </div>
</template>

<style scoped>
.login-container {
  text-align: center;
  margin-top: 100px;
}
button {
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
}
</style>
