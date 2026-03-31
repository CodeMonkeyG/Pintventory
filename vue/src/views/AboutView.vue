<script setup>
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';

const router = useRouter();
const { mobile } = useDisplay();

const goBack = () => {
  router.push('/login');
};
</script>

<template>
  <v-container class="py-12">
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-btn
          variant="text"
          prepend-icon="mdi-arrow-left"
          @click="goBack"
          class="mb-6"
        >
          Back to Login
        </v-btn>

        <h1 :class="mobile ? 'text-h3' : 'text-h2'" class="font-weight-bold mb-6 text-primary">
          About Pintventory
        </h1>

        <p class="text-h6 mb-8 text-medium-emphasis">
          Pintventory is more than just a tracking tool—it's your digital assistant for managing and valuing inventory. 
          Designed for collectors, vintage resellers, and small businesses, it helps you spend less time on data entry and more time growing your business.
        </p>

        <v-row class="mb-8">
          <v-col cols="12" sm="6">
            <v-card variant="outlined" class="h-100 pa-4" border="primary">
              <v-icon color="primary" size="32" class="mb-4">mdi-sparkles</v-icon>
              <h3 class="text-h6 mb-2">AI-Powered Analysis</h3>
              <p class="text-body-2">
                Use Gemini 2.5 Flash to automatically identify items from photos, generating professional titles, 
                descriptions, and expert evaluations.
              </p>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6">
            <v-card variant="outlined" class="h-100 pa-4" border="primary">
              <v-icon color="primary" size="32" class="mb-4">mdi-shape-outline</v-icon>
              <h3 class="text-h6 mb-2">Smart Classification</h3>
              <p class="text-body-2">
                Automatically distinguish between "Unique" one-of-a-kind vintage treasures and "Standard" reorderable mass-produced stock.
              </p>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6">
            <v-card variant="outlined" class="h-100 pa-4" border="primary">
              <v-icon color="primary" size="32" class="mb-4">mdi-map-marker-radius</v-icon>
              <h3 class="text-h6 mb-2">Location Tracking</h3>
              <p class="text-body-2">
                Never lose an item again. Organize your inventory by specific shelves, bins, or warehouses with ease.
              </p>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6">
            <v-card variant="outlined" class="h-100 pa-4" border="primary">
              <v-icon color="primary" size="32" class="mb-4">mdi-palette</v-icon>
              <h3 class="text-h6 mb-2">Modern & Customizable</h3>
              <p class="text-body-2">
                A fully responsive, mobile-optimized experience with a variety of modern and retro themes to suit your style.
              </p>
            </v-card>
          </v-col>
        </v-row>

        <v-divider class="mb-8"></v-divider>

        <div class="text-center">
          <v-btn
            color="primary"
            size="large"
            @click="goBack"
            elevation="2"
            height="56"
            min-width="200"
          >
            Get Started Now
          </v-btn>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.v-card {
  transition: transform 0.2s;
}
.v-card:hover {
  transform: translateY(-4px);
}
</style>
