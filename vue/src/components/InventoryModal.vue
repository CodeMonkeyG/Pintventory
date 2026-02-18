<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import api from '../axios';
import PhotoGallery from './PhotoGallery.vue';

const props = defineProps({
  show: Boolean,
  item: Object // If null, we are in Create mode
});

const emit = defineEmits(['close', 'save', 'delete-photo']);

const activeTab = ref('details');
const fileInput = ref(null);
const autoFillInput = ref(null);
const isAnalyzing = ref(false);
const pendingPhotos = ref([]);
const localPhotos = ref([]);

// Lists for dropdowns
const vendors = ref([]);
const customers = ref([]);

// Form state
const formData = ref({
    title: '',
    sku: '',
    status: 'in_stock',
    quantity_on_hand: 0,
    reorder_point: 0,
    unit: '',
    location: '',
    tags: '',
    description: ''
});

// Gallery State
const showGallery = ref(false);
const galleryIndex = ref(0);

const isEdit = computed(() => !!props.item);

// Purchase / Sale form state
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

// Initialize form when item changes or on create
watch(() => props.item, (it) => {
    if (it) {
        formData.value = {
            title: it.title || '',
            sku: it.sku || '',
            status: it.status || 'in_stock',
            quantity_on_hand: it.quantity_on_hand ?? 0,
            reorder_point: it.reorder_point ?? 0,
            unit: it.unit || '',
            location: it.location || '',
            tags: Array.isArray(it.tags) ? it.tags.join(', ') : (it.tags || ''),
            description: it.description || ''
        };
        localPhotos.value = (it.photos || []).map(p => ({ id: p.id, url: p.url, caption: p.caption || '' }));
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
            description: ''
        };
        localPhotos.value = [];
        pendingPhotos.value = [];
        showPurchaseForm.value = false;
        showSaleForm.value = false;
    }
}, { immediate: true });

