<script setup>
import { onMounted, ref } from 'vue';
import { useLocationStore } from '../stores/locations';
import MainLayout from '../layouts/MainLayout.vue';
import StorageLocationModal from '../components/StorageLocationModal.vue';
import { useDisplay } from 'vuetify';

const store = useLocationStore();
const { mobile, smAndDown } = useDisplay();
const showModal = ref(false);
const selectedItem = ref(null);

onMounted(() => {
  store.fetchItems();
});

const openCreateModal = () => {
  selectedItem.value = null;
  showModal.value = true;
};

const openEditModal = (item) => {
  selectedItem.value = { ...item };
  showModal.value = true;
};

const handleSave = async (itemData) => {
  try {
    if (selectedItem.value) {
      await store.updateItem(selectedItem.value.id, itemData);
    } else {
      await store.createItem(itemData);
    }
    showModal.value = false;
  } catch (error) {
    alert('Failed to save location: ' + (error.response?.data?.message || error.message));
  }
};

const handleDelete = async (id) => {
    if (!confirm('Are you sure? Items linked to this location will have their location set to null.')) return;
    try {
        await store.deleteItem(id);
    } catch (error) {
        alert('Failed to delete location');
    }
};
</script>

<template>
  <MainLayout>
    <div :class="mobile ? 'd-flex flex-column gap-4' : 'd-flex justify-space-between align-center'" class="mb-6">
      <h1 :class="mobile ? 'text-h4' : 'text-h3'">Storage Locations</h1>
      <v-btn color="primary" @click="openCreateModal" :block="mobile" size="large" elevation="2">
        <v-icon prepend-icon>mdi-plus</v-icon>
        Add Location
      </v-btn>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <v-text-field
          v-model="store.filters.search"
          placeholder="Search locations..."
          prepend-inner-icon="mdi-magnify"
          hide-details
          variant="outlined"
          density="compact"
        />
      </v-card-text>
    </v-card>

    <div v-if="store.loading" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
    </div>

    <div v-else-if="store.filteredItems.length === 0" class="text-center py-12 text-grey">
      <v-icon size="64" class="mb-4">mdi-map-marker-outline</v-icon>
      <div class="text-h6">No locations found.</div>
    </div>

    <template v-else>
      <!-- Mobile View: Cards -->
      <v-row v-if="smAndDown">
        <v-col v-for="item in store.filteredItems" :key="item.id" cols="12">
          <v-card variant="outlined" class="pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <div>
                <div class="text-h6 font-weight-bold">{{ item.name }}</div>
                <div class="text-body-2 text-grey">{{ item.description || 'No description' }}</div>
              </div>
            </div>
            
            <v-divider class="my-2"></v-divider>
            
            <div class="d-flex justify-end gap-2">
              <v-btn size="small" variant="tonal" @click="openEditModal(item)">Edit</v-btn>
              <v-btn size="small" variant="tonal" color="error" @click="handleDelete(item.id)">Delete</v-btn>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Desktop View: Table -->
      <v-card v-else>
        <v-table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in store.filteredItems" :key="item.id">
              <td>
                <div class="font-weight-bold">{{ item.name }}</div>
              </td>
              <td>{{ item.description || '-' }}</td>
              <td>{{ new Date(item.created_at).toLocaleDateString() }}</td>
              <td>
                <v-btn size="small" variant="text" icon="mdi-pencil" @click="openEditModal(item)" class="mr-1"></v-btn>
                <v-btn size="small" variant="text" icon="mdi-delete" color="error" @click="handleDelete(item.id)"></v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </template>

    <StorageLocationModal 
      :show="showModal" 
      :item="selectedItem" 
      @close="showModal = false" 
      @save="handleSave" 
    />
  </MainLayout>
</template>
