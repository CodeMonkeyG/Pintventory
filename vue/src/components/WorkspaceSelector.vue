<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const workspaces = computed(() => auth.workspaces);
const currentWorkspace = computed(() => auth.currentWorkspace);

const selectWorkspace = (workspaceId) => {
    if (currentWorkspace.value?.id === workspaceId) return;
    auth.switchWorkspace(workspaceId);
};
</script>

<template>
  <v-menu v-if="auth.loggedIn">
    <template v-slot:activator="{ props }">
      <v-btn
        variant="tonal"
        class="text-none"
        color="primary"
        v-bind="props"
        prepend-icon="mdi-briefcase-outline"
        append-icon="mdi-chevron-down"
      >
        {{ currentWorkspace?.name || 'Select Workspace' }}
      </v-btn>
    </template>

    <v-list density="comfortable" width="250">
      <v-list-subheader>YOUR WORKSPACES</v-list-subheader>
      
      <v-list-item
        v-for="workspace in workspaces"
        :key="workspace.id"
        :value="workspace.id"
        @click="selectWorkspace(workspace.id)"
        :active="currentWorkspace?.id === workspace.id"
        color="primary"
      >
        <template v-slot:prepend>
          <v-icon :icon="currentWorkspace?.id === workspace.id ? 'mdi-briefcase' : 'mdi-briefcase-outline'"></v-icon>
        </template>
        <v-list-item-title>{{ workspace.name }}</v-list-item-title>
        <template v-slot:append>
            <v-chip size="x-small" variant="tonal" class="text-uppercase">{{ workspace.pivot?.role || 'member' }}</v-chip>
        </template>
      </v-list-item>

      <v-divider class="my-2"></v-divider>
      
      <v-list-item prepend-icon="mdi-plus" title="Create Workspace" disabled>
        <template v-slot:append>
            <v-chip size="x-small" color="info">COMING SOON</v-chip>
        </template>
      </v-list-item>
    </v-list>
  </v-menu>
</template>
