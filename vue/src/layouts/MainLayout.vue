<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';
import WorkspaceSelector from '../components/WorkspaceSelector.vue';

const authStore = useAuthStore();
const router = useRouter();
const { mobile } = useDisplay();

const drawer = ref(false);
const newWorkspaceName = ref('');
const loading = ref(false);

const navItems = [
  { title: 'Inventory', to: '/inventory', icon: 'mdi-package-variant-closed' },
  { title: 'Customers', to: '/customers', icon: 'mdi-account-group' },
  { title: 'Vendors', to: '/vendors', icon: 'mdi-truck-delivery' },
  { title: 'Locations', to: '/storage-locations', icon: 'mdi-map-marker' },
];

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

const createWorkspace = async () => {
    if (!newWorkspaceName.value) return;
    
    loading.value = true;
    try {
        await authStore.createWorkspace(newWorkspaceName.value);
        authStore.showCreateWorkspaceDialog = false;
        newWorkspaceName.value = '';
    } finally {
        loading.value = false;
    }
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

        <div class="px-4 mb-4" v-if="authStore.loggedIn">
            <WorkspaceSelector class="w-100" />
        </div>
        
        <v-divider></v-divider>
        
        <v-list-item
          v-for="item in navItems"
          :key="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          color="primary"
        ></v-list-item>

        <v-divider class="my-2"></v-divider>

        <v-list-item
          to="/profile"
          prepend-icon="mdi-account-cog-outline"
          title="Profile Settings"
        ></v-list-item>

        <v-list-item
          @click="logout"
          prepend-icon="mdi-logout"
          title="Logout"
          color="error"
        ></v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar color="surface" elevation="0" border="b">
      <v-app-bar-nav-icon v-if="mobile" @click="drawer = !drawer"></v-app-bar-nav-icon>
      
      <div class="d-flex flex-column ml-2">
          <v-app-bar-title class="text-h6 text-md-h5 font-weight-black text-primary line-height-1">
            Pintventory
          </v-app-bar-title>
          <div v-if="authStore.currentWorkspace" class="text-caption font-weight-bold text-grey-darken-1 d-flex align-center mt-n1">
            <v-icon size="12" class="mr-1">mdi-briefcase-outline</v-icon>
            {{ authStore.currentWorkspace.name }}
          </div>
      </div>
      
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

      <!-- User & Workspace Menu -->
      <v-menu v-if="authStore.user" :close-on-content-click="false" min-width="240px">
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" variant="text" class="text-none px-2 ml-2">
            <div class="d-flex align-center gap-2">
              <v-avatar size="32" color="primary-lighten-4">
                <v-img v-if="authStore.user.avatar" :src="authStore.user.avatar"></v-img>
                <v-icon v-else color="primary">mdi-account</v-icon>
              </v-avatar>
              <span v-if="!mobile" class="text-body-2 font-weight-medium">{{ authStore.user.name }}</span>
              <v-icon size="small">mdi-chevron-down</v-icon>
            </div>
          </v-btn>
        </template>

        <v-list elevation="10" border rounded="lg" class="pa-2">
          <v-list-item
            :prepend-avatar="authStore.user.avatar"
            :title="authStore.user.name"
            :subtitle="authStore.user.email"
            class="mb-2"
          >
            <template v-slot:prepend v-if="!authStore.user.avatar">
              <v-avatar color="primary-lighten-4">
                <v-icon color="primary">mdi-account</v-icon>
              </v-avatar>
            </template>
          </v-list-item>

          <v-divider class="mb-2"></v-divider>

          <div class="px-2 mb-2">
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1 ml-1">
              Active Workspace
            </div>
            <WorkspaceSelector class="w-100" />
          </div>

          <v-divider class="my-2"></v-divider>

          <v-list-item
            to="/profile"
            prepend-icon="mdi-account-cog-outline"
            title="Profile Settings"
            density="compact"
          ></v-list-item>

          <v-list-item
            @click="logout"
            prepend-icon="mdi-logout"
            title="Logout"
            color="error"
            density="compact"
          ></v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>
    
    <v-main :class="(mobile ? 'pa-4' : 'pa-8 pa-md-16') + ' pt-16'" style="overflow-y: auto; overflow-x: hidden;">
      <v-container fluid class="max-width-1200 mx-auto pa-0 mt-4">
        <slot></slot>
      </v-container>
    </v-main>

    <!-- Global Workspace Creation Dialog -->
    <v-dialog v-model="authStore.showCreateWorkspaceDialog" max-width="400" persistent>
      <v-card title="Create Workspace">
        <v-card-text>
          <v-text-field
            v-model="newWorkspaceName"
            label="Workspace Name"
            placeholder="e.g. My Shop, Personal Collection"
            autofocus
            @keyup.enter="createWorkspace"
            :disabled="loading"
            variant="outlined"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text="Cancel" @click="authStore.showCreateWorkspaceDialog = false" :disabled="loading"></v-btn>
          <v-btn 
            color="primary" 
            text="Create" 
            @click="createWorkspace" 
            :loading="loading"
            :disabled="!newWorkspaceName"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<style scoped>
.max-width-1200 {
  max-width: 1200px;
}
</style>