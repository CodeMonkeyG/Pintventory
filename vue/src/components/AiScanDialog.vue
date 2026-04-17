<script setup>
import { ref, watch, computed } from 'vue';
import { useDisplay } from 'vuetify';
import SingleItemScanner from './SingleItemScanner.vue';
import MultiItemScanner from './MultiItemScanner.vue';
import PhotoGallery from './PhotoGallery.vue';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['update:show', 'save']);

const { mobile } = useDisplay();
const tab = ref('single');
const singleScannerRef = ref(null);
const multiScannerRef = ref(null);

const showGallery = ref(false);
const galleryPhotos = computed(() => {
  const currentScanner = tab.value === 'single' ? singleScannerRef.value : multiScannerRef.value;
  if (currentScanner?.capturedPhoto) {
    return [{ url: currentScanner.capturedPhoto.url }];
  }
  return [];
});

const hasCapturedPhoto = computed(() => {
  const currentScanner = tab.value === 'single' ? singleScannerRef.value : multiScannerRef.value;
  return !!currentScanner?.capturedPhoto;
});

const isAnalyzingOrSaving = computed(() => {
  const currentScanner = tab.value === 'single' ? singleScannerRef.value : multiScannerRef.value;
  return currentScanner?.isAnalyzing || currentScanner?.isSaving;
});

const close = () => {
    emit('update:show', false);
};

const handleDiscard = () => {
  const currentScanner = tab.value === 'single' ? singleScannerRef.value : multiScannerRef.value;
  if (currentScanner) {
    currentScanner.reset();
  }
};

const handleAdd = () => {
  const currentScanner = tab.value === 'single' ? singleScannerRef.value : multiScannerRef.value;
  if (currentScanner) {
    if (tab.value === 'single') {
      currentScanner.saveToInventory();
    } else {
      currentScanner.saveSelected();
    }
  }
};

const handleSave = async (payload, photos) => {
    emit('save', payload, photos);
};

// Reset tab when dialog opens
watch(() => props.show, (newVal) => {
  if (newVal) tab.value = 'single';
});
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '800'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'" class="d-flex flex-column" :style="mobile ? 'height: 100dvh;' : 'max-height: 90vh;'">
      <v-toolbar color="primary" :density="mobile ? 'comfortable' : 'default'">
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>AI Item Identification</v-toolbar-title>
        
        <v-spacer></v-spacer>

        <template v-if="hasCapturedPhoto">
          <v-btn 
            variant="text" 
            color="white" 
            @click="handleAdd" 
            prepend-icon="mdi-plus"
            :loading="isAnalyzingOrSaving"
            class="px-4"
          >
            Add
          </v-btn>
        </template>

        <template v-slot:extension>
          <v-tabs v-model="tab" grow color="white">
            <v-tab value="single" prepend-icon="mdi-camera">Single Item</v-tab>
            <v-tab value="multi" prepend-icon="mdi-ImageFilterCenterFocusStrongOutline">Multi Item</v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-card-text class="pa-0 flex-grow-1 overflow-y-auto">
        <v-window v-model="tab">
          <v-window-item value="single">
            <SingleItemScanner 
              ref="singleScannerRef"
              :active="tab === 'single' && show" 
              @save="handleSave" 
              @open-gallery="showGallery = true"
            />
          </v-window-item>
          
          <v-window-item value="multi">
            <MultiItemScanner 
              ref="multiScannerRef"
              :active="tab === 'multi' && show" 
              @close="close" 
              @open-gallery="showGallery = true"
            />
          </v-window-item>
        </v-window>
      </v-card-text>
    </v-card>
  </v-dialog>

  <PhotoGallery
    v-if="showGallery"
    :show="showGallery"
    :photos="galleryPhotos"
    :startIndex="0"
    @close="showGallery = false"
  />
</template>
