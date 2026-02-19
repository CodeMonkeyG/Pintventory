<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<template>
  <v-app>
    <v-app-bar color="primary" dark>
      <v-app-bar-title class="text-h5 font-weight-bold">Thriftly</v-app-bar-title>
      
      <v-spacer />
      
      <div class="d-flex align-center gap-2">
        <router-link to="/inventory" style="color: white; text-decoration: none; padding: 0 12px; cursor: pointer; transition: opacity 0.2s;" @mouseover="$event.target.style.opacity='0.7'" @mouseout="$event.target.style.opacity='1'">Inventory</router-link>
        <router-link to="/customers" style="color: white; text-decoration: none; padding: 0 12px; cursor: pointer; transition: opacity 0.2s;" @mouseover="$event.target.style.opacity='0.7'" @mouseout="$event.target.style.opacity='1'">Customers</router-link>
        <router-link to="/vendors" style="color: white; text-decoration: none; padding: 0 12px; cursor: pointer; transition: opacity 0.2s;" @mouseover="$event.target.style.opacity='0.7'" @mouseout="$event.target.style.opacity='1'">Vendors</router-link>
        <router-link to="/profile" style="color: white; text-decoration: none; padding: 0 12px; cursor: pointer; transition: opacity 0.2s;" @mouseover="$event.target.style.opacity='0.7'" @mouseout="$event.target.style.opacity='1'">Profile</router-link>
      </div>

      <v-spacer />

      <div class="d-flex align-center gap-2">
        <div v-if="authStore.user" class="d-flex align-center gap-2">
          <img v-if="authStore.user.avatar" :src="authStore.user.avatar" alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;" />
          <span class="text-body2">{{ authStore.user.name }}</span>
        </div>
        <v-btn variant="outlined" @click="logout">
          Logout
        </v-btn>
      </div>
    </v-app-bar>
    
    <v-main class="mt-6 pa-16" style="overflow-y: auto; overflow-x: hidden;">
      <slot></slot>
    </v-main>
  </v-app>
</template>