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

const emit = defineEmits(['close']);

const currentIndex = ref(0);

watch(() => props.show, (val) => {
  if (val) {
    currentIndex.value = props.startIndex;
    document.body.style.overflow = 'hidden'; // Prevent scrolling background
  } else {
    document.body.style.overflow = '';
  }
});

const next = () => {
  if (currentIndex.value < props.photos.length - 1) {
    currentIndex.value++;
  } else {
    currentIndex.value = 0; // Loop around
  }
};

const prev = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  } else {
    currentIndex.value = props.photos.length - 1; // Loop around
  }
};

const handleKeydown = (e) => {
  if (!props.show) return;
  if (e.key === 'Escape') emit('close');
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
  <div v-if="show" class="gallery-backdrop" @click.self="$emit('close')">
    <button class="close-btn" @click="$emit('close')">&times;</button>
    
    <button v-if="photos.length > 1" class="nav-btn prev" @click.stop="prev">&lsaquo;</button>
    
    <div class="gallery-content">
      <img v-if="photos[currentIndex]" :src="photos[currentIndex].url" :alt="photos[currentIndex].caption || 'Photo'" />
      <div v-if="photos[currentIndex] && photos[currentIndex].caption" class="caption">
        {{ photos[currentIndex].caption }}
      </div>
      <div class="counter">
        {{ currentIndex + 1 }} / {{ photos.length }}
      </div>
    </div>

    <button v-if="photos.length > 1" class="nav-btn next" @click.stop="next">&rsaquo;</button>
  </div>
</template>

<style scoped>
.gallery-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000; /* Higher than normal modals */
}

.gallery-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  align-items: center;
}

img {
  max-width: 100%;
  max-height: 85vh;
  object-fit: contain;
  border: 1px solid #333;
  box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

.caption {
  color: white;
  margin-top: 10px;
  font-size: 1.1em;
  text-align: center;
}

.counter {
  color: #aaa;
  margin-top: 5px;
  font-size: 0.9em;
}

.close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: none;
  color: white;
  font-size: 40px;
  cursor: pointer;
  z-index: 2001;
  line-height: 1;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: white;
  font-size: 60px;
  cursor: pointer;
  padding: 0 20px;
  z-index: 2001;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.nav-btn:hover {
  opacity: 1;
}

.prev {
  left: 20px;
}

.next {
  right: 20px;
}
</style>
