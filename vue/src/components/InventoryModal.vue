<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import api from '../axios';
import PhotoGallery from './PhotoGallery.vue';
import { revokeBlobUrls } from '../utils/helpers';
import { useVendorStore } from '../stores/vendors';
import { useCustomerStore } from '../stores/customers';

const vendorStore = useVendorStore();
const customerStore = useCustomerStore();

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
  <v-dialog :modelValue="show" @update:modelValue="$emit('update:show', $event)" persistent max-width="800">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ isEdit ? 'Edit Item' : 'Add New Item' }}</span>
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-tabs v-model="activeTab" v-if="isEdit">
        <v-tab value="details">Details & Photos</v-tab>
        <v-tab value="purchases">Purchases</v-tab>
        <v-tab value="sales">Sales</v-tab>
      </v-tabs>

      <v-card-text class="pa-6">
        <v-window v-model="activeTab">
          <!-- Details & Photos Tab -->
          <v-window-item value="details">
            <div class="mb-4">
              <v-btn
                color="success"
                @click="triggerAutoFill"
                :disabled="isAnalyzing"
                prepend-icon="mdi-sparkles"
                class="mb-4"
              >
                {{ isAnalyzing ? 'Analyzing Image...' : 'Auto-Fill from Image' }}
              </v-btn>
              <input type="file" ref="autoFillInput" @change="handleAutoFill" accept="image/*" capture="environment" hidden />
            </div>

            <v-card class="mb-4" variant="outlined">
              <v-card-text>
                <div class="d-flex justify-space-between align-center mb-3">
                  <h3 class="text-h6">Photos</h3>
                  <v-btn
                    icon
                    small
                    color="primary"
                    @click="triggerUpload"
                  >
                    <v-icon>mdi-plus</v-icon>
                  </v-btn>
                </div>
                <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" capture="environment" hidden />

                <div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));">
                  <div v-if="localPhotos.length === 0 && pendingPhotos.length === 0" class="text-center text-grey pa-4">
                    No photos yet
                  </div>
                  
                  <div
                    v-for="(photo, index) in localPhotos"
                    :key="photo.id"
                    class="position-relative"
                    style="aspect-ratio: 1; cursor: pointer; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;"
                    @click="openGallery(index)"
                  >
                    <img :src="photo.url" :alt="photo.caption" style="width: 100%; height: 100%; object-fit: cover;" />
                    <v-btn
                      icon
                      size="x-small"
                      color="error"
                      class="position-absolute"
                      style="top: 4px; right: 4px;"
                      @click.stop="deletePhoto(photo)"
                    >
                      <v-icon>mdi-close</v-icon>
                    </v-btn>
                  </div>
                  
                  <div
                    v-for="photo in pendingPhotos"
                    :key="photo.url"
                    class="position-relative"
                    style="aspect-ratio: 1; border: 2px dashed #bbb; border-radius: 4px; overflow: hidden;"
                  >
                    <img :src="photo.url" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.7;" />
                    <v-btn
                      icon
                      size="x-small"
                      color="error"
                      class="position-absolute"
                      style="top: 4px; right: 4px;"
                      @click="deletePhoto(photo)"
                    >
                      <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <div class="position-absolute text-center" style="top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999;">
                      Pending
                    </div>
                  </div>
                </div>
              </v-card-text>
            </v-card>

            <v-text-field
              v-model="formData.title"
              label="Title *"
              placeholder="Item Name"
              required
            />
            
            <div class="d-flex gap-3">
              <v-text-field
                v-model="formData.sku"
                label="SKU"
                placeholder="INV-..."
                class="flex-grow-1"
              />
              <v-select
                v-model="formData.status"
                label="Status"
                :items="['in_stock', 'low_stock', 'out_of_stock', 'archived']"
                class="flex-grow-1"
              />
            </div>

            <div class="d-flex gap-3">
              <v-text-field
                v-model.number="formData.quantity_on_hand"
                label="Quantity On Hand"
                type="number"
                disabled
                class="flex-grow-1"
              />
              <v-text-field
                v-model.number="formData.reorder_point"
                label="Reorder Point"
                type="number"
                min="0"
                class="flex-grow-1"
              />
              <v-text-field
                v-model="formData.unit"
                label="Unit"
                class="flex-grow-1"
              />
            </div>

            <v-text-field
              v-model="formData.location"
              label="Location"
            />

            <v-text-field
              v-model="formData.tags"
              label="Tags"
              hint="Comma-separated"
            />

            <v-textarea
              v-model="formData.evaluation"
              label="Evaluation"
              rows="2"
            />

            <v-textarea
              v-model="formData.description"
              label="Description"
              rows="2"
            />
          </v-window-item>

          <!-- Purchases Tab -->
          <v-window-item value="purchases">
            <div class="d-flex justify-space-between align-center mb-4">
              <h3 class="text-h6">Purchase History</h3>
              <v-btn
                variant="outlined"
                @click="showPurchaseForm = !showPurchaseForm"
              >
                {{ showPurchaseForm ? 'Cancel' : 'Record Purchase' }}
              </v-btn>
            </div>

            <v-card v-if="showPurchaseForm" class="mb-4" variant="outlined">
              <v-card-text>
                <v-select
                  v-model="newPurchase.vendor_id"
                  label="Vendor *"
                  :items="vendors"
                  item-title="name"
                  item-value="id"
                  required
                />
                <div class="d-flex gap-3">
                  <v-text-field
                    v-model="newPurchase.purchased_at"
                    label="Date"
                    type="date"
                    class="flex-grow-1"
                  />
                  <v-text-field
                    v-model.number="newPurchase.quantity_purchased"
                    label="Qty"
                    type="number"
                    min="1"
                    class="flex-grow-1"
                  />
                  <v-text-field
                    v-model.number="newPurchase.unit_cost"
                    label="Unit Cost"
                    type="number"
                    min="0"
                    step="0.01"
                    class="flex-grow-1"
                  />
                </div>
                <v-btn color="primary" @click="savePurchase">Save Purchase</v-btn>
              </v-card-text>
            </v-card>

            <v-table v-if="item && item.purchases && item.purchases.length > 0">
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
                  <td>{{ p.vendor ? p.vendor.name : 'Unknown' }}</td>
                  <td>{{ p.quantity_purchased }}</td>
                  <td>{{ p.unit_cost }}</td>
                </tr>
              </tbody>
            </v-table>
            <div v-else class="text-center py-6 text-grey">
              No purchases recorded.
            </div>
          </v-window-item>

          <!-- Sales Tab -->
          <v-window-item value="sales">
            <div class="d-flex justify-space-between align-center mb-4">
              <h3 class="text-h6">Sales History</h3>
              <v-btn
                variant="outlined"
                @click="showSaleForm = !showSaleForm"
              >
                {{ showSaleForm ? 'Cancel' : 'Record Sale' }}
              </v-btn>
            </div>

            <v-card v-if="showSaleForm" class="mb-4" variant="outlined">
              <v-card-text>
                <v-select
                  v-model="newSale.customer_id"
                  label="Customer *"
                  :items="customers"
                  item-title="name"
                  item-value="id"
                  required
                />
                <div class="d-flex gap-3">
                  <v-text-field
                    v-model="newSale.sold_at"
                    label="Date"
                    type="date"
                    class="flex-grow-1"
                  />
                  <v-text-field
                    v-model.number="newSale.quantity_sold"
                    label="Qty"
                    type="number"
                    min="1"
                    class="flex-grow-1"
                  />
                  <v-text-field
                    v-model.number="newSale.unit_price"
                    label="Price"
                    type="number"
                    min="0"
                    step="0.01"
                    class="flex-grow-1"
                  />
                </div>
                <v-btn color="primary" @click="saveSale">Save Sale</v-btn>
              </v-card-text>
            </v-card>

            <v-table v-if="item && item.sales && item.sales.length > 0">
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
                  <td>{{ s.customer ? s.customer.name : 'Unknown' }}</td>
                  <td>{{ s.quantity_sold }}</td>
                  <td>{{ s.unit_price }}</td>
                </tr>
              </tbody>
            </v-table>
            <div v-else class="text-center py-6 text-grey">
              No sales recorded.
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
      
      <v-card-actions>
        <v-spacer />
        <v-btn @click="$emit('close')">Close</v-btn>
        <v-btn v-if="activeTab === 'details'" color="primary" @click="save">
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