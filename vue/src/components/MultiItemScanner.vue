<script setup>
import { ref, watch, onUnmounted, inject } from 'vue';
import api from '../axios';
import { useDisplay } from 'vuetify';
import { useInventoryStore } from '../stores/inventory';
import { queueOfflineItem } from '../utils/offlineStore';

const props = defineProps({
  active: Boolean
});

const emit = defineEmits(['close']);

const isOnline = inject('isOnline');
const { mobile } = useDisplay();
const inventoryStore = useInventoryStore();

const isAnalyzing = ref(false);
const isSaving = ref(false);
const cameraInput = ref(null);
const galleryInput = ref(null);
const capturedPhoto = ref(null);
const foundItems = ref([]);
const selectedIndices = ref(new Set());

const getMarketUrl = (platform, query) => {
    if (!query) return '#';
    const encodedQuery = encodeURIComponent(query);
    switch (platform) {
        case 'ebay': return `https://www.ebay.com/sch/i.html?_nkw=${encodedQuery}`;
        case 'facebook': return `https://www.facebook.com/marketplace/search/?query=${encodedQuery}`;
        case 'etsy': return `https://www.etsy.com/search?q=${encodedQuery}`;
        default: return '#';
    }
};

const triggerCamera = () => {
    if (cameraInput.value) {
        cameraInput.value.click();
    }
};

const triggerGallery = () => {
    if (galleryInput.value) {
        galleryInput.value.click();
    }
};

const handlePhotoCapture = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isAnalyzing.value = true;
    foundItems.value = [];
    selectedIndices.value = new Set();
    
    try {
        capturedPhoto.value = {
            file: file,
            url: URL.createObjectURL(file)
        };

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/shotgun-scan', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' },
            timeout: 60000 // 60 seconds
        });

        foundItems.value = response.data.map(item => ({
            ...item,
            quantity_on_hand: 1,
            unit: 'pcs',
            status: 'in_stock'
        }));
        
        foundItems.value.forEach((_, index) => selectedIndices.value.add(index));
        
    } catch (error) {
        console.error('Shotgun scan failed', error);
        alert('Shotgun Scan failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isAnalyzing.value = false;
        event.target.value = null;
    }
};

const toggleSelection = (index) => {
    if (selectedIndices.value.has(index)) {
        selectedIndices.value.delete(index);
    } else {
        selectedIndices.value.add(index);
    }
};

const reset = () => {
    if (capturedPhoto.value?.url) {
        URL.revokeObjectURL(capturedPhoto.value.url);
    }
    capturedPhoto.value = null;
    foundItems.value = [];
    selectedIndices.value = new Set();
    isAnalyzing.value = false;
    isSaving.value = false;
};

const saveSelected = async () => {
    if (selectedIndices.value.size === 0) {
        alert('Please select at least one item to save.');
        return;
    }

    isSaving.value = true;
    let savedCount = 0;
    
    try {
        const indices = Array.from(selectedIndices.value);
        for (const index of indices) {
            const item = foundItems.value[index];
            const payload = {
                title: item.title,
                description: item.description,
                item_type: item.item_type,
                quantity_on_hand: item.quantity_on_hand,
                unit: item.unit,
                status: item.status,
                evaluation: item.evaluation,
                tags: Array.isArray(item.tags) ? item.tags : (item.tags ? item.tags.split(',').map(t => t.trim()) : [])
            };

            if (!isOnline.value) {
                await queueOfflineItem(payload, [capturedPhoto.value.file]);
            } else {
                const newItem = await inventoryStore.createItem(payload);
                await inventoryStore.uploadPhoto(newItem.id, capturedPhoto.value.file);
            }
            savedCount++;
        }
        
        if (!isOnline.value) {
            alert(`Queued ${savedCount} items for upload once back online.`);
        } else {
            alert(`Successfully saved ${savedCount} items to inventory.`);
        }
        emit('close');
        reset();
    } catch (error) {
        alert('Failed to save some items: ' + error.message);
    } finally {
        isSaving.value = false;
    }
};

defineExpose({
    isAnalyzing,
    isSaving,
    capturedPhoto,
    reset,
    saveSelected
});

onUnmounted(() => {
    reset();
});
</script>

