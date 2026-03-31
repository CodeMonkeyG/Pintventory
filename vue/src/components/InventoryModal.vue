<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import api from '../axios';
import PhotoGallery from './PhotoGallery.vue';
import { revokeBlobUrls } from '../utils/helpers';
import { useVendorStore } from '../stores/vendors';
import { useCustomerStore } from '../stores/customers';
import { useDisplay } from 'vuetify';

const vendorStore = useVendorStore();
const customerStore = useCustomerStore();
const { mobile } = useDisplay();

const props = defineProps({
  show: Boolean,
  item: Object
});

const emit = defineEmits(['close', 'save', 'delete-photo', 'update:show']);

const activeTab = ref('details');
const fileInput = ref(null);
const autoFillInput = ref(null);
const isAnalyzing = ref(false);
const pendingPhotos = ref([]);
const localPhotos = ref([]);

const vendors = ref([]);
const customers = ref([]);

const formData = ref({
    title: '',
    sku: '',
    status: 'in_stock',
    quantity_on_hand: 0,
    reorder_point: 0,
    unit: '',
    location: '',
    tags: '',
    description: '',
    evaluation: ''
});

const showGallery = ref(false);
const galleryIndex = ref(0);

const isEdit = computed(() => !!props.item);

const showPurchaseForm = ref(false);
const showSaleForm = ref(false);

const newPurchase = ref({
    vendor_id: '',
    quantity_purchased: 1,
    unit_cost: 0,
    purchased_at: new Date().toISOString().split('T')[0],
    notes: ''
});

const newSale = ref({
    customer_id: '',
    quantity_sold: 1,
    unit_price: 0,
    sold_at: new Date().toISOString().split('T')[0],
    notes: ''
});

watch(() => props.item, (item) => {
    if (item) {
        formData.value = {
            title: item.title || '',
            sku: item.sku || '',
            status: item.status || 'in_stock',
            quantity_on_hand: item.quantity_on_hand ?? 0,
            reorder_point: item.reorder_point ?? 0,
            unit: item.unit || '',
            location: item.location || '',
            tags: Array.isArray(item.tags) ? item.tags.join(', ') : (item.tags || ''),
            description: item.description || '',
            evaluation: item.evaluation || ''
        };
        localPhotos.value = (item.photos || []).map(p => ({ id: p.id, url: p.url, caption: p.caption || '' }));
    } else {
        formData.value = {
            title: '',
            sku: '',
            status: 'in_stock',
            quantity_on_hand: 0,
            reorder_point: 0,
            unit: '',
            location: '',
            tags: '',
            description: '',
            evaluation: ''
        };
        localPhotos.value = [];
        pendingPhotos.value = [];
        showPurchaseForm.value = false;
        showSaleForm.value = false;
    }
}, { immediate: true });

watch(activeTab, async (tab) => {
    if (tab === 'purchases') {
        vendors.value = await vendorStore.fetchAllVendors();
    }
    if (tab === 'sales') {
        customers.value = await customerStore.fetchAllCustomers();
    }
});

watch(() => props.show, (val) => {
    if (!val) {
        const urls = pendingPhotos.value.map(p => p.url);
        revokeBlobUrls(urls);
        pendingPhotos.value = [];
    }
});

const triggerAutoFill = () => autoFillInput.value.click();

const handleAutoFill = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isAnalyzing.value = true;
    try {
        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/image-identify', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;
        if (data.title) formData.value.title = data.title;
        if (data.description) formData.value.description = data.description;
        if (data.evaluation) formData.value.evaluation = data.evaluation;
        if (data.tags) {
            const newTags = Array.isArray(data.tags) ? data.tags.join(', ') : data.tags;
            formData.value.tags = newTags;
        }

        const previewUrl = URL.createObjectURL(file);
        pendingPhotos.value.push({ file, url: previewUrl });
        
        alert('Auto-fill complete!');
    } catch (error) {
        console.error(error);
        alert('AI Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isAnalyzing.value = false;
        event.target.value = null;
    }
};

const save = () => {
  if (!formData.value.title) return alert('Title is required');

  const payload = {
    ...formData.value,
    tags: formData.value.tags ? formData.value.tags.split(',').map(t => t.trim()).filter(t => t) : []
  };
  
  emit('save', payload, pendingPhotos.value.map(p => p.file));
};

