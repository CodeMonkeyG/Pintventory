<script setup>
import { ref, computed } from 'vue';
import { useDisplay } from 'vuetify';

const { mobile } = useDisplay();
const DEFAULT_SEARCH = 'goodwill salvation army thrift store consignment second hand';
const searchQuery = ref(DEFAULT_SEARCH);
const submittedQuery = ref(DEFAULT_SEARCH);
const userLocation = ref(null);
const isLocating = ref(false);
const iframeRef = ref(null);

const iframeSrc = computed(() => {
  if (!submittedQuery.value) return '';
  let url = `https://maps.google.com/maps?q=${encodeURIComponent(submittedQuery.value)}`;
  
  // Add zoom level (z=13 is approx 5-10 mile radius view)
  url += '&z=13';
  
  // If we have user location, we can try to center or influence the search
  if (userLocation.value) {
    url += `&ll=${userLocation.value.lat},${userLocation.value.lng}`;
  }
  
  url += '&output=embed';
  return url;
});

const requestLocation = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser');
    return;
  }

  isLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      userLocation.value = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      // Re-trigger search with location context if query is default
      if (submittedQuery.value === DEFAULT_SEARCH) {
        handleSearch();
      }
      isLocating.value = false;
    },
    (error) => {
      console.error('Error getting location:', error);
      alert('Could not get your location. The search will use your IP address instead.');
      isLocating.value = false;
    },
    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
  );
};

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    submittedQuery.value = searchQuery.value.trim();
  }
};

const clearSearch = () => {
  searchQuery.value = '';
  submittedQuery.value = '';
};

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
  <div class="locations-search-view h-100 d-flex flex-column">
    <div class="mb-6">
      <h1 :class="mobile ? 'text-h4' : 'text-h3'" class="mb-2 font-weight-black text-primary">Map Search</h1>
      <p class="text-body-1 text-grey-darken-1">Find thrift stores, consignment shops, and second-hand markets near you.</p>
    </div>

    <v-card variant="outlined" class="mb-6 rounded-xl bg-surface" border>
      <v-card-text class="pa-4 pa-md-6">
        <div class="d-flex flex-column gap-3">
          <v-text-field
            v-model="searchQuery"
            label="Search External Locations"
            placeholder="e.g. Austin Antique Mall, Round Top Flea Market"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            @click:clear="clearSearch"
            @keyup.enter="handleSearch"
          >
            <template v-slot:append-inner>
              <v-btn
                color="primary"
                variant="flat"
                class="text-none ml-2"
                rounded="lg"
                @click="handleSearch"
                :disabled="!searchQuery"
              >
                Search
              </v-btn>
            </template>
          </v-text-field>
          
          <div class="d-flex align-center">
            <v-btn
              variant="tonal"
              size="small"
              :color="userLocation ? 'success' : 'primary'"
              :prepend-icon="userLocation ? 'mdi-map-marker-check' : 'mdi-crosshairs-gps'"
              @click="requestLocation"
              :loading="isLocating"
              class="text-none"
            >
              {{ userLocation ? 'Location Active' : 'Use My Current Location' }}
            </v-btn>
            <v-spacer />
            <span v-if="userLocation" class="text-caption text-grey">
              Accuracy improved by GPS coordinates
            </span>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <div class="flex-grow-1 border rounded-xl overflow-hidden bg-grey-lighten-4 position-relative" style="min-height: 700px;">
      <template v-if="iframeSrc">
        <iframe
          ref="iframeRef"
          :src="iframeSrc"
          class="w-100 h-100 border-0"
          allow="geolocation"
        ></iframe>
      </template>
      <div v-else class="fill-height d-flex flex-column align-center justify-center text-grey-darken-1 pa-12 text-center">
        <v-icon size="64" class="mb-4 opacity-20">mdi-map-search-outline</v-icon>
        <div class="text-h6 font-weight-bold">Ready to Search</div>
        <p class="text-body-2 max-width-400">Enter a location or event name above to search Google directly within Pintventory.</p>
      </div>
    </div>

    <div class="d-flex justify-center mt-8 mb-4">
      <v-btn
        variant="text"
        size="small"
        color="grey-darken-1"
        prepend-icon="mdi-arrow-up"
        @click="scrollToTop"
        class="text-none"
      >
        Back to Top
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.max-width-400 {
  max-width: 400px;
}
.opacity-20 {
  opacity: 0.2;
}
.gap-3 {
  gap: 12px;
}
.locations-search-view {
  min-height: calc(100vh - 200px);
}
</style>
