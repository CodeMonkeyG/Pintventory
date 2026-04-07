<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  show: Boolean,
  photos: {
    type: Array,
    default: () => []
  },
  startIndex: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['close', 'update:show']);

const currentIndex = ref(0);
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);

const resetZoom = () => {
  scale.value = 1;
  translateX.value = 0;
  translateY.value = 0;
};

const closeGallery = () => {
  resetZoom();
  emit('close');
  emit('update:show', false);
};

watch(() => props.show, (val) => {
  if (val) {
    currentIndex.value = props.startIndex;
    resetZoom();
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const next = () => {
  resetZoom();
  if (currentIndex.value < props.photos.length - 1) {
    currentIndex.value++;
  } else {
    currentIndex.value = 0;
  }
};

const prev = () => {
  resetZoom();
  if (currentIndex.value > 0) {
    currentIndex.value--;
  } else {
    currentIndex.value = props.photos.length - 1;
  }
};

const zoomIn = () => {
  scale.value = Math.min(scale.value + 0.5, 5);
};

const zoomOut = () => {
  scale.value = Math.max(scale.value - 0.5, 1);
  if (scale.value === 1) resetZoom();
};

const handleWheel = (e) => {
  if (e.deltaY < 0) {
    zoomIn();
  } else {
    zoomOut();
  }
};

const toggleZoom = () => {
  if (scale.value > 1) {
    resetZoom();
  } else {
    scale.value = 2.5;
  }
};

const startDrag = (e) => {
  if (scale.value <= 1) return;
  isDragging.value = true;
  startX.value = (e.clientX || e.touches[0].clientX) - translateX.value;
  startY.value = (e.clientY || e.touches[0].clientY) - translateY.value;
};

const onDrag = (e) => {
  if (!isDragging.value) return;
  translateX.value = (e.clientX || e.touches[0].clientX) - startX.value;
  translateY.value = (e.clientY || e.touches[0].clientY) - startY.value;
};

const stopDrag = () => {
  isDragging.value = false;
};

const handleKeydown = (e) => {
  if (!props.show) return;
  if (e.key === 'Escape') closeGallery();
  if (e.key === 'ArrowRight' && scale.value === 1) next();
  if (e.key === 'ArrowLeft' && scale.value === 1) prev();
  if (e.key === '+' || e.key === '=') zoomIn();
  if (e.key === '-' || e.key === '_') zoomOut();
};

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<template>
  <v-dialog :modelValue="show" @update:modelValue="$emit('update:show', $event)" fullscreen @click:outside="closeGallery">
    <v-card class="bg-black overflow-hidden h-screen w-screen d-flex flex-column">
      <!-- Toolbar -->
      <div class="d-flex justify-space-between align-center pa-4" style="z-index: 2001;">
        <div class="text-white">
          {{ currentIndex + 1 }} / {{ photos.length }}
          <span v-if="photos[currentIndex]?.caption" class="ml-4 opacity-70">{{ photos[currentIndex].caption }}</span>
        </div>
        
        <div class="d-flex gap-2">
          <v-btn icon variant="text" color="white" @click="zoomOut" :disabled="scale <= 1">
            <v-icon>mdi-minus</v-icon>
          </v-btn>
          <v-btn icon variant="text" color="white" @click="resetZoom" :disabled="scale === 1">
            <v-icon>mdi-magnify-minus-outline</v-icon>
          </v-btn>
          <v-btn icon variant="text" color="white" @click="zoomIn" :disabled="scale >= 5">
            <v-icon>mdi-plus</v-icon>
          </v-btn>
          <v-divider vertical class="mx-2 bg-white"></v-divider>
          <v-btn icon variant="tonal" color="white" @click="closeGallery">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>
      
      <!-- Main Content -->
      <div 
        class="flex-grow-1 position-relative d-flex justify-center align-center overflow-hidden"
        @wheel.prevent="handleWheel"
        @mousedown="startDrag"
        @mousemove="onDrag"
        @mouseup="stopDrag"
        @mouseleave="stopDrag"
        @touchstart="startDrag"
        @touchmove="onDrag"
        @touchend="stopDrag"
      >
        <v-btn 
          v-if="photos.length > 1 && scale === 1" 
          icon 
          variant="tonal"
          color="white"
          @click.stop="prev" 
          class="position-absolute" 
          style="left: 20px; z-index: 2001;"
        >
          <v-icon size="x-large">mdi-chevron-left</v-icon>
        </v-btn>
        
        <div 
          class="image-container d-flex justify-center align-center"
          :style="{
            cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default'
          }"
        >
          <img 
            v-if="photos[currentIndex]" 
            :src="photos[currentIndex].url" 
            :alt="photos[currentIndex].caption || 'Photo'" 
            @dblclick="toggleZoom"
            draggable="false"
            :style="{
              transform: `translate(${translateX}px, ${translateY}px) scale(${scale})`,
              transition: isDragging ? 'none' : 'transform 0.2s ease-out',
              maxWidth: '90vw',
              maxHeight: '80vh',
              objectFit: 'contain'
            }" 
          />
        </div>

        <v-btn 
          v-if="photos.length > 1 && scale === 1" 
          icon 
          variant="tonal"
          color="white"
          @click.stop="next" 
          class="position-absolute" 
          style="right: 20px; z-index: 2001;"
        >
          <v-icon size="x-large">mdi-chevron-right</v-icon>
        </v-btn>
      </div>

      <!-- Zoom Level Indicator (Temporary Overlay) -->
      <v-fade-transition>
        <div v-if="scale > 1" class="position-absolute text-white bg-black-opacity-50 pa-2 rounded-lg" style="bottom: 40px; left: 50%; transform: translateX(-50%); z-index: 2001;">
          {{ Math.round(scale * 100) }}% Zoom
        </div>
      </v-fade-transition>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.gap-2 {
  gap: 8px;
}
.bg-black-opacity-50 {
  background: rgba(0, 0, 0, 0.5);
}
.cursor-grab {
  cursor: grab;
}
.cursor-grabbing {
  cursor: grabbing;
}
.image-container {
  width: 100%;
  height: 100%;
  touch-action: none;
}
.opacity-70 {
  opacity: 0.7;
}
</style>
