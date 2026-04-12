<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { useCustomerStore } from '../stores/customers';
import MainLayout from '../layouts/MainLayout.vue';
import CustomerModal from '../components/CustomerModal.vue';
import api from '../axios';
import { debounce } from '../utils/helpers';
import { useDisplay } from 'vuetify';

const store = useCustomerStore();
const { mobile, smAndDown } = useDisplay();
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
    <div :class="mobile ? 'd-flex flex-column gap-4' : 'd-flex justify-space-between align-center'" class="mb-6">
      <h1 :class="mobile ? 'text-h4' : 'text-h3'">Customers</h1>
      <v-btn color="primary" @click="openCreateModal" :block="mobile" size="large" elevation="2">
        <v-icon prepend-icon>mdi-plus</v-icon>
        Add Customer
      </v-btn>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <v-text-field
          v-model="searchInput"
          placeholder="Search customers..."
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

    <div v-else-if="store.items.length === 0" class="text-center py-12 text-grey">
      <v-icon size="64" class="mb-4">mdi-account-group-outline</v-icon>
      <div class="text-h6">No customers found.</div>
    </div>

    <template v-else>
      <!-- Mobile View: Cards -->
      <v-row v-if="smAndDown">
        <v-col v-for="item in store.items" :key="item.id" cols="12">
          <v-card variant="outlined" class="pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <div>
                <div class="text-h6 font-weight-bold">{{ item.name }}</div>
                <div class="text-caption text-grey">{{ item.email }}</div>
              </div>
              <div class="text-right">
                <div class="text-subtitle-2 font-weight-bold text-success">{{ formatCurrency(item.total_revenue) }}</div>
                <div class="text-caption">{{ item.total_items_purchased || 0 }} items</div>
              </div>
            </div>
            
            <v-divider class="my-2"></v-divider>
            
            <div class="d-flex justify-space-between align-center">
              <div class="text-caption">
                <v-icon size="14" class="mr-1">mdi-account</v-icon>
                {{ item.contact_name || '-' }}
              </div>
              <div class="d-flex gap-2">
                <v-btn size="small" variant="tonal" @click="openEditModal(item)">Edit</v-btn>
                <v-btn size="small" variant="tonal" color="error" @click="handleDelete(item.id)">Delete</v-btn>
              </div>
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
              <th>Contact</th>
              <th>Items Bought</th>
              <th>Total Revenue</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in store.items" :key="item.id">
              <td>
                <div class="font-weight-bold">{{ item.name }}</div>
                <div class="text-caption text-grey">{{ item.email }}</div>
              </td>
              <td>{{ item.contact_name || '-' }}</td>
              <td>{{ item.total_items_purchased || 0 }}</td>
              <td>{{ formatCurrency(item.total_revenue) }}</td>
              <td>{{ formatDate(item.updated_at) }}</td>
              <td>
                <v-btn size="small" variant="text" icon="mdi-pencil" @click="openEditModal(item)" class="mr-1"></v-btn>
                <v-btn size="small" variant="text" icon="mdi-delete" color="error" @click="handleDelete(item.id)"></v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </template>
    
    <!-- Pagination -->
    <div v-if="store.pagination.last_page > 1" class="d-flex justify-center align-center gap-4 mt-8 flex-wrap">
      <v-btn
        :disabled="store.pagination.current_page === 1"
        @click="store.fetchItems(store.pagination.current_page - 1)"
        variant="outlined"
        size="small"
      >
        Previous
      </v-btn>
      <span class="text-body-2 font-weight-medium">
        {{ store.pagination.current_page }} / {{ store.pagination.last_page }}
      </span>
      <v-btn
        :disabled="store.pagination.current_page === store.pagination.last_page"
        @click="store.fetchItems(store.pagination.current_page + 1)"
        variant="outlined"
        size="small"
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
