<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import { useInventoryStore } from '../stores/inventory';
import { useLocationStore } from '../stores/locations';
import MainLayout from '../layouts/MainLayout.vue';
import InventoryModal from '../components/InventoryModal.vue';
import HuntingModeModal from '../components/HuntingModeModal.vue';
import PhotoGallery from '../components/PhotoGallery.vue';
import api from '../axios';
import { debounce } from '../utils/helpers';
import { useDisplay } from 'vuetify';
import { useRouter, useRoute } from 'vue-router';

const store = useInventoryStore();
const locationStore = useLocationStore();
const router = useRouter();
const route = useRoute();
const { mobile, smAndDown } = useDisplay();
const showModal = ref(false);
const showHuntingModal = ref(false);
const selectedItem = ref(null);

// Gallery State
const showGallery = ref(false);
const galleryPhotos = ref([]);
const galleryIndex = ref(0);

// Local search value for debouncing
const searchInput = ref('');

const checkRouteForModal = async () => {
  if (route.name === 'inventory-new') {
    selectedItem.value = null;
    showModal.value = true;
  } else if (route.name === 'inventory-edit' && route.params.id) {
    try {
      selectedItem.value = await store.fetchItemDetail(route.params.id);
      showModal.value = true;
    } catch (e) {
      console.error('Failed to load item from URL', e);
      router.push('/inventory');
    }
  } else {
    showModal.value = false;
  }
};

onMounted(async () => {
  await store.fetchItems();
  locationStore.fetchItems();
  checkRouteForModal();
});

// Watch route changes to open/close modal
watch(() => route.path, () => {
  checkRouteForModal();
});

