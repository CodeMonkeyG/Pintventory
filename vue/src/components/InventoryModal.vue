<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import api from '../axios';
import PhotoGallery from './PhotoGallery.vue';
import { revokeBlobUrls, saveDraftPhotos, loadDraftPhotos, clearDraftPhotos } from '../utils/helpers';
import { useVendorStore } from '../stores/vendors';
import { useCustomerStore } from '../stores/customers';
import { useLocationStore } from '../stores/locations';
import { useDisplay } from 'vuetify';

const vendorStore = useVendorStore();
const customerStore = useCustomerStore();
const locationStore = useLocationStore();
const { mobile } = useDisplay();

const props = defineProps({
  show: Boolean,
  item: Object
});

const emit = defineEmits(['close', 'save', 'delete-photo', 'update:show']);

const activeTab = ref('details');
const fileInput = ref(null);
const isAnalyzing = ref(false);
const isMarketAnalyzing = ref(false);
const isFacebookAnalyzing = ref(false);
const isEtsyAnalyzing = ref(false);
const isUploading = ref(false);
const pendingPhotos = ref([]);
const localPhotos = ref([]);

const vendors = ref([]);
const customers = ref([]);

const formData = ref({
    title: '',
    sku: '',
    status: 'in_stock',
    item_type: 'standard',
    quantity_on_hand: 0,
    reorder_point: 0,
    unit: '',
    location: '',
    storage_location_id: null,
    tags: '',
    description: '',
    evaluation: '',
    market_analysis: null,
    facebook_analysis: null,
    etsy_analysis: null,
    source_links: [],
    ebay_listing_url: '',
    facebook_listing_url: '',
    etsy_listing_url: ''
});

const showGallery = ref(false);
const galleryIndex = ref(0);
const showFullEvaluation = ref(false);
const showFullMarketAnalysis = ref(false);
const showFullFacebookAnalysis = ref(false);
const showFullEtsyAnalysis = ref(false);

const isEdit = computed(() => !!props.item);
const draftKey = computed(() => isEdit.value ? `inventory_edit_${props.item.id}` : 'inventory_new');

// Persistence: Save text state to localStorage
watch([formData, () => props.show], ([newForm, show]) => {
    if (show) {
        localStorage.setItem('pintventory_active_modal', 'inventory');
        localStorage.setItem('pintventory_editing_id', isEdit.value ? props.item.id : 'new');
        localStorage.setItem(`pintventory_draft_${draftKey.value}`, JSON.stringify(newForm));
    } else {
        localStorage.removeItem('pintventory_active_modal');
        localStorage.removeItem('pintventory_editing_id');
    }
}, { deep: true });

// Persistence: Save photos to IndexedDB
watch(pendingPhotos, async (newPhotos) => {
    if (props.show) {
        const files = newPhotos.map(p => p.file);
        await saveDraftPhotos(draftKey.value, files);
    }
}, { deep: true });

// Persistence: Load state
onMounted(async () => {
    const savedForm = localStorage.getItem(`pintventory_draft_${draftKey.value}`);
    if (savedForm && !isEdit.value) { 
        try {
            const parsed = JSON.parse(savedForm);
            if (localStorage.getItem('pintventory_active_modal') === 'inventory') {
                formData.value = { ...formData.value, ...parsed };
            }
        } catch (e) { console.error('Draft restore failed', e); }
    }

    // Always check for photos if the modal is currently showing (or about to show)
    if (props.show) {
        loadPhotosFromDraft();
    }
});

const loadPhotosFromDraft = async () => {
    const savedPhotos = await loadDraftPhotos(draftKey.value);
    if (savedPhotos.length > 0 && pendingPhotos.value.length === 0) {
        pendingPhotos.value = savedPhotos.map(file => ({
            file,
            url: URL.createObjectURL(file)
        }));
    }
};