<template>
  <div class="pa-4">
    <input type="file" ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" hidden />
    <input type="file" ref="galleryInput" @change="handlePhotoCapture" accept="image/*" hidden />

    <div v-if="!capturedPhoto && !isAnalyzing" class="text-center py-12">
        <v-icon size="80" color="primary" class="mb-6" opacity="0.3">mdi-ImageFilterCenterFocusStrongOutline</v-icon>
        <div class="text-h5 font-weight-bold mb-2">Multi-Item Scan</div>
        <div class="text-body-1 text-grey mb-8">Point at a group of items to find the gems.</div>
        
        <div class="d-flex flex-column gap-3 max-width-300 mx-auto">
            <v-btn color="primary" size="x-large" @click="triggerCamera" prepend-icon="mdi-camera" elevation="4">
                Take Photo
            </v-btn>
            <v-btn variant="tonal" color="primary" size="large" @click="triggerGallery" prepend-icon="mdi-image-multiple">
                Open Gallery
            </v-btn>
        </div>
    </div>

    <div v-if="isAnalyzing" class="text-center py-12">
        <v-progress-circular indeterminate size="80" width="8" color="primary" class="mb-6" />
        <div class="text-h5 font-weight-bold">Scanning for Loot...</div>
        <div class="text-body-1 text-grey">Our AI is picking out the best items from your photo.</div>
    </div>

    <div v-if="capturedPhoto && !isAnalyzing">
        <div class="d-flex align-center justify-space-between mb-4">
            <v-img 
                :src="capturedPhoto.url" 
                height="120" 
                max-width="120" 
                cover 
                rounded="lg" 
                class="border elevation-2 cursor-pointer" 
                @click="$emit('open-gallery')"
            >
              <div class="fill-height d-flex align-end justify-end pa-1">
                <v-icon size="16" color="white" class="bg-black-opacity-50 rounded-circle">mdi-magnify-plus</v-icon>
              </div>
            </v-img>
            <div class="text-right">
                <v-btn variant="tonal" size="small" color="primary" @click="triggerCamera" prepend-icon="mdi-camera-retake" class="mb-2">
                    Retake
                </v-btn>
                <div class="text-h6 font-weight-bold">Found {{ foundItems.length }} Items</div>
                <div class="text-caption text-grey">{{ selectedIndices.size }} selected</div>
            </div>
        </div>

        <v-row dense>
            <v-col v-for="(item, index) in foundItems" :key="index" cols="12">
                <v-card 
                    variant="outlined" 
                    :color="selectedIndices.has(index) ? 'primary' : 'grey-lighten-1'"
                    class="item-card overflow-hidden"
                    :class="{ 'selected-item': selectedIndices.has(index) }"
                    @click="toggleSelection(index)"
                >
                    <div class="d-flex pa-3">
                        <v-checkbox-btn
                            :model-value="selectedIndices.has(index)"
                            color="primary"
                            class="mr-2 mt-n1"
                            @click.stop="toggleSelection(index)"
                        ></v-checkbox-btn>
                        
                        <div class="flex-grow-1">
                            <v-text-field
                                v-model="item.title"
                                variant="underlined"
                                density="compact"
                                hide-details
                                class="mb-2 font-weight-bold"
                                placeholder="Item Title"
                                @click.stop
                            />
                            
                            <div class="d-flex gap-2 mb-3">
                                <v-chip size="x-small" color="success" variant="flat" class="font-weight-bold">
                                    {{ item.estimated_value }}
                                </v-chip>
                                <v-chip size="x-small" color="secondary" variant="tonal" class="text-uppercase">
                                    {{ item.item_type }}
                                </v-chip>
                            </div>

                            <div v-if="item.market_analysis" class="mb-1">
                                <v-row dense>
                                    <v-col 
                                        v-for="(data, platform) in item.market_analysis" 
                                        :key="platform"
                                        cols="4"
                                    >
					    <v-card variant="outlined" class="pa-2 fill-height bg-surface" style="border-color: rgba(var(--v-border-color), 0.15) !important;">
                                            <div class="d-flex justify-space-between align-center mb-0">
						<span class="text-caption font-weight-bold text-grey text-uppercase">{{ platform }}</span>
                                                <v-btn
                                                    icon="mdi-open-in-new"
                                                    size="x-small"
                                                    variant="text"
                                                    color="primary"
                                                    :href="getMarketUrl(platform, data.query)"
                                                    target="_blank"
                                                    density="compact"
                                                    class="mt-n1 mr-n1"
                                                ></v-btn>
                                            </div>
                                            <div class="font-weight-black text-truncate" style="font-size: 10px;">{{ data.range }}</div>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </div>
  </div>
</template>

<style scoped>
.gap-2 {
    gap: 8px;
}
.gap-3 {
    gap: 12px;
}
.max-width-300 {
    max-width: 300px;
}
.item-card {
    transition: all 0.2s ease;
    cursor: pointer;
}
.selected-item {
    background-color: rgba(var(--v-theme-primary), 0.05);
    border-width: 2px;
}
.item-card:hover {
    border-color: rgb(var(--v-theme-primary));
}
.cursor-pointer {
    cursor: pointer;
}
.bg-black-opacity-50 {
    background: rgba(0, 0, 0, 0.5) !important;
}
</style>
