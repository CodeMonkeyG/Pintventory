<script setup>
import { useDisplay } from 'vuetify';
import UnifiedScanner from './UnifiedScanner.vue';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['update:show', 'found-item', 'found-location']);

const { mobile } = useDisplay();

const close = () => {
    emit('update:show', false);
};

const handleFoundItem = (id) => {
    emit('found-item', id);
};

const handleFoundLocation = (locationId) => {
    emit('found-location', locationId);
};
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '600'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'" class="d-flex flex-column" :style="mobile ? 'height: 100dvh;' : 'max-height: 90vh;'">
      <v-toolbar color="primary" :density="mobile ? 'comfortable' : 'default'">
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Scan Barcode / QR</v-toolbar-title>
      </v-toolbar>

      <v-card-text class="pa-0 flex-grow-1 overflow-y-auto">
        <UnifiedScanner 
            :active="show" 
            @close="close" 
            @found-item="handleFoundItem"
            @found-location="handleFoundLocation"
        />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