// Add a watch to load photos when 'show' becomes true (if not already mounted)
watch(() => props.show, (val) => {
    if (val) {
        locationStore.fetchItems();
        loadPhotosFromDraft();
    }
    if (!val) {
        const urls = pendingPhotos.value.map(p => p.url);
        revokeBlobUrls(urls);
        pendingPhotos.value = [];
        
        // Reset form data and state when modal closes
        formData.value = {
            title: '',
            sku: '',
            status: 'in_stock',
            item_type: 'standard',
            quantity_on_hand: 0,
            reorder_point: 0,
            unit: '',
            location: '',
            storage_location_id: null,
            tags: '',
            description: '',
            evaluation: '',
            market_analysis: null,
            facebook_analysis: null,
            etsy_analysis: null,
            source_links: [],
            ebay_listing_url: '',
            facebook_listing_url: '',
            etsy_listing_url: ''
        };
        localPhotos.value = [];
        showPurchaseForm.value = false;
        showSaleForm.value = false;
        showFullEvaluation.value = false;
        showFullMarketAnalysis.value = false;
        showFullFacebookAnalysis.value = false;
        showFullEtsyAnalysis.value = false;
        activeTab.value = 'details';
    }
});

const resizeImage = (file, maxPixels = 8000000) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                const currentPixels = width * height;

                if (currentPixels > maxPixels) {
                    const ratio = Math.sqrt(maxPixels / currentPixels);
                    width = Math.floor(width * ratio);
                    height = Math.floor(height * ratio);
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (blob) {
                        const resizedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(resizedFile);
                    } else {
                        reject(new Error('Canvas to Blob conversion failed'));
                    }
                }, 'image/jpeg', 0.85); // 0.85 quality to stay under 5MB for 8MP
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

