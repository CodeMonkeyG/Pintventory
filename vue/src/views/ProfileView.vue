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
    // Merge defaults with saved preferences
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
    
    // Update store
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
    <div class="header">
      <h1>Profile</h1>
    </div>

    <div class="profile-container">
        <div v-if="message" class="alert">{{ message }}</div>

        <div class="form-section">
            <h2>User Details</h2>
            <div class="form-group">
                <label>Name</label>
                <input v-model="profile.name" type="text" />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input :value="profile.email" type="text" disabled class="disabled" />
            </div>
            <div class="form-group">
                <label>Role</label>
                <input :value="profile.role" type="text" disabled class="disabled" />
            </div>
        </div>

        <div class="form-section">
            <h2>Preferences</h2>
            <div class="form-group">
                <label>Currency (Default)</label>
                <select v-model="profile.preferences.currency">
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                    <option value="CAD">GBP (C$)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date Format</label>
                <select v-model="profile.preferences.date_format">
                    <option value="en-US">MM/DD/YYYY (US)</option>
                    <option value="en-GB">DD/MM/YYYY (UK/EU)</option>
                    <option value="iso">YYYY-MM-DD (ISO)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tax Handling</label>
                <select v-model="profile.preferences.tax_handling">
                    <option value="exclude_tax">Exclude Tax (Add at checkout)</option>
                    <option value="include_tax">Include Tax (VAT style)</option>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <input type="checkbox" id="notify" v-model="profile.preferences.low_stock_notification" />
                <label for="notify">Email me when stock is low</label>
            </div>
        </div>

        <button @click="saveProfile" :disabled="loading" class="btn-primary">
            {{ loading ? 'Saving...' : 'Save Profile' }}
        </button>
    </div>
  </MainLayout>
</template>

<style scoped>
.header {
  margin-bottom: 20px;
}

.profile-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    max-width: 600px;
}

.alert {
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid black;
    border-radius: 4px;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h2 {
    font-size: 1.2em;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.form-group {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    font-weight: 500;
}

input[type="text"], select {
    padding: 8px;
    border: 1px solid black;
    border-radius: 4px;
}

input.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    border-color: #ccc; /* Specifically visual cue for disabled */
}

.checkbox-group {
    flex-direction: row;
    align-items: center;
    gap: 10px;
}

.checkbox-group input {
    margin: 0;
}

.checkbox-group label {
    margin: 0;
    font-weight: normal;
}

.btn-primary {
    padding: 10px 20px;
    border: 1px solid black;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary:disabled {
    opacity: 0.5;
}
</style>
