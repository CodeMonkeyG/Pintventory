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
  <v-container class="d-flex align-center justify-center" style="height: 100vh;">
    <v-card max-width="400" class="text-center pa-8">
      <v-card-title class="text-h3 mb-2">Thriftly</v-card-title>
      <v-card-text class="text-h6 mb-6">
        Please log in to continue.
      </v-card-text>
      <v-btn
        color="primary"
        size="large"
        prepend-icon="mdi-google"
        @click="loginWithGoogle"
        class="w-100"
      >
        Login with Google
      </v-btn>
    </v-card>
  </v-container>
</template>