const triggerUpload = () => fileInput.value.click();

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    const previewUrl = URL.createObjectURL(file);
    pendingPhotos.value.push({ file, url: previewUrl });
    event.target.value = null;
};

const deletePhoto = (photo) => {
    if (photo.id) {
        emit('delete-photo', photo.id);
        localPhotos.value = localPhotos.value.filter(p => p.id !== photo.id);
    } else {
        pendingPhotos.value = pendingPhotos.value.filter(p => p.url !== photo.url);
    }
};

const openGallery = (index) => {
    galleryIndex.value = index;
    showGallery.value = true;
};

const savePurchase = async () => {
    if (!newPurchase.value.vendor_id) return alert('Vendor is required');
    try {
        await api.post('/purchases', {
            inventory_item_id: props.item.id,
            ...newPurchase.value
        });
        alert('Purchase recorded. Item quantity updated.');
        showPurchaseForm.value = false;
        newPurchase.value = {
            vendor_id: '',
            quantity_purchased: 1,
            unit_cost: 0,
            purchased_at: new Date().toISOString().split('T')[0],
            notes: ''
        };
        emit('save', null, []); 
    } catch (e) {
        alert('Failed to save purchase: ' + (e.response?.data?.message || e.message));
    }
};

const saveSale = async () => {
    if (!newSale.value.customer_id) return alert('Customer is required');
    try {
        await api.post('/sales', {
            inventory_item_id: props.item.id,
            ...newSale.value
        });
        alert('Sale recorded. Item quantity updated.');
        showSaleForm.value = false;
        newSale.value = {
            customer_id: '',
            quantity_sold: 1,
            unit_price: 0,
            sold_at: new Date().toISOString().split('T')[0],
            notes: ''
        };
        emit('save', null, []);
    } catch (e) {
        alert('Failed to save sale: ' + (e.response?.data?.message || e.message));
    }
};

const formatDate = (d) => new Date(d).toLocaleDateString();