const handleAiAutoFill = async () => {
    let file = null;
    if (pendingPhotos.value.length > 0) {
        file = pendingPhotos.value[0].file;
    } else if (localPhotos.value.length > 0) {
        try {
            const response = await fetch(localPhotos.value[0].url);
            const blob = await response.blob();
            file = new File([blob], 'photo.jpg', { type: blob.type });
        } catch (e) {
            console.error('Failed to fetch local photo for AI analysis', e);
            alert('Could not access the photo for AI analysis.');
            return;
        }
    }

    if (!file) {
        alert('Please upload a photo first.');
        return;
    }

    isAnalyzing.value = true;
    try {
        // Resize image if it's too large
        file = await resizeImage(file);

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/image-identify', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;
        if (data.title) formData.value.title = data.title;
        if (data.description) formData.value.description = data.description;
        if (data.item_type) {
            formData.value.item_type = data.item_type;
            if (data.item_type === 'unique') {
                formData.value.quantity_on_hand = 1;
                formData.value.unit = 'pcs';
            }
        }
        
        if (data.evaluation) {
            if (typeof data.evaluation === 'object') {
                // If it's an object (era, material, value), format it nicely
                let evalString = '';
                if (data.evaluation.era) evalString += `Era: ${data.evaluation.era}\n`;
                if (data.evaluation.material) evalString += `Material: ${data.evaluation.material}\n`;
                if (data.evaluation.value_estimation) evalString += `Value: ${data.evaluation.value_estimation}\n`;
                
                // If the object has other fields or is just a general evaluation
                if (evalString === '') {
                    evalString = JSON.stringify(data.evaluation, null, 2);
                }
                formData.value.evaluation = evalString.trim();
            } else {
                formData.value.evaluation = data.evaluation;
            }
        }

        if (data.tags) {
            const newTags = Array.isArray(data.tags) ? data.tags.join(', ') : data.tags;
            formData.value.tags = newTags;
        }

        if (data.source_links) {
            formData.value.source_links = data.source_links;
        }

        alert('Auto-fill complete!');
    } catch (error) {
        console.error(error);
        alert('AI Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isAnalyzing.value = false;
    }
};

const handleMarketAnalysis = async () => {
    let file = null;
    if (pendingPhotos.value.length > 0) {
        file = pendingPhotos.value[0].file;
    } else if (localPhotos.value.length > 0) {
        try {
            const response = await fetch(localPhotos.value[0].url);
            const blob = await response.blob();
            file = new File([blob], 'photo.jpg', { type: blob.type });
        } catch (e) {
            console.error('Failed to fetch local photo for Market analysis', e);
            alert('Could not access the photo for Market analysis.');
            return;
        }
    }

    if (!file) {
        alert('Please upload a photo first.');
        return;
    }

    isMarketAnalyzing.value = true;
    try {
        file = await resizeImage(file);

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/market-analyze', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        formData.value.market_analysis = response.data;
        showFullMarketAnalysis.value = true;
        
        alert('Market analysis complete!');
    } catch (error) {
        console.error(error);
        alert('Market Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isMarketAnalyzing.value = false;
    }
};

const handleFacebookAnalysis = async () => {
    let file = null;
    if (pendingPhotos.value.length > 0) {
        file = pendingPhotos.value[0].file;
    } else if (localPhotos.value.length > 0) {
        try {
            const response = await fetch(localPhotos.value[0].url);
            const blob = await response.blob();
            file = new File([blob], 'photo.jpg', { type: blob.type });
        } catch (e) {
            console.error('Failed to fetch local photo for Facebook analysis', e);
            alert('Could not access the photo for Facebook analysis.');
            return;
        }
    }

    if (!file) {
        alert('Please upload a photo first.');
        return;
    }

    isFacebookAnalyzing.value = true;
    try {
        file = await resizeImage(file);

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/facebook-analyze', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        formData.value.facebook_analysis = response.data;
        showFullFacebookAnalysis.value = true;
        
        alert('Facebook analysis complete!');
    } catch (error) {
        console.error(error);
        alert('Facebook Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isFacebookAnalyzing.value = false;
    }
};

const handleEtsyAnalysis = async () => {
    let file = null;
    if (pendingPhotos.value.length > 0) {
        file = pendingPhotos.value[0].file;
    } else if (localPhotos.value.length > 0) {
        try {
            const response = await fetch(localPhotos.value[0].url);
            const blob = await response.blob();
            file = new File([blob], 'photo.jpg', { type: blob.type });
        } catch (e) {
            console.error('Failed to fetch local photo for Etsy analysis', e);
            alert('Could not access the photo for Etsy analysis.');
            return;
        }
    }

    if (!file) {
        alert('Please upload a photo first.');
        return;
    }

    isEtsyAnalyzing.value = true;
    try {
        file = await resizeImage(file);

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/etsy-analyze', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        formData.value.etsy_analysis = response.data;
        showFullEtsyAnalysis.value = true;
        
        alert('Etsy analysis complete!');
    } catch (error) {
        console.error(error);
        alert('Etsy Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isEtsyAnalyzing.value = false;
    }
};

const save = async () => {
  if (!formData.value.title) return alert('Title is required');

  const payload = {
    ...formData.value,
    tags: formData.value.tags ? formData.value.tags.split(',').map(t => t.trim()).filter(t => t) : []
  };
  
  emit('save', payload, pendingPhotos.value.map(p => p.file));
  
  // Clear persistence after emission (Parent handles success check usually, but we clear now to avoid restore on success)
  localStorage.removeItem(`pintventory_draft_${draftKey.value}`);
  await clearDraftPhotos(draftKey.value);
};

const triggerUpload = () => fileInput.value.click();

const handleFileUpload = async (event) => {
    let file = event.target.files[0];
    if (!file) return;
    
    isUploading.value = true;
    try {
        // Resize image immediately to save memory on mobile
        file = await resizeImage(file);
        const previewUrl = URL.createObjectURL(file);
        pendingPhotos.value.push({ file, url: previewUrl });
    } catch (e) {
        console.error("Image processing failed", e);
        alert("Failed to process image.");
    } finally {
        isUploading.value = false;
        event.target.value = null;
    }
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

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
};

const closeModal = () => {
  emit('close');
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

      <v-progress-linear
        v-if="isAnalyzing || isMarketAnalyzing || isFacebookAnalyzing || isEtsyAnalyzing || isUploading"
        indeterminate
        color="secondary"
        height="2"
      ></v-progress-linear>

      <v-tabs v-model="activeTab" v-if="isEdit" color="primary" grow>
        <v-tab value="details">Details</v-tab>
        <v-tab value="purchases">Purchases</v-tab>
        <v-tab value="sales">Sales</v-tab>
      </v-tabs>

      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
        <v-window v-model="activeTab">
          <!-- Details & Photos Tab -->
          <v-window-item value="details">
            <div class="mb-0">
              <!-- Expandable AI Evaluation -->
              <v-expand-transition>
                <v-card
                  v-if="formData.evaluation"
                  variant="tonal"
                  color="success"
                  class="mt-0 mb-6"
                >
                  <v-card-text class="pa-3">
                    <div class="d-flex align-center mb-1 cursor-pointer" @click="showFullEvaluation = !showFullEvaluation">
                      <v-icon size="16" class="mr-2">mdi-information-outline</v-icon>
                      <span class="text-caption font-weight-bold uppercase">AI Evaluation</span>
                      <v-spacer />
                      <v-icon :icon="showFullEvaluation ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" />
                    </div>
                    <div :class="showFullEvaluation ? '' : 'text-truncate-2'" class="text-body-2 white-space-pre-wrap">
                      {{ formData.evaluation }}
                    </div>

                    <div v-if="!showFullEvaluation" class="text-center text-caption mt-1 font-italic opacity-70 cursor-pointer" @click="showFullEvaluation = !showFullEvaluation">
                      Click to expand
                    </div>
                  </v-card-text>
                </v-card>
              </v-expand-transition>

              <!-- Expandable Market Analysis -->
              <v-expand-transition>
                <v-card
                  v-if="formData.market_analysis"
                  variant="tonal"
                  color="amber-darken-3"
                  class="mt-3 mb-6"
                >
                  <v-card-text class="pa-3">
                    <div class="d-flex align-center mb-1 cursor-pointer" @click="showFullMarketAnalysis = !showFullMarketAnalysis">
                      <v-icon size="16" class="mr-2">mdi-chart-line</v-icon>
                      <span class="text-caption font-weight-bold uppercase">eBay Market Analysis</span>
                      <v-spacer />
                      <v-icon :icon="showFullMarketAnalysis ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" />
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-2 cursor-pointer" @click="showFullMarketAnalysis = !showFullMarketAnalysis">
                      <v-chip size="x-small" color="amber-darken-4">List: {{ formData.market_analysis.listing_price_range }}</v-chip>
                      <v-chip size="x-small" color="success">Sold: {{ formData.market_analysis.sold_price_range }}</v-chip>
                      <v-chip size="x-small" color="primary">STR: {{ formData.market_analysis.sell_through_rate }}</v-chip>
                      <v-btn 
                        v-if="formData.market_analysis.market_url"
                        :href="formData.market_analysis.market_url" 
                        target="_blank" 
                        variant="text" 
                        size="x-small" 
                        color="primary"
                        prepend-icon="mdi-launch"
                        @click.stop
                        class="ml-auto"
                      >
                        View Live Listings
                      </v-btn>
                    </div>

                    <div v-if="showFullMarketAnalysis">
                      <div class="text-caption font-weight-bold mt-2">Suggested Title:</div>
                      <div class="text-body-2 mb-2">{{ formData.market_analysis.suggested_ebay_title }}</div>
                      
                      <div class="text-caption font-weight-bold mt-2">Flipping Advice:</div>
                      <div class="text-body-2 mb-2 white-space-pre-wrap">{{ formData.market_analysis.flipping_advice }}</div>

                      <div class="mt-4 p-3 bg-grey-darken-4 rounded-lg position-relative" v-if="formData.market_analysis.listing_copy">
                        <div class="text-caption font-weight-bold mb-1 d-flex align-center">
                          <v-icon size="14" class="mr-1">mdi-content-copy</v-icon>
                          Listing Template
                          <v-spacer />
                          <v-btn icon="mdi-content-copy" variant="text" size="x-small" @click.stop="copyToClipboard(formData.market_analysis.listing_copy)" title="Copy to clipboard" />
                        </div>
                        <div class="text-body-2 white-space-pre-wrap font-italic text-grey-lighten-1">
                          {{ formData.market_analysis.listing_copy }}
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center text-caption mt-1 font-italic opacity-70">
                      Click to expand strategy
                    </div>
                  </v-card-text>
                </v-card>
              </v-expand-transition>

              <!-- Expandable Facebook Analysis -->
              <v-expand-transition>
                <v-card
                  v-if="formData.facebook_analysis"
                  variant="tonal"
                  color="blue-darken-2"
                  class="mt-3 mb-6"
                >
                  <v-card-text class="pa-3">
                    <div class="d-flex align-center mb-1 cursor-pointer" @click="showFullFacebookAnalysis = !showFullFacebookAnalysis">
                      <v-icon size="16" class="mr-2">mdi-facebook</v-icon>
                      <span class="text-caption font-weight-bold uppercase">FB Marketplace Analysis</span>
                      <v-spacer />
                      <v-icon :icon="showFullFacebookAnalysis ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" />
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-2 cursor-pointer" @click="showFullFacebookAnalysis = !showFullFacebookAnalysis">
                      <v-chip size="x-small" color="blue-darken-3">Local: {{ formData.facebook_analysis.local_price_estimate }}</v-chip>
                      <v-chip size="x-small" color="indigo">Target: {{ formData.facebook_analysis.target_audience }}</v-chip>
                      <v-btn 
                        v-if="formData.facebook_analysis.market_url"
                        :href="formData.facebook_analysis.market_url" 
                        target="_blank" 
                        variant="text" 
                        size="x-small" 
                        color="white"
                        prepend-icon="mdi-launch"
                        @click.stop
                        class="ml-auto"
                      >
                        Search Marketplace
                      </v-btn>
                    </div>

                    <div v-if="showFullFacebookAnalysis">
                      <div class="text-caption font-weight-bold mt-2">Suggested Groups:</div>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                        <v-chip v-for="group in formData.facebook_analysis.suggested_groups" :key="group" size="x-small" variant="outlined">
                          {{ group }}
                        </v-chip>
                      </div>
                      
                      <div class="text-caption font-weight-bold mt-2">Safety & Scams:</div>
                      <div class="text-body-2 mb-2">{{ formData.facebook_analysis.safety_tips }}</div>

                      <div class="text-caption font-weight-bold mt-2">Listing Strategy:</div>
                      <div class="text-body-2 mb-2 white-space-pre-wrap">{{ formData.facebook_analysis.listing_strategy }}</div>

                      <div class="mt-4 p-3 bg-grey-darken-4 rounded-lg position-relative" v-if="formData.facebook_analysis.listing_copy">
                        <div class="text-caption font-weight-bold mb-1 d-flex align-center">
                          <v-icon size="14" class="mr-1">mdi-content-copy</v-icon>
                          FB Listing Copy
                          <v-spacer />
                          <v-btn icon="mdi-content-copy" variant="text" size="x-small" @click.stop="copyToClipboard(formData.facebook_analysis.listing_copy)" title="Copy to clipboard" />
                        </div>
                        <div class="text-body-2 white-space-pre-wrap font-italic text-grey-lighten-1">
                          {{ formData.facebook_analysis.listing_copy }}
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center text-caption mt-1 font-italic opacity-70">
                      Click to expand strategy
                    </div>
                  </v-card-text>
                </v-card>
              </v-expand-transition>

              <!-- Expandable Etsy Analysis -->
              <v-expand-transition>
                <v-card
                  v-if="formData.etsy_analysis"
                  variant="tonal"
                  color="orange-darken-3"
                  class="mt-3 mb-6"
                >
                  <v-card-text class="pa-3">
                    <div class="d-flex align-center mb-1 cursor-pointer" @click="showFullEtsyAnalysis = !showFullEtsyAnalysis">
                      <v-icon size="16" class="mr-2">mdi-storefront-outline</v-icon>
                      <span class="text-caption font-weight-bold uppercase">Etsy Market Analysis</span>
                      <v-spacer />
                      <v-icon :icon="showFullEtsyAnalysis ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" />
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-2 cursor-pointer" @click="showFullEtsyAnalysis = !showFullEtsyAnalysis">
                      <v-chip size="x-small" color="orange-darken-4">Etsy: {{ formData.etsy_analysis.etsy_price_estimate }}</v-chip>
                      <v-chip size="x-small" color="deep-orange-darken-1">Target: {{ formData.etsy_analysis.target_persona }}</v-chip>
                      <v-btn 
                        v-if="formData.etsy_analysis.market_url"
                        :href="formData.etsy_analysis.market_url" 
                        target="_blank" 
                        variant="text" 
                        size="x-small" 
                        color="white"
                        prepend-icon="mdi-launch"
                        @click.stop
                        class="ml-auto"
                      >
                        Search Etsy
                      </v-btn>
                    </div>

                    <div v-if="showFullEtsyAnalysis">
                      <div class="text-caption font-weight-bold mt-2">13 Etsy Tags:</div>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                        <v-chip v-for="tag in formData.etsy_analysis.seo_tags" :key="tag" size="x-small" variant="outlined">
                          {{ tag }}
                        </v-chip>
                      </div>
                      
                      <div class="text-caption font-weight-bold mt-2">Shipping Strategy:</div>
                      <div class="text-body-2 mb-2">{{ formData.etsy_analysis.shipping_advice }}</div>

                      <div class="text-caption font-weight-bold mt-2">Curation & Aesthetic:</div>
                      <div class="text-body-2 mb-2 white-space-pre-wrap">{{ formData.etsy_analysis.curation_strategy }}</div>

                      <div class="mt-4 p-3 bg-grey-darken-4 rounded-lg position-relative" v-if="formData.etsy_analysis.listing_copy">
                        <div class="text-caption font-weight-bold mb-1 d-flex align-center">
                          <v-icon size="14" class="mr-1">mdi-content-copy</v-icon>
                          Etsy Listing Copy
                          <v-spacer />
                          <v-btn icon="mdi-content-copy" variant="text" size="x-small" @click.stop="copyToClipboard(formData.etsy_analysis.listing_copy)" title="Copy to clipboard" />
                        </div>
                        <div class="text-body-2 white-space-pre-wrap font-italic text-grey-lighten-1">
                          {{ formData.etsy_analysis.listing_copy }}
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center text-caption mt-1 font-italic opacity-70">
                      Click to expand strategy
                    </div>
                  </v-card-text>
                </v-card>
              </v-expand-transition>
            </div>

            <div class="mb-6">
              <div class="d-flex justify-space-between align-center mb-3">
                <h3 class="text-subtitle-1 font-weight-bold">Photos</h3>
                <div class="d-flex align-center gap-2">
                  <v-menu v-if="pendingPhotos.length > 0 || localPhotos.length > 0" :close-on-content-click="false">
                    <template v-slot:activator="{ props }">
                      <v-btn
                        variant="tonal"
                        size="small"
                        color="secondary"
                        prepend-icon="mdi-robot"
                        append-icon="mdi-chevron-down"
                        v-bind="props"
                      >
                        AI Tools
                      </v-btn>
                    </template>
                    <v-list density="comfortable" style="min-width: 200px;">
                      <v-list-item
                        @click="handleAiAutoFill"
                        :disabled="isAnalyzing"
                        density="comfortable"
                      >
                        <template v-slot:prepend>
                          <v-progress-circular v-if="isAnalyzing" indeterminate size="20" width="2" color="success" class="mr-3" />
                          <v-icon v-else color="success">mdi-auto-fix</v-icon>
                        </template>
                        <v-list-item-title>Quick AI Analysis & Auto-Fill</v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        @click="handleMarketAnalysis"
                        :disabled="isMarketAnalyzing"
                        density="comfortable"
                      >
                        <template v-slot:prepend>
                          <v-progress-circular v-if="isMarketAnalyzing" indeterminate size="20" width="2" color="amber-darken-3" class="mr-3" />
                          <v-icon v-else color="amber-darken-3">mdi-shopping-outline</v-icon>
                        </template>
                        <v-list-item-title>eBay Market Check</v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        @click="handleFacebookAnalysis"
                        :disabled="isFacebookAnalyzing"
                        density="comfortable"
                      >
                        <template v-slot:prepend>
                          <v-progress-circular v-if="isFacebookAnalyzing" indeterminate size="20" width="2" color="blue-darken-2" class="mr-3" />
                          <v-icon v-else color="blue-darken-2">mdi-facebook</v-icon>
                        </template>
                        <v-list-item-title>FB Market Check</v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        @click="handleEtsyAnalysis"
                        :disabled="isEtsyAnalyzing"
                        density="comfortable"
                      >
                        <template v-slot:prepend>
                          <v-progress-circular v-if="isEtsyAnalyzing" indeterminate size="20" width="2" color="orange-darken-3" class="mr-3" />
                          <v-icon v-else color="orange-darken-3">mdi-storefront-outline</v-icon>
                        </template>
                        <v-list-item-title>Etsy Market Check</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                  <v-btn
                    variant="tonal"
                    size="small"
                    color="primary"
                    prepend-icon="mdi-camera"
                    @click="triggerUpload"
                    :loading="isUploading"
                  >
                    Add Photo
                  </v-btn>
                </div>
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
                <v-select
                  v-model="formData.item_type"
                  label="Inventory Type *"
                  :items="[
                    { title: 'Standard (Bulk/Reorderable)', value: 'standard' },
                    { title: 'Unique (One-of-a-kind/Vintage)', value: 'unique' }
                  ]"
                  variant="outlined"
                  density="compact"
                  required
                  @update:model-value="(val) => { if (val === 'unique') { formData.quantity_on_hand = 1; formData.unit = 'pcs'; } }"
                />
              </v-col>

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
                  :disabled="formData.item_type === 'unique'"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="6" sm="4" v-if="formData.item_type === 'standard'">
                <v-text-field
                  v-model.number="formData.reorder_point"
                  label="Reorder Pt"
                  type="number"
                  min="0"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="12" :sm="formData.item_type === 'standard' ? 4 : 6">
                <v-text-field
                  v-model="formData.unit"
                  label="Unit (e.g. pcs, sets)"
                  variant="outlined"
                  density="compact"
                />
              </v-col>

              <v-col cols="12">
                <div class="d-flex align-center gap-2">
                  <v-select
                    v-model="formData.storage_location_id"
                    label="Storage Location"
                    :items="locationStore.items"
                    item-title="name"
                    item-value="id"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-map-marker"
                    clearable
                    class="flex-grow-1"
                  />
                  <v-btn
                    icon="mdi-cog"
                    variant="text"
                    size="small"
                    to="/storage-locations"
                    title="Manage Locations"
                    class="mb-5"
                  />
                </div>
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
                  v-model="formData.description"
                  label="Description"
                  rows="2"
                  variant="outlined"
                  density="compact"
                  auto-grow
                />
              </v-col>

              <v-col cols="12">
                <div class="text-subtitle-2 font-weight-bold mb-2">My Listings</div>
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field
                      v-model="formData.ebay_listing_url"
                      label="eBay Listing URL"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-link"
                      placeholder="https://www.ebay.com/itm/..."
                    >
                      <template v-slot:append-inner v-if="formData.ebay_listing_url">
                        <v-btn icon="mdi-launch" variant="text" size="x-small" :href="formData.ebay_listing_url" target="_blank" @click.stop title="Visit Listing" />
                      </template>
                    </v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="formData.facebook_listing_url"
                      label="Facebook Listing URL"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-link"
                      placeholder="https://www.facebook.com/marketplace/item/..."
                    >
                      <template v-slot:append-inner v-if="formData.facebook_listing_url">
                        <v-btn icon="mdi-launch" variant="text" size="x-small" :href="formData.facebook_listing_url" target="_blank" @click.stop title="Visit Listing" />
                      </template>
                    </v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="formData.etsy_listing_url"
                      label="Etsy Listing URL"
                      variant="outlined"
                      density="compact"
                      prepend-inner-icon="mdi-link"
                      placeholder="https://www.etsy.com/listing/..."
                    >
                      <template v-slot:append-inner v-if="formData.etsy_listing_url">
                        <v-btn icon="mdi-launch" variant="text" size="x-small" :href="formData.etsy_listing_url" target="_blank" @click.stop title="Visit Listing" />
                      </template>
                    </v-text-field>
                  </v-col>
                </v-row>
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
.gap-2 {
  gap: 8px;
}
.gap-3 {
  gap: 12px;
}
.cursor-pointer {
  cursor: pointer;
}
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.white-space-pre-wrap {
  white-space: pre-wrap;
}
.opacity-70 {
  opacity: 0.7;
}
</style>