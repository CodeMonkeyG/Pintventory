<script setup>
import { onMounted, ref, computed } from 'vue';
import { useInventoryStore } from '../stores/inventory';
import MainLayout from '../layouts/MainLayout.vue';
import InventoryModal from '../components/InventoryModal.vue';

const store = useInventoryStore();
const showModal = ref(false);
const selectedItem = ref(null);

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

const openEditModal = (item) => {
  selectedItem.value = { ...item }; // Copy to avoid direct mutation
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
    alert('Failed to save item: ' + (error.response?.data?.message || error.message));
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
            <th>Last Cost</th>
            <th>Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="7" class="text-center">Loading...</td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td colspan="7" class="text-center">No items found.</td>
          </tr>
          <tr v-for="item in store.items" :key="item.id">
            <td>
                <div v-if="item.photos && item.photos.length > 0" class="photo-thumbnail">
                    <img :src="item.photos[0].url" alt="Item Photo" />
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
            <td>
                -
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
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  align-items: center;
}

.search-input {
  flex: 1;
  padding: 8px;
  border-radius: 4px;
}

.filter-select {
  padding: 8px;
  border-radius: 4px;
}

.table-container {
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  overflow: hidden;
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
}

.photo-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
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

/* Specific background colors for badges removed */
.status-badge.in_stock { }
.status-badge.low_stock { }
.status-badge.out_of_stock { }
.status-badge.archived { }

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
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>