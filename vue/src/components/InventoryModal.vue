<script setup>
import { ref, watch, computed, inject } from 'vue';

const props = defineProps({
  show: Boolean,
  item: Object // If null, we are in Create mode
});

const emit = defineEmits(['close', 'save']);
const api = inject('$api') || (window.axios ? window.axios : null); // Fallback or injection

const activeTab = ref('details');
const fileInput = ref(null);
const uploading = ref(false);
const localPhotos = ref([]);

const formData = ref({
  title: '',
  sku: '',
  description: '',
  status: 'in_stock',
  quantity_on_hand: 0,
  reorder_point: 0,
  unit: 'each',
  location: '',
  tags: '' // Will parse to array on save
});

const isEdit = computed(() => !!props.item);

// Watch for item changes to populate form
watch(() => props.item, (newItem) => {
  if (newItem) {
    formData.value = {
      ...newItem,
      tags: newItem.tags ? (Array.isArray(newItem.tags) ? newItem.tags.join(', ') : newItem.tags) : ''
    };
    localPhotos.value = newItem.photos || [];
    activeTab.value = 'details';
  } else {
    // Reset for create
    formData.value = {
      title: '',
      sku: '',
      description: '',
      status: 'in_stock',
      quantity_on_hand: 0,
      reorder_point: 0,
      unit: 'each',
      location: '',
      tags: ''
    };
    localPhotos.value = [];
    activeTab.value = 'details';
  }
}, { immediate: true });

const save = () => {
  // Simple validation
  if (!formData.value.title) return alert('Title is required');

  const payload = {
    ...formData.value,
    tags: formData.value.tags ? formData.value.tags.split(',').map(t => t.trim()).filter(t => t) : []
  };
  
  emit('save', payload);
};

const triggerUpload = () => {
    fileInput.value.click();
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!isEdit.value) return alert('Please save the item before adding photos.');

    const uploadData = new FormData();
    uploadData.append('photo', file);

    uploading.value = true;
    try {
        // Use the injected API or global axios
        // Note: In setup script, we might need to import api directly if inject doesn't work as expected in all contexts
        // But let's assume api is available via props or global
        // For now, let's use the one from main.js if possible, or we need to import it here.
        // Better to import it directly to be safe.
        const { default: api } = await import('../axios');
        
        const response = await api.post(`/inventory-items/${props.item.id}/photos`, uploadData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        localPhotos.value.push(response.data);
    } catch (error) {
        alert('Failed to upload photo: ' + (error.response?.data?.message || error.message));
    } finally {
        uploading.value = false;
        event.target.value = null; // Reset input
    }
};

const deletePhoto = async (photoId) => {
    if (!confirm('Are you sure you want to delete this photo?')) return;
    
    try {
        const { default: api } = await import('../axios');
        await api.delete(`/photos/${photoId}`);
        localPhotos.value = localPhotos.value.filter(p => p.id !== photoId);
    } catch (error) {
        alert('Failed to delete photo');
    }
};

const getPhotoUrl = (path) => {
    // If path starts with http, return it
    if (path.startsWith('http')) return path;
    // Otherwise assume it's relative to backend root/storage
    // In Laravel, Storage::url() returns /storage/path
    // We need to prepend the backend URL if it's not on the same domain/port, 
    // but here NGINX proxies /storage to backend?
    // Wait, NGINX config doesn't have /storage location!
    // We need to add /storage location to NGINX config.
    return path;
};
</script>

<template>
  <div v-if="show" class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Item' : 'Add New Item' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <div class="tabs">
          <button 
            :class="['tab-btn', { active: activeTab === 'details' }]" 
            @click="activeTab = 'details'"
          >Details</button>
          <button 
            :class="['tab-btn', { active: activeTab === 'photos' }]" 
            @click="activeTab = 'photos'"
            :disabled="!isEdit"
            title="Save item first to add photos"
          >Photos</button>
      </div>
      
      <div class="modal-body" v-if="activeTab === 'details'">
        <div class="form-group">
          <label>Title *</label>
          <input v-model="formData.title" type="text" placeholder="Item Name" />
        </div>
        
        <div class="form-row">
            <div class="form-group">
            <label>SKU (Auto-generated if empty)</label>
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
                <input v-model.number="formData.quantity_on_hand" type="number" min="0" />
            </div>
             <div class="form-group">
                <label>Reorder Point</label>
                <input v-model.number="formData.reorder_point" type="number" min="0" />
            </div>
             <div class="form-group">
                <label>Unit</label>
                <input v-model="formData.unit" type="text" placeholder="each, lbs, kg" />
            </div>
        </div>

        <div class="form-group">
          <label>Location</label>
          <input v-model="formData.location" type="text" placeholder="e.g. Aisle 3, Shelf B" />
        </div>

        <div class="form-group">
          <label>Tags (comma separated)</label>
          <input v-model="formData.tags" type="text" placeholder="electronics, sale, fragile" />
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea v-model="formData.description" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-body" v-else-if="activeTab === 'photos'">
          <div class="photos-toolbar">
              <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" hidden />
              <button class="btn-primary" @click="triggerUpload" :disabled="uploading">
                  {{ uploading ? 'Uploading...' : '+ Add Photo' }}
              </button>
          </div>
          
          <div class="photos-grid">
              <div v-if="localPhotos.length === 0" class="no-photos">No photos yet.</div>
              <div v-for="photo in localPhotos" :key="photo.id" class="photo-card">
                  <img :src="photo.url" :alt="photo.caption" />
                  <button class="delete-photo-btn" @click="deletePhoto(photo.id)">&times;</button>
              </div>
          </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" @click="$emit('close')">Close</button>
        <button v-if="activeTab === 'details'" class="btn-save" @click="save">{{ isEdit ? 'Save Changes' : 'Create Item' }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Previous styles remain... */
.modal-backdrop {
  background-color:rgba(0, 0, 0, 0.5); /* No color, fully transparent but still blocks interaction */
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  /* background: rgba(0, 0, 0, 0.5); removed for no color */
  border: 1px solid black; /* To show it exists? or just let it be transparent but blocking? */
  /* If transparent, users won't see it covering. Let's add a border or outline if needed, but strictly "no color" means transparent bg. 
     However, modal needs some visibility. I'll leave background blank (transparent) or maybe a simple border. 
     Actually, let's keep it transparent but blocking. 
  */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  border: 1px solid black;
  background-color:white;
  padding: 20px;
  border-radius: 8px;
  width: 600px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

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
    border-radius: 4px;
}

.modal-footer {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel {
  border: 1px solid black;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-save {
  border: 1px solid black;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

/* New Tabs Styles */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 10px;
}

.tab-btn {
    background: none;
    border: 1px solid black;
    padding: 8px 16px;
    cursor: pointer;
    font-weight: 600;
    border-radius: 4px;
}

.tab-btn.active {
    font-weight: bold;
    text-decoration: underline;
}

.tab-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Photos Styles */
.photos-toolbar {
    margin-bottom: 15px;
}

.btn-primary {
    border: 1px solid black;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
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
}

.no-photos {
    font-style: italic;
    grid-column: 1 / -1;
    text-align: center;
    padding: 20px;
}
</style>
