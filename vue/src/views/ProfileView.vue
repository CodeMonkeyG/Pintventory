<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import MainLayout from '../layouts/MainLayout.vue';
import api from '../axios';
import { useDisplay } from 'vuetify';

const authStore = useAuthStore();
const { mobile } = useDisplay();
const loading = ref(false);
const message = ref('');

const profile = ref({
  name: '',
  email: '',
  role: '',
  preferences: {
    currency: 'USD',
    date_format: 'en-US',
    tax_handling: 'exclude_tax',
    low_stock_notification: false,
  }
});

onMounted(async () => {
  if (authStore.user) {
    populateForm(authStore.user);
  } else {
    await authStore.fetchUser();
    if (authStore.user) {
        populateForm(authStore.user);
    }
  }
});

const populateForm = (user) => {
    profile.value.name = user.name;
    profile.value.email = user.email;
    profile.value.role = user.role;
    profile.value.preferences = { ...profile.value.preferences, ...(user.preferences || {}) };
};

const saveProfile = async () => {
  loading.value = true;
  message.value = '';
  try {
    const response = await api.put('/user', {
        name: profile.value.name,
        preferences: profile.value.preferences
    });
    
    authStore.user = response.data;
    message.value = 'Profile updated successfully.';
  } catch (error) {
    message.value = 'Failed to update profile.';
    console.error(error);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <MainLayout>
    <div class="mb-6">
      <h1 :class="mobile ? 'text-h4' : 'text-h3'">Profile</h1>
    </div>

    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-alert v-if="message" :type="message.includes('success') ? 'success' : 'error'" class="mb-6" variant="tonal" closable @click:close="message = ''">
          {{ message }}
        </v-alert>

        <v-card class="mb-6" variant="outlined">
          <v-card-title class="text-h6 pb-0">User Details</v-card-title>
          <v-card-text class="pt-4">
            <v-row dense>
              <v-col cols="12">
                <v-text-field
                  v-model="profile.name"
                  label="Name"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  :value="profile.email"
                  label="Email"
                  disabled
                  variant="outlined"
                  density="compact"
                  hint="Email cannot be changed"
                  persistent-hint
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  :value="profile.role"
                  label="Role"
                  disabled
                  variant="outlined"
                  density="compact"
                  hint="Contact admin to change role"
                  persistent-hint
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card class="mb-6" variant="outlined">
          <v-card-title class="text-h6 pb-0">Preferences</v-card-title>
          <v-card-text class="pt-4">
            <v-row dense>
              <v-col cols="12">
                <v-select
                  v-model="profile.preferences.currency"
                  label="Default Currency"
                  :items="[
                    { value: 'USD', title: 'USD ($)' },
                    { value: 'EUR', title: 'EUR (€)' },
                    { value: 'GBP', title: 'GBP (£)' },
                    { value: 'CAD', title: 'CAD (C$)' }
                  ]"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="profile.preferences.date_format"
                  label="Date Format"
                  :items="[
                    { value: 'en-US', title: 'MM/DD/YYYY (US)' },
                    { value: 'en-GB', title: 'DD/MM/YYYY (UK/EU)' },
                    { value: 'iso', title: 'YYYY-MM-DD (ISO)' }
                  ]"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="profile.preferences.tax_handling"
                  label="Tax Handling"
                  :items="[
                    { value: 'exclude_tax', title: 'Exclude Tax (Add at checkout)' },
                    { value: 'include_tax', title: 'Include Tax (VAT style)' }
                  ]"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12">
                <v-checkbox
                  v-model="profile.preferences.low_stock_notification"
                  label="Email me when stock is low"
                  density="compact"
                  hide-details
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <div class="d-flex justify-end">
          <v-btn
            color="primary"
            size="large"
            @click="saveProfile"
            :disabled="loading"
            :loading="loading"
            :block="mobile"
            elevation="1"
          >
            Save Profile
          </v-btn>
        </div>
      </v-col>
    </v-row>
  </MainLayout>
</template>