const closeModal = () => {
  $emit('close');
  emit('update:show', false);
};
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '800'"
    :transition="mobile ? 'dialog-bottom-transition' : 'dialog-transition'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" v-if="mobile">
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>{{ isEdit ? 'Edit Item' : 'Add Item' }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="save">Save</v-btn>
      </v-toolbar>

      <v-card-title class="d-flex justify-space-between align-center px-6 pt-6 pb-2" v-else>
        <span class="text-h5">{{ isEdit ? 'Edit Inventory Item' : 'Add New Inventory Item' }}</span>
        <v-btn icon variant="text" @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-tabs v-model="activeTab" v-if="isEdit" color="primary" grow>
        <v-tab value="details">Details</v-tab>
        <v-tab value="purchases">Purchases</v-tab>
        <v-tab value="sales">Sales</v-tab>
      </v-tabs>

      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
        <v-window v-model="activeTab">
          <!-- Details & Photos Tab -->
          <v-window-item value="details">
            <div class="mb-6">
              <v-btn
                color="success"
                @click="triggerAutoFill"
                :disabled="isAnalyzing"
                prepend-icon="mdi-sparkles"
                block
                size="large"
                elevation="1"
              >
                {{ isAnalyzing ? 'Analyzing Image...' : 'AI Auto-Fill from Image' }}
              </v-btn>
              <input type="file" ref="autoFillInput" @change="handleAutoFill" accept="image/*" capture="environment" hidden />
            </div>

            <div class="mb-6">
              <div class="d-flex justify-space-between align-center mb-3">
                <h3 class="text-subtitle-1 font-weight-bold">Photos</h3>
                <v-btn
                  variant="tonal"
                  size="small"
                  color="primary"
                  prepend-icon="mdi-camera"
                  @click="triggerUpload"
                >
                  Add Photo
                </v-btn>
              </div>
              <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" capture="environment" hidden />

              <div class="d-flex flex-nowrap gap-3 pb-2 overflow-x-auto" style="min-height: 100px;">
                <div v-if="localPhotos.length === 0 && pendingPhotos.length === 0" class="w-100 d-flex flex-column align-center justify-center border-dashed rounded-lg py-8 text-grey">
                  <v-icon size="32" class="mb-2">mdi-image-plus</v-icon>
                  <span class="text-caption">No photos uploaded</span>
                </div>
                
                <div
                  v-for="(photo, index) in localPhotos"
                  :key="photo.id"
                  class="flex-shrink-0 position-relative rounded-lg overflow-hidden border"
                  style="width: 100px; height: 100px; cursor: pointer;"
                  @click="openGallery(index)"
                >
                  <v-img :src="photo.url" :alt="photo.caption" cover class="fill-height" />
                  <v-btn
                    icon="mdi-close"
                    size="x-small"
                    color="error"
                    class="position-absolute"
                    style="top: 4px; right: 4px;"
                    @click.stop="deletePhoto(photo)"
                  />
                </div>
                
                <div
                  v-for="photo in pendingPhotos"
                  :key="photo.url"
                  class="flex-shrink-0 position-relative rounded-lg overflow-hidden border-dashed"
                  style="width: 100px; height: 100px;"
                >
                  <v-img :src="photo.url" cover class="fill-height" style="opacity: 0.6;" />
                  <v-btn
                    icon="mdi-close"
                    size="x-small"
                    color="error"
                    class="position-absolute"
                    style="top: 4px; right: 4px;"
                    @click="deletePhoto(photo)"
                  />
                  <div class="position-absolute text-center w-100" style="top: 50%; transform: translateY(-50%); font-size: 10px; font-weight: bold; background: rgba(255,255,255,0.7);">
                    PENDING
                  </div>
                </div>
              </div>
            </div>

            <v-row dense>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.title"
                  label="Title *"
                  variant="outlined"
                  density="compact"
                  required
                />
              </v-col>
              
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="formData.sku"
                  label="SKU"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="12" sm="6">
                <v-select
                  v-model="formData.status"
                  label="Status"
                  :items="[
                    { title: 'In Stock', value: 'in_stock' },
                    { title: 'Low Stock', value: 'low_stock' },
                    { title: 'Out of Stock', value: 'out_of_stock' },
                    { title: 'Archived', value: 'archived' }
                  ]"
                  variant="outlined"
                  density="compact"
                />
              </v-col>

              <v-col cols="6" sm="4">
                <v-text-field
                  v-model.number="formData.quantity_on_hand"
                  label="Qty On Hand"
                  type="number"
                  disabled
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="6" sm="4">
                <v-text-field
                  v-model.number="formData.reorder_point"
                  label="Reorder Pt"
                  type="number"
                  min="0"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="formData.unit"
                  label="Unit (e.g. pcs, sets)"
                  variant="outlined"
                  density="compact"
                />
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="formData.location"
                  label="Storage Location"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-map-marker"
                />
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="formData.tags"
                  label="Tags"
                  hint="Separate with commas"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-tag"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.evaluation"
                  label="AI Evaluation"
                  rows="3"
                  variant="outlined"
                  density="compact"
                  auto-grow
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.description"
                  label="Description"
                  rows="2"
                  variant="outlined"
                  density="compact"
                  auto-grow
                />
              </v-col>
            </v-row>
          </v-window-item>

          <!-- Purchases Tab -->
          <v-window-item value="purchases">
            <div class="d-flex justify-space-between align-center mb-4">
              <h3 class="text-subtitle-1 font-weight-bold">Purchase History</h3>
              <v-btn
                variant="outlined"
                size="small"
                @click="showPurchaseForm = !showPurchaseForm"
                :color="showPurchaseForm ? 'error' : 'primary'"
              >
                {{ showPurchaseForm ? 'Cancel' : 'Add Purchase' }}
              </v-btn>
            </div>

            <v-expand-transition>
              <v-card v-if="showPurchaseForm" class="mb-6 bg-grey-lighten-4" variant="tonal" border rounded="lg">
                <v-card-text class="pa-4">
                  <v-select
                    v-model="newPurchase.vendor_id"
                    label="Vendor *"
                    :items="vendors"
                    item-title="name"
                    item-value="id"
                    variant="outlined"
                    density="compact"
                    required
                  />
                  <v-row dense>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="newPurchase.purchased_at"
                        label="Date"
                        type="date"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                    <v-col cols="6" sm="3">
                      <v-text-field
                        v-model.number="newPurchase.quantity_purchased"
                        label="Qty"
                        type="number"
                        min="1"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                    <v-col cols="6" sm="3">
                      <v-text-field
                        v-model.number="newPurchase.unit_cost"
                        label="Cost"
                        type="number"
                        min="0"
                        step="0.01"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                  </v-row>
                  <v-btn color="primary" block @click="savePurchase" class="mt-2">Record Purchase</v-btn>
                </v-card-text>
              </v-card>
            </v-expand-transition>

            <v-table v-if="item && item.purchases && item.purchases.length > 0" density="comfortable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Vendor</th>
                  <th>Qty</th>
                  <th>Cost</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in item.purchases" :key="p.id">
                  <td>{{ formatDate(p.purchased_at) }}</td>
                  <td class="text-truncate" style="max-width: 120px;">{{ p.vendor ? p.vendor.name : 'Unknown' }}</td>
                  <td>{{ p.quantity_purchased }}</td>
                  <td>${{ p.unit_cost }}</td>
                </tr>
              </tbody>
            </v-table>
            <div v-else class="text-center py-8 text-grey border rounded-lg">
              <v-icon size="32" class="mb-2">mdi-history</v-icon>
              <div class="text-caption">No purchases recorded.</div>
            </div>
          </v-window-item>

          <!-- Sales Tab -->
          <v-window-item value="sales">
            <div class="d-flex justify-space-between align-center mb-4">
              <h3 class="text-subtitle-1 font-weight-bold">Sales History</h3>
              <v-btn
                variant="outlined"
                size="small"
                @click="showSaleForm = !showSaleForm"
                :color="showSaleForm ? 'error' : 'primary'"
              >
                {{ showSaleForm ? 'Cancel' : 'Add Sale' }}
              </v-btn>
            </div>

            <v-expand-transition>
              <v-card v-if="showSaleForm" class="mb-6 bg-grey-lighten-4" variant="tonal" border rounded="lg">
                <v-card-text class="pa-4">
                  <v-select
                    v-model="newSale.customer_id"
                    label="Customer *"
                    :items="customers"
                    item-title="name"
                    item-value="id"
                    variant="outlined"
                    density="compact"
                    required
                  />
                  <v-row dense>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="newSale.sold_at"
                        label="Date"
                        type="date"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                    <v-col cols="6" sm="3">
                      <v-text-field
                        v-model.number="newSale.quantity_sold"
                        label="Qty"
                        type="number"
                        min="1"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                    <v-col cols="6" sm="3">
                      <v-text-field
                        v-model.number="newSale.unit_price"
                        label="Price"
                        type="number"
                        min="0"
                        step="0.01"
                        variant="outlined"
                        density="compact"
                      />
                    </v-col>
                  </v-row>
                  <v-btn color="primary" block @click="saveSale" class="mt-2">Record Sale</v-btn>
                </v-card-text>
              </v-card>
            </v-expand-transition>

            <v-table v-if="item && item.sales && item.sales.length > 0" density="comfortable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Qty</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in item.sales" :key="s.id">
                  <td>{{ formatDate(s.sold_at) }}</td>
                  <td class="text-truncate" style="max-width: 120px;">{{ s.customer ? s.customer.name : 'Unknown' }}</td>
                  <td>{{ s.quantity_sold }}</td>
                  <td>${{ s.unit_price }}</td>
                </tr>
              </tbody>
            </v-table>
            <div v-else class="text-center py-8 text-grey border rounded-lg">
              <v-icon size="32" class="mb-2">mdi-history</v-icon>
              <div class="text-caption">No sales recorded.</div>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
      
      <v-divider v-if="!mobile"></v-divider>
      <v-card-actions class="pa-4" v-if="!mobile">
        <v-spacer />
        <v-btn variant="text" @click="$emit('close')">Cancel</v-btn>
        <v-btn v-if="activeTab === 'details'" color="primary" variant="elevated" @click="save">
          {{ isEdit ? 'Save Changes' : 'Create Item' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <PhotoGallery
    :show="showGallery"
    :photos="localPhotos"
    :startIndex="galleryIndex"
    @close="showGallery = false"
  />
</template>

<style scoped>
.overflow-x-auto {
  overflow-x: auto;
  scrollbar-width: none; /* Firefox */
}
.overflow-x-auto::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}
.border-dashed {
  border: 2px dashed #e0e0e0;
}
.gap-3 {
  gap: 12px;
}
</style>