<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';

const authStore = useAuthStore();
const router = useRouter();
const { mobile } = useDisplay();

const drawer = ref(false);

const navItems = [
  { title: 'Inventory', to: '/inventory', icon: 'mdi-package-variant-closed' },
  { title: 'Customers', to: '/customers', icon: 'mdi-account-group' },
  { title: 'Vendors', to: '/vendors', icon: 'mdi-truck-delivery' },
  { title: 'Locations', to: '/storage-locations', icon: 'mdi-map-marker' },
  { title: 'Profile', to: '/profile', icon: 'mdi-account' },
];

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<template>
  <v-app>
    <!-- Navigation Drawer for Mobile -->
    <v-navigation-drawer v-model="drawer" temporary v-if="mobile">
      <v-list>
        <v-list-item
          v-if="authStore.user"
          :prepend-avatar="authStore.user.avatar"
          :title="authStore.user.name"
          :subtitle="authStore.user.email"
          class="mb-2"
        ></v-list-item>
        
        <v-divider></v-divider>
        
        <v-list-item
          v-for="item in navItems"
          :key="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          color="primary"
        ></v-list-item>
      </v-list>
      
      <template v-slot:append>
        <div class="pa-4">
          <v-btn block color="error" variant="tonal" @click="logout" prepend-icon="mdi-logout">
            Logout
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar color="surface" elevation="0" border="b">
      <v-app-bar-nav-icon v-if="mobile" @click="drawer = !drawer"></v-app-bar-nav-icon>
      
      <v-app-bar-title class="text-h6 text-md-h5 font-weight-bold text-primary">
        Pintventory
      </v-app-bar-title>
      
      <v-spacer />
      
      <!-- Desktop Navigation -->
      <div v-if="!mobile" class="d-flex align-center gap-1 mr-4">
        <v-btn
          v-for="item in navItems"
          :key="item.title"
          :to="item.to"
          variant="text"
          class="text-none"
        >
          {{ item.title }}
        </v-btn>
      </div>

      <v-spacer v-if="!mobile" />

      <!-- User Profile & Logout (Desktop) -->
      <div v-if="!mobile" class="d-flex align-center gap-3 mr-2">
        <div v-if="authStore.user" class="d-flex align-center gap-2">
          <v-avatar size="32">
            <v-img v-if="authStore.user.avatar" :src="authStore.user.avatar" alt="Avatar"></v-img>
            <v-icon v-else>mdi-account</v-icon>
          </v-avatar>
          <span class="text-body-2 font-weight-medium">{{ authStore.user.name }}</span>
        </div>
        <v-btn variant="outlined" size="small" @click="logout" class="text-none">
          Logout
        </v-btn>
      </div>
    </v-app-bar>
    
    <v-main :class="(mobile ? 'pa-4' : 'pa-8 pa-md-16') + ' pt-16'" style="overflow-y: auto; overflow-x: hidden;">
      <v-container fluid class="max-width-1200 mx-auto pa-0 mt-4">
        <slot></slot>
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.max-width-1200 {
  max-width: 1200px;
}
</style>