// Watch modal closure to clean up URL
watch(showModal, (val) => {
  if (!val && (route.name === 'inventory-new' || route.name === 'inventory-edit')) {
    router.push('/inventory');
  }
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

const tagFilter = computed({
  get: () => store.filters.tag,
  set: (val) => store.setFilter('tag', val)
});

const locationFilter = computed({
  get: () => store.filters.storage_location_id,
  set: (val) => store.setFilter('storage_location_id', val)
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
  router.push({ name: 'inventory-new' });
};

const openEditModal = async (item) => {
  router.push({ name: 'inventory-edit', params: { id: item.id } });
};

const handleDelete = async (id) => {
  if (!confirm('Are you sure you want to delete this item?')) return;
  try {
    await store.deleteItem(id);
  } catch (error) {
    alert('Failed to delete item: ' + (error.response?.data?.message || error.message));
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

const getStatusColor = (status) => {
  switch (status) {
    case 'in_stock': return 'success';
    case 'low_stock': return 'warning';
    case 'out_of_stock': return 'error';
    case 'archived': return 'grey';
    default: return 'primary';
  }
};

const toggleSort = (field) => {
  if (store.filters.sort_by === field) {
    store.setFilter('sort_dir', store.filters.sort_dir === 'asc' ? 'desc' : 'asc');
  } else {
    store.setFilter('sort_by', field);
    store.setFilter('sort_dir', 'asc');
  }
};

const getSortIcon = (field) => {
  if (store.filters.sort_by !== field) return 'mdi-sort';
  return store.filters.sort_dir === 'asc' ? 'mdi-sort-ascending' : 'mdi-sort-descending';
};
</script>

<template>
  <MainLayout>
    <div :class="mobile ? 'd-flex flex-column gap-4' : 'd-flex justify-space-between align-center'" class="mb-6">
      <h1 :class="mobile ? 'text-h4' : 'text-h3'">Inventory</h1>
      <div class="d-flex gap-2" :class="mobile ? 'flex-column' : ''">
        <v-btn color="secondary" variant="tonal" @click="showHuntingModal = true" :block="mobile">
          <v-icon left>mdi-camera-outline</v-icon>
          Hunting Mode
        </v-btn>
        <v-btn color="primary" @click="openCreateModal" :block="mobile">
          <v-icon left>mdi-plus</v-icon>
          Add Item
        </v-btn>
      </div>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <v-row dense align="center">
          <v-col cols="12" md="3">
            <v-text-field
              v-model="searchInput"
              placeholder="Search Title or SKU..."
              prepend-inner-icon="mdi-magnify"
              hide-details
              density="compact"
              variant="outlined"
            />
          </v-col>
          
          <v-col cols="12" sm="6" md="2">
            <v-select
              v-model="statusFilter"
              label="Status"
              :items="[
                { title: 'All Statuses', value: '' },
                { title: 'In Stock', value: 'in_stock' },
                { title: 'Low Stock', value: 'low_stock' },
                { title: 'Out of Stock', value: 'out_of_stock' },
                { title: 'Archived', value: 'archived' }
              ]"
              hide-details
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" sm="6" md="2">
            <v-select
              v-model="locationFilter"
              label="Location"
              :items="[{ name: 'All Locations', id: '' }, ...locationStore.items]"
              item-title="name"
              item-value="id"
              hide-details
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" sm="6" md="2">
            <v-text-field
              v-model="tagFilter"
              placeholder="Filter by Tag"
              prepend-inner-icon="mdi-tag-outline"
              hide-details
              density="compact"
              variant="outlined"
              clearable
            />
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-checkbox
              v-model="lowStockFilter"
              label="Low Stock Only"
              hide-details
              density="compact"
              color="primary"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <div v-if="store.loading" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
    </div>

    <div v-else-if="store.items.length === 0" class="text-center py-12 text-grey">
      <v-icon size="64" class="mb-4">mdi-package-variant</v-icon>
      <div class="text-h6">No items found.</div>
    </div>

    <template v-else>
      <!-- Mobile View: Cards -->
      <v-row v-if="smAndDown">
        <v-col v-for="item in store.items" :key="item.id" cols="12">
          <v-card variant="outlined" @click="openEditModal(item)">
            <div class="d-flex pa-3">
              <v-avatar size="80" rounded="lg" class="mr-4">
                <v-img 
                  v-if="item.photos && item.photos.length > 0" 
                  :src="item.photos[0].url" 
                  cover
                >
                  <template v-slot:placeholder>
                    <v-row class="fill-height ma-0" align="center" justify="center">
                      <v-progress-circular indeterminate color="grey-lighten-5" />
                    </v-row>
                  </template>
                </v-img>
                <v-icon v-else size="40" color="grey">mdi-package-variant</v-icon>
              </v-avatar>
              
              <div class="flex-grow-1 min-width-0">
                <div class="d-flex justify-space-between align-start">
                  <div class="font-weight-bold text-truncate pr-2">{{ item.title }}</div>
                  <div class="d-flex align-center gap-1">
                    <v-chip :color="getStatusColor(item.status)" size="x-small">
                        {{ item.status.replace('_', ' ') }}
                    </v-chip>
                    <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click.stop="handleDelete(item.id)"></v-btn>
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-1 mt-1 mb-1" v-if="item.tags && item.tags.length">
                  <v-chip v-for="tag in item.tags" :key="tag" size="x-small" variant="tonal" color="grey">
                    {{ tag }}
                  </v-chip>
                </div>
                <div class="text-caption text-grey">{{ item.sku }}</div>
                <div class="mt-1 font-weight-medium">
                  {{ item.quantity_on_hand }} {{ item.unit }}
                </div>
                <div v-if="item.storage_location" class="text-caption text-primary mt-1">
                  <v-icon size="12" class="mr-1">mdi-map-marker</v-icon>
                  {{ item.storage_location.name }}
                </div>
                <div class="text-caption text-grey mt-1">
                  Updated: {{ formatDate(item.updated_at) }}
                </div>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Desktop View: Table -->
      <v-card v-else>
        <v-table hover>
          <thead>
            <tr>
              <th class="text-left" style="width: 60px">Photo</th>
              <th class="text-left sortable-header" @click="toggleSort('title')">
                Title
                <v-icon size="14" class="ml-1">{{ getSortIcon('title') }}</v-icon>
              </th>
              <th class="text-left sortable-header" @click="toggleSort('sku')">
                SKU
                <v-icon size="14" class="ml-1">{{ getSortIcon('sku') }}</v-icon>
              </th>
              <th class="text-left sortable-header" @click="toggleSort('storage_location_name')">
                Location
                <v-icon size="14" class="ml-1">{{ getSortIcon('storage_location_name') }}</v-icon>
              </th>
              <th class="text-left sortable-header" @click="toggleSort('status')">
                Status
                <v-icon size="14" class="ml-1">{{ getSortIcon('status') }}</v-icon>
              </th>
              <th class="text-left sortable-header" @click="toggleSort('quantity_on_hand')">
                On Hand
                <v-icon size="14" class="ml-1">{{ getSortIcon('quantity_on_hand') }}</v-icon>
              </th>
              <th class="text-left sortable-header" @click="toggleSort('updated_at')">
                Updated
                <v-icon size="14" class="ml-1">{{ getSortIcon('updated_at') }}</v-icon>
              </th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in store.items" :key="item.id" style="cursor: pointer" @click="openEditModal(item)">
              <td>
                <v-avatar size="40" rounded="sm" @click.stop="openGallery(item)">
                  <v-img 
                    v-if="item.photos && item.photos.length > 0" 
                    :src="item.photos[0].url" 
                    cover
                  />
                  <v-icon v-else color="grey-lighten-2">mdi-package-variant</v-icon>
                  <div v-if="item.photos && item.photos.length > 1" class="position-absolute text-white text-caption" style="bottom: 0; right: 0; background: rgba(0,0,0,0.6); padding: 0 2px; border-top-left-radius: 2px; font-size: 8px !important;">
                    +{{ item.photos.length - 1 }}
                  </div>
                </v-avatar>
              </td>
              <td>
                <div class="font-weight-bold text-truncate" style="max-width: 250px;">{{ item.title }}</div>
                <div class="d-flex flex-wrap gap-1 mt-1" v-if="item.tags && item.tags.length">
                  <v-chip v-for="tag in item.tags" :key="tag" size="x-small" variant="tonal" color="grey" style="font-size: 8px !important; height: 16px;">
                    {{ tag }}
                  </v-chip>
                </div>
              </td>
              <td class="text-caption font-mono">{{ item.sku }}</td>
              <td>
                <div v-if="item.storage_location" class="text-body-2">
                  <v-icon size="14" color="primary" class="mr-1">mdi-map-marker</v-icon>
                  {{ item.storage_location.name }}
                </div>
                <div v-else-if="item.location" class="text-caption text-grey italic">
                  {{ item.location }}
                </div>
                <span v-else class="text-grey">—</span>
              </td>
              <td>
                <v-chip :color="getStatusColor(item.status)" size="small">
                  {{ item.status.replace('_', ' ') }}
                </v-chip>
              </td>
              <td>
                <div class="font-weight-medium">{{ item.quantity_on_hand }} {{ item.unit }}</div>
              </td>
              <td class="text-caption">{{ formatDate(item.updated_at) }}</td>
              <td class="text-right">
                <v-btn size="small" variant="text" icon="mdi-pencil" @click.stop="openEditModal(item)"></v-btn>
                <v-btn size="small" variant="text" icon="mdi-delete" color="error" @click.stop="handleDelete(item.id)"></v-btn>
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

    <InventoryModal 
      v-model:show="showModal" 
      :item="selectedItem" 
      @close="showModal = false" 
      @save="handleSave"
      @delete-photo="handleDeletePhoto"
    />

    <HuntingModeModal
      v-model:show="showHuntingModal"
      @save="handleSave"
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
.gap-2 {
  gap: 8px;
}
.min-width-0 {
  min-width: 0;
}
.sortable-header {
  cursor: pointer;
  white-space: nowrap;
}
.sortable-header:hover {
  background: rgba(0,0,0,0.03);
}
.font-mono {
  font-family: monospace;
}
</style>
