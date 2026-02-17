<script setup>
import { onMounted, ref, computed } from 'vue';
import { useVendorStore } from '../stores/vendors';
import MainLayout from '../layouts/MainLayout.vue';
import VendorModal from '../components/VendorModal.vue';

const store = useVendorStore();
const showModal = ref(false);
const selectedItem = ref(null);

onMounted(() => {
  store.fetchItems();
});

const search = computed({
  get: () => store.filters.search,
  set: (val) => store.setFilter('search', val)
});

const isPreferredFilter = computed({
  get: () => store.filters.is_preferred,
  set: (val) => store.setFilter('is_preferred', val)
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
    alert('Failed to save vendor: ' + (error.response?.data?.message || error.message));
  }
};

const handleDelete = async (id) => {
    if (!confirm('Are you sure?')) return;
    try {
        await store.deleteItem(id);
    } catch (error) {
        alert('Failed to delete vendor');
    }
};
</script>

<template>
  <MainLayout>
    <div class="header">
      <h1>Vendors</h1>
      <button class="btn-primary" @click="openCreateModal">Add Vendor</button>
    </div>

    <div class="filters">
      <div class="filter-row">
          <input v-model="search" placeholder="Search vendors..." class="search-input" />
          <div class="checkbox-group">
              <input type="checkbox" id="prefFilter" v-model="isPreferredFilter" />
              <label for="prefFilter">Preferred Only</label>
          </div>
      </div>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Preferred</th>
            <th>Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="6" class="text-center">Loading...</td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td colspan="6" class="text-center">No vendors found.</td>
          </tr>
          <tr v-for="item in store.items" :key="item.id">
            <td>
              <div class="title">{{ item.name }}</div>
            </td>
            <td>{{ item.contact_name || '-' }}</td>
            <td>{{ item.email || '-' }}</td>
            <td>{{ item.is_preferred ? 'Yes' : 'No' }}</td>
            <td>{{ formatDate(item.updated_at) }}</td>
            <td>
              <button class="action-btn" @click="openEditModal(item)">Edit</button>
              <button class="action-btn delete" @click="handleDelete(item.id)">Delete</button>
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

    <VendorModal 
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
  border: 1px solid black;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.filters {
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  background: white;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: center;
}

.search-input {
  flex: 1;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid black;
}

.checkbox-group {
    display: flex;
    gap: 5px;
    align-items: center;
}

.table-container {
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  overflow: hidden;
  background: white;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th, .data-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  font-weight: 600;
  font-size: 0.9em;
  text-transform: uppercase;
}

.title {
  font-weight: 600;
}

.action-btn {
    border: none;
    background: none;
    cursor: pointer;
    text-decoration: underline;
    margin-right: 10px;
}

.action-btn.delete {
    font-weight: bold;
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
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>