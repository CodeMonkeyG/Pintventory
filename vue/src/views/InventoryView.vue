<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { useInventoryStore } from '../stores/inventory';
import MainLayout from '../layouts/MainLayout.vue';
import InventoryModal from '../components/InventoryModal.vue';
import PhotoGallery from '../components/PhotoGallery.vue';
import api from '../axios';
import { debounce } from '../utils/helpers';

const store = useInventoryStore();
const showModal = ref(false);
const selectedItem = ref(null);

// Gallery State
const showGallery = ref(false);
const galleryPhotos = ref([]);
const galleryIndex = ref(0);

// Local search value for debouncing
const searchInput = ref('');

onMounted(() => {
  store.fetchItems();
});

// Debounced search handler
const debouncedSearch = debounce((val) => {
  store.setFilter('search', val);
}, 300);

watch(searchInput, (val) => {
  debouncedSearch(val);
});

const statusFilter = computed({
  get: () => store.filters.status,
  set: (val) => store.setFilter('status', val)
});

const lowStockFilter = computed({
  get: () => store.filters.low_stock,
  set: (val) => store.setFilter('low_stock', val)
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
};

const openCreateModal = () => {
  selectedItem.value = null;
  showModal.value = true;
};

const openEditModal = async (item) => {
  try {
      selectedItem.value = await store.fetchItemDetail(item.id);
      showModal.value = true;
  } catch (error) {
      console.error("Failed to fetch item details", error);
      alert("Could not load item details.");
  }
};

const handleSave = async (itemData, newPhotos = []) => {
  // Transaction save (itemData is null)
  if (!itemData) {
      if (selectedItem.value) {
          await openEditModal(selectedItem.value);
      }
      await store.fetchItems(store.pagination.current_page);
      return;
  }

  try {
    let itemId;
    if (selectedItem.value) {
      itemId = selectedItem.value.id;
      await store.updateItem(itemId, itemData);
    } else {
      const newItem = await store.createItem(itemData);
      itemId = newItem.id;
    }

    if (newPhotos.length > 0) {
        for (const file of newPhotos) {
            await store.uploadPhoto(itemId, file);
        }
        await store.fetchItems(store.pagination.current_page);
    }

    showModal.value = false;
  } catch (error) {
    alert('Failed to save item: ' + (error.response?.data?.message || error.message));
  }
};

const handleDeletePhoto = async (photoId) => {
    if (!confirm('Are you sure you want to delete this photo?')) return;
    try {
        await store.deletePhoto(photoId);
        await store.fetchItems(store.pagination.current_page);
        
        if (selectedItem.value && selectedItem.value.photos) {
             selectedItem.value.photos = selectedItem.value.photos.filter(p => p.id !== photoId);
        }
    } catch (error) {
        alert('Failed to delete photo: ' + (error.response?.data?.message || error.message));
    }
};

const openGallery = (item, index = 0) => {
    if (item.photos && item.photos.length > 0) {
        galleryPhotos.value = item.photos;
        galleryIndex.value = index;
        showGallery.value = true;
    }
};
</script>

<template>
  <MainLayout>
    <div class="d-flex justify-space-between align-center mb-6">
      <h1 class="text-h3">Inventory</h1>
      <v-btn color="primary" @click="openCreateModal">
        <v-icon left>mdi-plus</v-icon>
        Add Item
      </v-btn>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <div class="d-flex gap-3 align-center flex-wrap">
          <v-text-field
            v-model="searchInput"
            placeholder="Search by title or SKU..."
            prepend-icon="mdi-magnify"
            hide-details
            class="flex-grow-1"
            max-width="400"
          />
          
          <v-select
            v-model="statusFilter"
            label="Status"
            :items="['', 'in_stock', 'low_stock', 'out_of_stock', 'archived']"
            hide-details
            max-width="150"
            class="flex-grow-1"
          />
          
          <v-checkbox
            v-model="lowStockFilter"
            label="Low Stock Only"
            hide-details
            class="flex-grow-1"
          />
        </div>
      </v-card-text>
    </v-card>

    <v-card>
      <v-table>
        <thead>
          <tr>
            <th>Photo</th>
            <th>Title / SKU</th>
            <th>Status</th>
            <th>On Hand</th>
            <th>Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="6" class="text-center py-8">
              <v-progress-circular indeterminate />
            </td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td colspan="6" class="text-center py-8 text-grey">
              No items found.
            </td>
          </tr>
          <tr v-for="item in store.items" :key="item.id">
            <td>
              <div v-if="item.photos && item.photos.length > 0" class="position-relative" style="cursor: pointer; width: 40px; height: 40px; overflow: hidden; border-radius: 4px;" @click="openGallery(item)">
                <img :src="item.photos[0].url" alt="Item Photo" style="width: 100%; height: 100%; object-fit: cover;" />
                <div v-if="item.photos.length > 1" class="position-absolute text-white text-caption" style="bottom: 0; right: 0; background: rgba(0,0,0,0.6); padding: 1px 3px; border-top-left-radius: 3px;">
                  +{{ item.photos.length - 1 }}
                </div>
              </div>
              <div v-else style="width: 40px; height: 40px; border-radius: 4px; border: 1px dashed #999;" />
            </td>
            <td>
              <div class="font-weight-600">{{ item.title }}</div>
              <div class="text-caption text-grey">{{ item.sku }}</div>
            </td>
            <td>
              <v-chip
                :color="item.status === 'in_stock' ? 'success' : item.status === 'low_stock' ? 'warning' : 'error'"
                size="small"
              >
                {{ item.status.replace('_', ' ') }}
              </v-chip>
            </td>
            <td>
              {{ item.quantity_on_hand }} {{ item.unit }}
            </td>
            <td>{{ formatDate(item.updated_at) }}</td>
            <td>
              <v-btn size="x-small" variant="text" @click="openEditModal(item)">
                Edit
              </v-btn>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
    
    <div v-if="store.pagination.last_page > 1" class="d-flex justify-center align-center gap-4 mt-6">
      <v-btn
        :disabled="store.pagination.current_page === 1"
        @click="store.fetchItems(store.pagination.current_page - 1)"
      >
        Previous
      </v-btn>
      <span class="text-body2">
        Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}
      </span>
      <v-btn
        :disabled="store.pagination.current_page === store.pagination.last_page"
        @click="store.fetchItems(store.pagination.current_page + 1)"
      >
        Next
      </v-btn>
    </div>

    <InventoryModal 
      :show="showModal" 
      :item="selectedItem" 
      @close="showModal = false" 
      @save="handleSave"
      @delete-photo="handleDeletePhoto"
    />

    <PhotoGallery
      :show="showGallery"
      :photos="galleryPhotos"
      :startIndex="galleryIndex"
      @close="showGallery = false"
    />
  </MainLayout>
</template>