// Load vendors/customers when needed
watch(activeTab, async (tab) => {
    if (tab === 'purchases') {
        try {
            const res = await api.get('/vendors?per_page=100');
            vendors.value = res.data.data;
        } catch (e) { console.error('Failed to load vendors', e); }
    }
    if (tab === 'sales') {
        try {
            const res = await api.get('/customers?per_page=100');
            customers.value = res.data.data;
        } catch (e) { console.error('Failed to load customers', e); }
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
        if (data.tags) {
            const newTags = Array.isArray(data.tags) ? data.tags.join(', ') : data.tags;
            formData.value.tags = newTags;
        }

        // Also add to pending photos
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

// Transaction Logic
const savePurchase = async () => {
    if (!newPurchase.value.vendor_id) return alert('Vendor is required');
    try {
        await api.post('/purchases', {
            inventory_item_id: props.item.id,
            ...newPurchase.value
        });
        alert('Purchase recorded. Item quantity updated.');
        showPurchaseForm.value = false;
        // Reset form
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
        emit('save', null, []); // Trigger refresh
    } catch (e) {
        alert('Failed to save sale: ' + (e.response?.data?.message || e.message));
    }
};

const formatDate = (d) => new Date(d).toLocaleDateString();
</script>

<template>
  <div v-if="show" class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Item' : 'Add New Item' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <!-- Navigation Tabs -->
      <div class="tabs" v-if="isEdit">
          <button :class="['tab-btn', { active: activeTab === 'details' }]" @click="activeTab = 'details'">Details & Photos</button>
          <button :class="['tab-btn', { active: activeTab === 'purchases' }]" @click="activeTab = 'purchases'">Purchases</button>
          <button :class="['tab-btn', { active: activeTab === 'sales' }]" @click="activeTab = 'sales'">Sales</button>
      </div>

      <!-- Details & Photos Tab -->
      <div class="modal-body" v-if="activeTab === 'details'">
        <div class="auto-fill-section">
            <input type="file" ref="autoFillInput" @change="handleAutoFill" accept="image/*" capture="environment" hidden />
            <button class="btn-ai" @click="triggerAutoFill" :disabled="isAnalyzing">
                {{ isAnalyzing ? 'Analyzing Image...' : '✨ Auto-Fill from Image' }}
            </button>
        </div>

        <div class="photos-section">
            <label>Photos</label>
            <div class="photos-toolbar">
                <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" capture="environment" hidden />
                <button class="btn-primary" @click="triggerUpload">+ Add Photo</button>
            </div>
            <div class="photos-grid">
                <div v-if="localPhotos.length === 0 && pendingPhotos.length === 0" class="no-photos">No photos yet.</div>
                
                <!-- Existing Photos -->
                <div v-for="(photo, index) in localPhotos" :key="photo.id" class="photo-card">
                    <img :src="photo.url" :alt="photo.caption" @click="openGallery(index)" />
                    <button class="delete-photo-btn" @click.stop="deletePhoto(photo)">&times;</button>
                </div>
                
                <!-- Pending Photos -->
                <div v-for="photo in pendingPhotos" :key="photo.url" class="photo-card pending">
                    <img :src="photo.url" />
                    <button class="delete-photo-btn" @click="deletePhoto(photo)">&times;</button>
                </div>
            </div>
        </div>

        <div class="form-group">
          <label>Title *</label>
          <input v-model="formData.title" type="text" placeholder="Item Name" />
        </div>
        
        <div class="form-row">
            <div class="form-group">
            <label>SKU</label>
            <input v-model="formData.sku" type="text" placeholder="INV-..." />
            </div>
            <div class="form-group">
            <label>Status</label>
            <select v-model="formData.status">
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="archived">Archived</option>
            </select>
            </div>
        </div>

        <div class="form-row">
             <div class="form-group">
                <label>Quantity On Hand</label>
                <input v-model.number="formData.quantity_on_hand" type="number" disabled title="Adjust via Purchases/Sales" />
                <small v-if="isEdit">Auto-calculated from ledger</small>
            </div>
             <div class="form-group">
                <label>Reorder Point</label>
                <input v-model.number="formData.reorder_point" type="number" min="0" />
            </div>
             <div class="form-group">
                <label>Unit</label>
                <input v-model="formData.unit" type="text" />
            </div>
        </div>

        <div class="form-group">
          <label>Location</label>
          <input v-model="formData.location" type="text" />
        </div>

        <div class="form-group">
          <label>Tags</label>
          <input v-model="formData.tags" type="text" />
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea v-model="formData.description" rows="3"></textarea>
        </div>

      </div>

      <!-- Purchases Tab -->
      <div class="modal-body" v-else-if="activeTab === 'purchases'">
          <div class="ledger-header">
              <h3>Purchase History</h3>
              <button class="btn-primary" @click="showPurchaseForm = !showPurchaseForm">
                  {{ showPurchaseForm ? 'Cancel' : '+ Record Purchase' }}
              </button>
          </div>

          <div v-if="showPurchaseForm" class="ledger-form">
              <div class="form-group">
                  <label>Vendor</label>
                  <select v-model="newPurchase.vendor_id">
                      <option disabled value="">Select Vendor</option>
                      <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                  </select>
              </div>
              <div class="form-row">
                  <div class="form-group">
                      <label>Date</label>
                      <input v-model="newPurchase.purchased_at" type="date" />
                  </div>
                  <div class="form-group">
                      <label>Qty</label>
                      <input v-model.number="newPurchase.quantity_purchased" type="number" min="1" />
                  </div>
                  <div class="form-group">
                      <label>Unit Cost</label>
                      <input v-model.number="newPurchase.unit_cost" type="number" min="0" step="0.01" />
                  </div>
              </div>
              <button class="btn-save" @click="savePurchase">Save Purchase</button>
          </div>

          <table class="ledger-table">
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
                  <tr v-if="!item.purchases || item.purchases.length === 0">
                      <td colspan="4" class="text-center">No purchases recorded.</td>
                  </tr>
              </tbody>
          </table>
      </div>

      <!-- Sales Tab -->
      <div class="modal-body" v-else-if="activeTab === 'sales'">
          <div class="ledger-header">
              <h3>Sales History</h3>
              <button class="btn-primary" @click="showSaleForm = !showSaleForm">
                  {{ showSaleForm ? 'Cancel' : '+ Record Sale' }}
              </button>
          </div>

          <div v-if="showSaleForm" class="ledger-form">
              <div class="form-group">
                  <label>Customer</label>
                  <select v-model="newSale.customer_id">
                      <option disabled value="">Select Customer</option>
                      <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
              </div>
              <div class="form-row">
                  <div class="form-group">
                      <label>Date</label>
                      <input v-model="newSale.sold_at" type="date" />
                  </div>
                  <div class="form-group">
                      <label>Qty</label>
                      <input v-model.number="newSale.quantity_sold" type="number" min="1" />
                  </div>
                  <div class="form-group">
                      <label>Price</label>
                      <input v-model.number="newSale.unit_price" type="number" min="0" step="0.01" />
                  </div>
              </div>
              <button class="btn-save" @click="saveSale">Save Sale</button>
          </div>

          <table class="ledger-table">
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
                  <tr v-if="!item.sales || item.sales.length === 0">
                      <td colspan="4" class="text-center">No sales recorded.</td>
                  </tr>
              </tbody>
          </table>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" @click="$emit('close')">Close</button>
        <button v-if="activeTab === 'details'" class="btn-save" @click="save">{{ isEdit ? 'Save Changes' : 'Create Item' }}</button>
      </div>
    </div>

    <PhotoGallery
      :show="showGallery"
      :photos="localPhotos"
      :startIndex="galleryIndex"
      @close="showGallery = false"
    />
  </div>
</template>

<style scoped>
/* Main Structure */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  border: 1px solid black;
  background: white;
  padding: 20px;
  border-radius: 8px;
  width: 700px; /* Wider for tables */
  max-width: 95%;
  max-height: 90vh;
  overflow-y: auto;
}

/* Headers */
.modal-header, .ledger-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.ledger-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    border-bottom: 1px solid black;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
}

.tab-btn.active {
    font-weight: bold;
    text-decoration: underline;
}

/* Forms */
.modal-body {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-height: 300px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

input, select, textarea {
    padding: 8px;
    border: 1px solid black;
    border-radius: 4px;
}

/* Ledger Tables */
.ledger-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.ledger-table th, .ledger-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.ledger-form {
    padding: 15px;
    border: 1px solid black;
    margin-bottom: 20px;
    border-radius: 4px;
}

/* Buttons */
.modal-footer {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel, .btn-save, .btn-primary {
  border: 1px solid black;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-save {
    font-weight: bold;
}

/* Photos */
.photos-toolbar {
    margin-bottom: 15px;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}

.photo-card {
    position: relative;
    border: 1px solid black;
    border-radius: 4px;
    overflow: hidden;
    aspect-ratio: 1;
}

.photo-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
}

.delete-photo-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    border: 1px solid black;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white; /* Ensure visibility */
}

.no-photos {
    font-style: italic;
    grid-column: 1 / -1;
    text-align: center;
    padding: 20px;
}

/* AI Button */
.btn-ai {
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #8e44ad;
    color: #8e44ad;
    font-weight: bold;
    border-radius: 4px;
    background: #fdf5ff;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-ai:disabled {
    opacity: 0.7;
    cursor: wait;
}
</style>