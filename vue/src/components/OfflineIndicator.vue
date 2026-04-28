<script setup>
import { inject, watch, ref } from 'vue';

const isOnline = inject('isOnline');
const show = ref(false);
const message = ref('');
const color = ref('error');
const icon = ref('mdi-cloud-off');

watch(isOnline, (online) => {
  if (!online) {
    message.value = 'You are offline. Changes will be saved locally.';
    color.value = 'error';
    icon.value = 'mdi-cloud-off';
    show.value = true;
  } else {
    message.value = 'You are back online! Syncing...';
    color.value = 'success';
    icon.value = 'mdi-cloud-sync';
    show.value = true;
    setTimeout(() => {
      show.value = false;
    }, 3000);
  }
}, { immediate: false });
</script>

<template>
  <v-snackbar
    v-model="show"
    :color="color"
    :timeout="isOnline ? 3000 : -1"
    location="top"
  >
    <div class="d-flex align-center">
      <v-icon class="mr-3">{{ icon }}</v-icon>
      {{ message }}
    </div>
    
    <template v-slot:actions v-if="!isOnline">
      <v-btn
        variant="text"
        @click="show = false"
      >
        Dismiss
      </v-btn>
    </template>
  </v-snackbar>
</template>
