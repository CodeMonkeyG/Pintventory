<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import MainLayout from '../layouts/MainLayout.vue';
import api from '../axios';

const authStore = useAuthStore();
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
      <h1 class="text-h3">Profile</h1>
    </div>

    <v-container max-width="600">
      <v-alert v-if="message" :type="message.includes('success') ? 'success' : 'error'" class="mb-6">
        {{ message }}
      </v-alert>

      <v-card class="mb-6">
        <v-card-title class="text-h6">User Details</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="profile.name"
            label="Name"
          />
          <v-text-field
            :value="profile.email"
            label="Email"
            disabled
            class="mt-3"
          />
          <v-text-field
            :value="profile.role"
            label="Role"
            disabled
            class="mt-3"
          />
        </v-card-text>
      </v-card>

      <v-card class="mb-6">
        <v-card-title class="text-h6">Preferences</v-card-title>
        <v-card-text>
          <v-select
            v-model="profile.preferences.currency"
            label="Currency (Default)"
            :items="[
              { value: 'USD', title: 'USD ($)' },
              { value: 'EUR', title: 'EUR (€)' },
              { value: 'GBP', title: 'GBP (£)' },
              { value: 'CAD', title: 'CAD (C$)' }
            ]"
          />
          <v-select
            v-model="profile.preferences.date_format"
            label="Date Format"
            :items="[
              { value: 'en-US', title: 'MM/DD/YYYY (US)' },
              { value: 'en-GB', title: 'DD/MM/YYYY (UK/EU)' },
              { value: 'iso', title: 'YYYY-MM-DD (ISO)' }
            ]"
            class="mt-3"
          />
          <v-select
            v-model="profile.preferences.tax_handling"
            label="Tax Handling"
            :items="[
              { value: 'exclude_tax', title: 'Exclude Tax (Add at checkout)' },
              { value: 'include_tax', title: 'Include Tax (VAT style)' }
            ]"
            class="mt-3"
          />
          <v-checkbox
            v-model="profile.preferences.low_stock_notification"
            label="Email me when stock is low"
            class="mt-3"
          />
        </v-card-text>
      </v-card>

      <v-btn
        color="primary"
        size="large"
        @click="saveProfile"
        :disabled="loading"
        :loading="loading"
      >
        {{ loading ? 'Saving...' : 'Save Profile' }}
      </v-btn>
    </v-container>
  </MainLayout>
</template>
