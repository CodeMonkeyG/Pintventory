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

const closeGallery = () => {
  emit('close');
  emit('update:show', false);
};

watch(() => props.show, (val) => {
  if (val) {
    currentIndex.value = props.startIndex;
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const next = () => {
  if (currentIndex.value < props.photos.length - 1) {
    currentIndex.value++;
  } else {
    currentIndex.value = 0;
  }
};

const prev = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  } else {
    currentIndex.value = props.photos.length - 1;
  }
};

const handleKeydown = (e) => {
  if (!props.show) return;
  if (e.key === 'Escape') closeGallery();
  if (e.key === 'ArrowRight') next();
  if (e.key === 'ArrowLeft') prev();
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
    <v-card class="bg-black">
      <v-btn icon @click="closeGallery" class="position-absolute" style="top: 20px; right: 20px; z-index: 2001;">
        <v-icon>mdi-close</v-icon>
      </v-btn>
      
      <div class="d-flex justify-center align-center h-screen">
        <v-btn v-if="photos.length > 1" icon @click.stop="prev" class="position-absolute" style="left: 20px; top: 50%; transform: translateY(-50%); z-index: 2001;">
          <v-icon size="x-large">mdi-chevron-left</v-icon>
        </v-btn>
        
        <div class="text-center">
          <img v-if="photos[currentIndex]" :src="photos[currentIndex].url" :alt="photos[currentIndex].caption || 'Photo'" style="max-width: 90vw; max-height: 85vh; object-fit: contain;" />
          <div v-if="photos[currentIndex] && photos[currentIndex].caption" class="text-white mt-3 text-h6">
            {{ photos[currentIndex].caption }}
          </div>
          <div class="text-grey mt-2">
            {{ currentIndex + 1 }} / {{ photos.length }}
          </div>
        </div>

        <v-btn v-if="photos.length > 1" icon @click.stop="next" class="position-absolute" style="right: 20px; top: 50%; transform: translateY(-50%); z-index: 2001;">
          <v-icon size="x-large">mdi-chevron-right</v-icon>
        </v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>
