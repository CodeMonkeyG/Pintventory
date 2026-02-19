<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { useCustomerStore } from '../stores/customers';
import MainLayout from '../layouts/MainLayout.vue';
import CustomerModal from '../components/CustomerModal.vue';
import api from '../axios';
import { debounce } from '../utils/helpers';

const store = useCustomerStore();
const showModal = ref(false);
const selectedItem = ref(null);

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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};

const openCreateModal = () => {
  selectedItem.value = null;
  showModal.value = true;
};

const openEditModal = async (item) => {
  try {
      const response = await api.get(`/customers/${item.id}`);
      selectedItem.value = response.data;
      showModal.value = true;
  } catch (error) {
      alert("Failed to load customer details");
  }
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
    alert('Failed to save customer: ' + (error.response?.data?.message || error.message));
  }
};

const handleDelete = async (id) => {
    if (!confirm('Are you sure?')) return;
    try {
        await store.deleteItem(id);
    } catch (error) {
        alert('Failed to delete customer');
    }
};
</script>

<template>
  <MainLayout>
    <div class="d-flex justify-space-between align-center mb-6">
      <h1 class="text-h3">Customers</h1>
      <v-btn color="primary" @click="openCreateModal">
        <v-icon left>mdi-plus</v-icon>
        Add Customer
      </v-btn>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <v-text-field
          v-model="searchInput"
          placeholder="Search customers..."
          prepend-icon="mdi-magnify"
          hide-details
        />
      </v-card-text>
    </v-card>

    <v-card>
      <v-table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Contact</th>
            <th>Items Bought</th>
            <th>Total Revenue</th>
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
              No customers found.
            </td>
          </tr>
          <tr v-for="item in store.items" :key="item.id">
            <td>
              <div class="font-weight-600">{{ item.name }}</div>
              <div class="text-caption text-grey">{{ item.email }}</div>
            </td>
            <td>{{ item.contact_name || '-' }}</td>
            <td>{{ item.total_items_purchased || 0 }}</td>
            <td>{{ formatCurrency(item.total_revenue) }}</td>
            <td>{{ formatDate(item.updated_at) }}</td>
            <td>
              <v-btn size="x-small" variant="text" @click="openEditModal(item)" class="mr-2">
                Edit
              </v-btn>
              <v-btn size="x-small" variant="text" color="error" @click="handleDelete(item.id)">
                Delete
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

    <CustomerModal 
      :show="showModal" 
      :item="selectedItem" 
      @close="showModal = false" 
      @save="handleSave" 
    />
  </MainLayout>
</template>
