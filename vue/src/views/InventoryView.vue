<script setup>
import { onMounted, ref, computed } from 'vue';
import { useInventoryStore } from '../stores/inventory';
import MainLayout from '../layouts/MainLayout.vue';
import InventoryModal from '../components/InventoryModal.vue';
import PhotoGallery from '../components/PhotoGallery.vue';
import api from '../axios';

const store = useInventoryStore();
const showModal = ref(false);
const selectedItem = ref(null);

// Gallery State
const showGallery = ref(false);
const galleryPhotos = ref([]);
const galleryIndex = ref(0);

onMounted(() => {
  store.fetchItems();
});

const search = computed({
  get: () => store.filters.search,
  set: (val) => store.setFilter('search', val)
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
      const response = await api.get(`/inventory-items/${item.id}`);
      selectedItem.value = response.data;
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
    <div class="header">
      <h1>Inventory</h1>
      <button class="btn-primary" @click="openCreateModal">Add Item</button>
    </div>

    <div class="filters">
      <input v-model="search" placeholder="Search by title or SKU..." class="search-input" />
      
      <select v-model="statusFilter" class="filter-select">
        <option value="">All Statuses</option>
        <option value="in_stock">In Stock</option>
        <option value="low_stock">Low Stock</option>
        <option value="out_of_stock">Out of Stock</option>
        <option value="archived">Archived</option>
      </select>
      
      <label class="checkbox-label">
        <input type="checkbox" v-model="lowStockFilter" />
        Low Stock Only
      </label>
    </div>

    <div class="table-container">
      <table class="inventory-table">
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
            <td colspan="6" class="text-center">Loading...</td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td colspan="6" class="text-center">No items found.</td>
          </tr>
          <tr v-for="item in store.items" :key="item.id">
            <td>
                <div v-if="item.photos && item.photos.length > 0" class="photo-thumbnail" @click="openGallery(item)">
                    <img :src="item.photos[0].url" alt="Item Photo" />
                    <div v-if="item.photos.length > 1" class="photo-count">+{{ item.photos.length - 1 }}</div>
                </div>
                <div v-else class="photo-placeholder"></div> 
            </td>
            <td>
              <div class="title">{{ item.title }}</div>
              <div class="sku">{{ item.sku }}</div>
            </td>
            <td>
              <span :class="['status-badge', item.status]">{{ item.status.replace('_', ' ') }}</span>
            </td>
            <td>
                {{ item.quantity_on_hand }} {{ item.unit }}
            </td>
            <td>{{ formatDate(item.updated_at) }}</td>
            <td>
              <button class="action-btn" @click="openEditModal(item)">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="pagination" v-if="store.pagination.last_page > 1">
        <button 
            :disabled="store.pagination.current_page === 1" 
            @click="store.fetchItems(store.pagination.current_page - 1)"
        >Previous</button>
        <span>Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}</span>
        <button 
            :disabled="store.pagination.current_page === store.pagination.last_page" 
            @click="store.fetchItems(store.pagination.current_page + 1)"
        >Next</button>
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

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.btn-primary {
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  border: 1px solid black;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  align-items: center;
  background: white;
}

.search-input {
  flex: 1;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid black;
}

.filter-select {
  padding: 8px;
  border-radius: 4px;
  border: 1px solid black;
}

.table-container {
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  overflow: hidden;
  background: white;
}

.inventory-table {
  width: 100%;
  border-collapse: collapse;
}

.inventory-table th, .inventory-table td {
  padding: 12px 15px;
  text-align: left;
}

.inventory-table th {
  font-weight: 600;
  font-size: 0.9em;
  text-transform: uppercase;
}

.photo-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  border: 1px dashed black;
}

.photo-thumbnail {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  overflow: hidden;
  position: relative;
  cursor: pointer;
}

.photo-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-count {
    position: absolute;
    bottom: 0;
    right: 0;
    background: rgba(0,0,0,0.6);
    color: white;
    font-size: 9px;
    padding: 1px 3px;
    border-top-left-radius: 3px;
}

.title {
  font-weight: 600;
}

.sku {
  font-size: 0.85em;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 600;
  text-transform: capitalize;
  border: 1px solid black;
}

.pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
    align-items: center;
}

.pagination button {
    padding: 5px 10px;
    cursor: pointer;
    border: 1px solid black;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-btn {
    background: none;
    border: none;
    text-decoration: underline;
    cursor: pointer;
}
</style>
