<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import api from '../axios';
import PhotoGallery from './PhotoGallery.vue';
import { revokeBlobUrls, saveDraftPhotos, loadDraftPhotos, clearDraftPhotos, resizeImage } from '../utils/helpers';
import { useVendorStore } from '../stores/vendors';
import { useCustomerStore } from '../stores/customers';
import { useLocationStore } from '../stores/locations';
import { useDisplay } from 'vuetify';
import QrLabel from './QrLabel.vue';

const vendorStore = useVendorStore();
const customerStore = useCustomerStore();
const locationStore = useLocationStore();
const { mobile } = useDisplay();

const showQrModal = ref(false);

const props = defineProps({
  show: Boolean,
  item: Object
});

const emit = defineEmits(['close', 'save', 'delete-photo', 'update:show']);

const activeTab = ref('details');
const fileInput = ref(null);
const cameraInput = ref(null);
const isAnalyzing = ref(false);
const isMarketAnalyzing = ref(false);
const isFacebookAnalyzing = ref(false);
const isEtsyAnalyzing = ref(false);
const isUploading = ref(false);
const pendingPhotos = ref([]);
const localPhotos = ref([]);

const formData = ref({
    title: '',
    sku: '',
    status: 'in_stock',
    item_type: 'standard',
    quantity_on_hand: 0,
    reorder_point: 0,
    unit: 'pcs',
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

const printLabel = () => {
    const printable = document.getElementById('qr-printable');
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Label</title>');
    printWindow.document.write('<style>body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; } @page { margin: 0; size: auto; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printable.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    // Wait for content to load for potential images
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
};

const isEdit = computed(() => !!props.item);
const draftKey = computed(() => isEdit.value ? `inventory_edit_${props.item.id}` : 'inventory_new');

const clearDraft = async () => {
    localStorage.removeItem(`pintventory_draft_${draftKey.value}`);
    await clearDraftPhotos(draftKey.value);
};

const restoreDraft = () => {
    const savedForm = localStorage.getItem(`pintventory_draft_${draftKey.value}`);
    if (savedForm) {
        try {
            const parsed = JSON.parse(savedForm);
            // Merge draft with current formData (preserving any item-specific fields if it's an edit)
            formData.value = { ...formData.value, ...parsed };
        } catch (e) { 
            console.error('Draft restore failed', e); 
        }
    }
};

watch(() => props.item, (newItem) => {
    // Reset to defaults first
    formData.value = {
        title: '',
        sku: '',
        status: 'in_stock',
        item_type: 'standard',
        quantity_on_hand: 0,
        reorder_point: 0,
        unit: 'pcs',
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

    if (newItem) {
        // Map newItem to formData
        formData.value = {
            title: newItem.title || '',
            sku: newItem.sku || '',
            status: newItem.status || 'in_stock',
            item_type: newItem.item_type || 'standard',
            quantity_on_hand: newItem.quantity_on_hand || 0,
            reorder_point: newItem.reorder_point || 0,
            unit: newItem.unit || 'pcs',
            location: newItem.location || '',
            storage_location_id: newItem.storage_location_id || null,
            tags: Array.isArray(newItem.tags) ? newItem.tags.join(', ') : (newItem.tags || ''),
            description: newItem.description || '',
            evaluation: newItem.evaluation || '',
            market_analysis: newItem.market_analysis || null,
            facebook_analysis: newItem.facebook_analysis || null,
            etsy_analysis: newItem.etsy_analysis || null,
            source_links: newItem.source_links || [],
            ebay_listing_url: newItem.ebay_listing_url || '',
            facebook_listing_url: newItem.facebook_listing_url || '',
            etsy_listing_url: newItem.etsy_listing_url || ''
        };
        if (newItem.photos) {
            localPhotos.value = [...newItem.photos];
        }
    }
    
    // Draft restoration is now handled in the 'show' watch to prevent race conditions
    // and accidental clearing on mount.
}, { immediate: true });

// Persistence: Save text state to localStorage
watch(formData, (newForm) => {
    if (props.show) {
        localStorage.setItem(`pintventory_draft_${draftKey.value}`, JSON.stringify(newForm));
    }
}, { deep: true });

// Persistence: Save photos to IndexedDB
watch(pendingPhotos, async (newPhotos) => {
    if (props.show) {
        const files = newPhotos.map(p => p.file);
        await saveDraftPhotos(draftKey.value, files);
    }
}, { deep: true });

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
watch(() => props.show, async (val) => {
    if (val) {
        locationStore.fetchItems();
        vendorStore.fetchAllVendors();
        customerStore.fetchAllCustomers();
        
        // Always attempt to restore draft when modal opens
        restoreDraft();
        await loadPhotosFromDraft();
    }
    if (!val) {
        const urls = pendingPhotos.value.map(p => p.url);
        revokeBlobUrls(urls);
        pendingPhotos.value = [];
        localPhotos.value = [];
        
        // Reset state when modal closes to prevent flashing next time
        formData.value = {
            title: '',
            sku: '',
            status: 'in_stock',
            item_type: 'standard',
            quantity_on_hand: 0,
            reorder_point: 0,
            unit: 'pcs',
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

        showPurchaseForm.value = false;
        showSaleForm.value = false;
        showFullEvaluation.value = false;
        showFullMarketAnalysis.value = false;
        showFullFacebookAnalysis.value = false;
        showFullEtsyAnalysis.value = false;
        activeTab.value = 'details';

        if (!isEdit.value) {
            // Clear persistence on close for new items as per feedback
            // This ensures next time "Add Item" is clicked it starts fresh
            // unless the browser crashed while it was open.
            await clearDraft();
        }
    }
});

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
        // file = await resizeImage(file);

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
        // file = await resizeImage(file);

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
        // file = await resizeImage(file);

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
        // file = await resizeImage(file);

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
const triggerCamera = () => cameraInput.value.click();

const handleFileUpload = async (event) => {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;
    
    isUploading.value = true;
    try {
        for (const file of files) {
            // Resize image (currently returns original file as per helpers.js)
            const processedFile = await resizeImage(file);
            const previewUrl = URL.createObjectURL(processedFile);
            pendingPhotos.value.push({ file: processedFile, url: previewUrl });
        }
    } catch (e) {
        console.error("Image processing failed", e);
        alert("Failed to process image(s).");
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
        // Revoke the blob URL to free memory
        if (photo.url) URL.revokeObjectURL(photo.url);
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

const getMarketUrl = (platform, query) => {
    if (!query) return '#';
    const encodedQuery = encodeURIComponent(query);
    switch (platform) {
        case 'ebay': return `https://www.ebay.com/sch/i.html?_nkw=${encodedQuery}`;
        case 'facebook': return `https://www.facebook.com/marketplace/search/?query=${encodedQuery}`;
        case 'offerup': return `https://offerup.com/search?q=${encodedQuery}`;
        case 'etsy': return `https://www.etsy.com/search?q=${encodedQuery}`;
        default: return '#';
    }
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
    <v-card :rounded="mobile ? '0' : 'lg'" class="d-flex flex-column" :style="mobile ? 'height: 100dvh;' : 'max-height: 90vh;'">
      <v-toolbar color="primary" :density="mobile ? 'comfortable' : 'default'">
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>
          {{ isEdit ? (mobile && activeTab !== 'details' ? activeTab.charAt(0).toUpperCase() + activeTab.slice(1) : (isEdit ? 'Edit Item' : 'Add Item')) : 'Add New Item' }}
        </v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn 
          variant="text" 
          @click="save" 
          :loading="isUploading"
          v-if="activeTab === 'details'"
          prepend-icon="mdi-check"
          class="px-4"
        >
          Save
        </v-btn>
        
        <template v-slot:extension v-if="isEdit">
          <v-tabs v-model="activeTab" grow color="white">
            <v-tab value="details">Details</v-tab>
            <v-tab value="purchases">Purchases</v-tab>
            <v-tab value="sales">Sales</v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-progress-linear
        v-if="isAnalyzing || isMarketAnalyzing || isFacebookAnalyzing || isEtsyAnalyzing || isUploading"
        indeterminate
        color="secondary"
        height="2"
      ></v-progress-linear>

      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'" class="flex-grow-1 overflow-y-auto">
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
                  variant="outlined"
                  class="mt-3 mb-6 bg-surface"
                  style="border-color: rgba(var(--v-border-color), 0.25) !important;"
                >
                  <v-card-text class="pa-3">
                    <!-- Legacy eBay Analysis -->
                    <template v-if="formData.market_analysis.listing_price_range || formData.market_analysis.sold_price_range">
                      <div class="d-flex align-center mb-3 cursor-pointer" @click="showFullMarketAnalysis = !showFullMarketAnalysis">
                        <v-icon size="18" class="mr-2" color="primary">mdi-chart-line</v-icon>
                        <span class="text-caption font-weight-bold uppercase text-grey-darken-1">eBay Market Analysis</span>
                        <v-spacer />
                        <v-icon :icon="showFullMarketAnalysis ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="16" color="grey" />
                      </div>
                      
                      <div class="d-flex flex-wrap gap-2 mb-2 cursor-pointer" @click="showFullMarketAnalysis = !showFullMarketAnalysis">
                        <v-chip v-if="formData.market_analysis.listing_price_range" size="x-small" variant="flat" color="secondary" class="text-caption">List: {{ formData.market_analysis.listing_price_range }}</v-chip>
                        <v-chip v-if="formData.market_analysis.sold_price_range" size="x-small" variant="flat" color="success" class="text-caption">Sold: {{ formData.market_analysis.sold_price_range }}</v-chip>
                        <v-chip v-if="formData.market_analysis.sell_through_rate" size="x-small" variant="flat" color="primary" class="text-caption">STR: {{ formData.market_analysis.sell_through_rate }}</v-chip>
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
                          View Live
                        </v-btn>
                      </div>

                      <div v-if="showFullMarketAnalysis">
                        <div v-if="formData.market_analysis.suggested_ebay_title" class="text-caption font-weight-bold mt-2">Suggested Title:</div>
                        <div v-if="formData.market_analysis.suggested_ebay_title" class="text-body-2 mb-2">{{ formData.market_analysis.suggested_ebay_title }}</div>
                        
                        <div v-if="formData.market_analysis.flipping_advice" class="text-caption font-weight-bold mt-2">Flipping Advice:</div>
                        <div v-if="formData.market_analysis.flipping_advice" class="text-body-2 mb-2 white-space-pre-wrap">{{ formData.market_analysis.flipping_advice }}</div>

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
                    </template>

                    <!-- New Multi-Market Analysis (eBay, FB, OfferUp, Etsy) -->
                    <template v-else-if="formData.market_analysis.ebay || formData.market_analysis.facebook">
                      <div class="d-flex align-center mb-3">
                        <v-icon size="18" class="mr-2" color="grey-darken-1">mdi-chart-line</v-icon>
                        <span class="text-caption font-weight-bold uppercase text-grey-darken-1">Multi-Market Evaluation</span>
                        <v-spacer />
                        <v-btn
                          variant="text"
                          size="x-small"
                          color="primary"
                          :append-icon="showFullMarketAnalysis ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                          @click="showFullMarketAnalysis = !showFullMarketAnalysis"
                        >
                          Details
                        </v-btn>
                      </div>

                      <v-row dense>
                        <v-col v-for="(data, platform) in formData.market_analysis" :key="platform" cols="6" sm="3">
                          <v-card variant="outlined" class="pa-2 fill-height bg-surface" style="border-color: rgba(var(--v-border-color), 0.15) !important;">
                            <div class="d-flex justify-space-between align-center mb-1">
                                <span class="text-caption font-weight-bold text-grey text-uppercase">{{ platform }}</span>
                                <v-btn
                                    icon="mdi-open-in-new"
                                    size="x-small"
                                    variant="text"
                                    color="primary"
                                    :href="getMarketUrl(platform, data.query)"
                                    target="_blank"
                                    @click.stop
                                    density="compact"
                                ></v-btn>
                            </div>
                            <div class="text-body-2 font-weight-black">{{ data.range }}</div>
                          </v-card>
                        </v-col>
                      </v-row>
                    </template>
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
                  <v-btn
                    v-if="isEdit"
                    variant="tonal"
                    size="small"
                    color="primary"
                    prepend-icon="mdi-qrcode"
                    @click="showQrModal = true"
                  >
                    Print Label
                  </v-btn>

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

                  <v-menu v-if="!mobile">
                    <template v-slot:activator="{ props }">
                      <v-btn
                        variant="tonal"
                        size="small"
                        color="primary"
                        prepend-icon="mdi-plus"
                        v-bind="props"
                        :loading="isUploading"
                      >
                        Add Photo
                      </v-btn>
                    </template>
                    <v-list density="comfortable">
                      <v-list-item @click="triggerCamera" prepend-icon="mdi-camera">
                        <v-list-item-title>Take Photo</v-list-item-title>
                      </v-list-item>
                      <v-list-item @click="triggerUpload" prepend-icon="mdi-image-multiple">
                        <v-list-item-title>Choose from Gallery</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </div>
              </div>
              <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" multiple hidden />
              <input type="file" ref="cameraInput" @change="handleFileUpload" accept="image/*" capture="environment" hidden />

              <div v-if="mobile" :class="(localPhotos.length > 0 || pendingPhotos.length > 0) ? 'd-flex gap-2 mb-4' : 'd-flex flex-column gap-3 mb-6'">
                <v-btn
                  color="primary"
                  :size="(localPhotos.length > 0 || pendingPhotos.length > 0) ? 'default' : 'large'"
                  prepend-icon="mdi-camera"
                  @click="triggerCamera"
                  class="text-none flex-grow-1"
                  elevation="2"
                >
                  {{ (localPhotos.length > 0 || pendingPhotos.length > 0) ? 'Take' : 'Take Photo' }}
                </v-btn>
                <v-btn
                  color="primary"
                  variant="tonal"
                  :size="(localPhotos.length > 0 || pendingPhotos.length > 0) ? 'default' : 'large'"
                  prepend-icon="mdi-image-multiple"
                  @click="triggerUpload"
                  class="text-none flex-grow-1"
                >
                  {{ (localPhotos.length > 0 || pendingPhotos.length > 0) ? 'Gallery' : 'Choose from Gallery' }}
                </v-btn>
              </div>

              <div class="d-flex flex-nowrap gap-3 pb-2 overflow-x-auto" :style="(!mobile || localPhotos.length > 0 || pendingPhotos.length > 0) ? 'min-height: 100px;' : ''">
                <div v-if="!mobile && localPhotos.length === 0 && pendingPhotos.length === 0" class="w-100 d-flex flex-column align-center justify-center border-dashed rounded-lg py-8 text-grey">
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
                    :items="vendorStore.allVendors"
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
                    :items="customerStore.allCustomers"
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
    </v-card>
  </v-dialog>

  <PhotoGallery
    :show="showGallery"
    :photos="localPhotos"
    :startIndex="galleryIndex"
    @close="showGallery = false"
  />

  <!-- QR Code Print Dialog -->
  <v-dialog v-model="showQrModal" max-width="350">
    <v-card>
      <v-card-title class="d-flex align-center">
          Label Preview
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" size="small" @click="showQrModal = false"></v-btn>
      </v-card-title>
      <v-card-text class="d-flex flex-column align-center">
          <div id="qr-printable" class="bg-white pa-4 rounded border">
              <QrLabel 
                  v-if="item" 
                  :id="item.id" 
                  type="item" 
                  :title="formData.title" 
                  :sku="formData.sku" 
                  :size="200"
              />
          </div>
          <div class="text-caption text-grey mt-4 text-center">
              This label can be scanned by the Pintventory app to quickly open this item.
          </div>
      </v-card-text>
      <v-card-actions class="pa-4">
          <v-btn block color="primary" @click="printLabel" prepend-icon="mdi-printer">
              Print Label
          </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
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