<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useDisplay } from 'vuetify';

const authStore = useAuthStore();
const router = useRouter();
const { mobile } = useDisplay();

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
  <v-container class="d-flex align-center justify-center" style="min-height: 100vh;">
    <v-card :max-width="mobile ? '100%' : '450'" :class="mobile ? 'pa-6' : 'pa-10'" class="text-center" variant="outlined">
      <v-card-title :class="mobile ? 'text-h4' : 'text-h2'" class="font-weight-bold mb-4">Pintventory</v-card-title>
      <v-card-text :class="mobile ? 'text-body-1' : 'text-h6'" class="mb-6">
        Pintventory is a smart, AI-powered inventory system designed for collectors and small businesses. Effortlessly track unique finds and bulk stock with automated image recognition.
      </v-card-text>
      
      <div class="mb-8">
        <v-btn
          variant="text"
          color="primary"
          to="/about"
          append-icon="mdi-chevron-right"
          class="text-none"
        >
          Learn more about Pintventory
        </v-btn>
      </div>

      <v-btn
        color="primary"
        size="large"
        prepend-icon="mdi-google"
        @click="loginWithGoogle"
        block
        elevation="2"
        height="56"
        class="text-none"
      >
        Sign in with Google
      </v-btn>
    </v-card>
  </v-container>
</template>
