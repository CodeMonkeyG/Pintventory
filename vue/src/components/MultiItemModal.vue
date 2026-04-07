<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import api from '../axios';
import { useDisplay } from 'vuetify';
import { useInventoryStore } from '../stores/inventory';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['close', 'update:show']);

const { mobile } = useDisplay();
const inventoryStore = useInventoryStore();

const isAnalyzing = ref(false);
const isSaving = ref(false);
const cameraInput = ref(null);
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
        
        // Auto-select all by default
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

            const newItem = await inventoryStore.createItem(payload);
            await inventoryStore.uploadPhoto(newItem.id, capturedPhoto.value.file);
            savedCount++;
        }
        
        alert(`Successfully saved ${savedCount} items to inventory.`);
        emit('update:show', false);
        reset();
    } catch (error) {
        alert('Failed to save some items: ' + error.message);
    } finally {
        isSaving.value = false;
    }
};

watch(() => props.show, (val) => {
    if (val) {
        reset();
        setTimeout(triggerCamera, 300);
    } else {
        reset();
    }
});

onUnmounted(() => {
    reset();
});
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '800'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" density="comfortable" dark>
        <v-btn icon @click="$emit('update:show', false)">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Multi Item</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn v-if="foundItems.length > 0" variant="text" @click="triggerCamera">
            Retake
        </v-btn>
      </v-toolbar>

      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
        <input type="file" ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" hidden />

        <div v-if="!capturedPhoto && !isAnalyzing" class="text-center py-12">
            <v-icon size="80" color="secondary" class="mb-4" opacity="0.2">mdi-ImageFilterCenterFocusStrongOutline</v-icon>
            <div class="text-h5 font-weight-bold mb-2">Multi-Item Scan</div>
            <div class="text-body-1 text-grey-darken-1 mb-8">Point at a group of items to find the gems.</div>
            <v-btn color="primary" size="x-large" @click="triggerCamera" prepend-icon="mdi-camera" elevation="4">
                Scan Group
            </v-btn>
        </div>

        <div v-if="isAnalyzing" class="text-center py-12">
            <v-progress-circular indeterminate size="80" width="8" color="primary" class="mb-6" />
            <div class="text-h5 font-weight-bold">Scanning for Loot...</div>
            <div class="text-body-1 text-grey">Our AI is picking out the best items from your photo.</div>
        </div>

        <div v-if="capturedPhoto && !isAnalyzing">
            <v-img :src="capturedPhoto.url" height="240" cover rounded="lg" class="mb-6 border elevation-2" />
            
            <div class="d-flex align-center justify-space-between mb-4">
                <div class="text-h6 font-weight-bold">
                    Found {{ foundItems.length }} Potential Items
                </div>
                <div class="text-caption text-grey">
                    {{ selectedIndices.size }} selected for import
                </div>
            </div>

            <v-row>
                <v-col v-for="(item, index) in foundItems" :key="index" cols="12">
                    <v-card 
                        variant="outlined" 
                        :color="selectedIndices.has(index) ? 'primary' : 'grey-lighten-1'"
                        class="item-card overflow-hidden"
                        :class="{ 'selected-item': selectedIndices.has(index) }"
                        @click="toggleSelection(index)"
                    >
                        <div class="d-flex pa-4">
                            <v-checkbox-btn
                                :model-value="selectedIndices.has(index)"
                                color="primary"
                                class="mr-2 mt-n1"
                                @click.stop="toggleSelection(index)"
                            ></v-checkbox-btn>
                            
                            <div class="flex-grow-1">
                                <v-text-field
                                    v-model="item.title"
                                    label="Item Title"
                                    variant="underlined"
                                    density="compact"
                                    hide-details
                                    class="mb-2 font-weight-bold"
                                    @click.stop
                                />
                                
                                <div class="d-flex gap-2 mb-2">
                                    <v-chip size="x-small" color="success" variant="tonal" class="font-weight-bold">
                                        {{ item.estimated_value }}
                                    </v-chip>
                                    <v-chip size="x-small" color="secondary" variant="tonal">
                                        {{ item.item_type }}
                                    </v-chip>
                                </div>

                                <div v-if="item.market_analysis" class="mb-3">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <v-card 
                                            v-for="(data, platform) in item.market_analysis" 
                                            :key="platform"
                                            variant="flat" 
                                            class="pa-1 bg-grey-lighten-4 rounded"
                                            style="font-size: 10px; min-width: 80px;"
                                            @click.stop
                                        >
                                            <div class="d-flex justify-space-between align-center">
                                                <span class="font-weight-bold text-uppercase" style="font-size: 8px;">{{ platform }}</span>
                                                <a :href="getMarketUrl(platform, data.query)" target="_blank" class="text-primary">
                                                    <v-icon size="10">mdi-open-in-new</v-icon>
                                                </a>
                                            </div>
                                            <div class="font-weight-black">{{ data.range }}</div>
                                        </v-card>
                                    </div>
                                </div>

                                <v-textarea
                                    v-model="item.description"
                                    label="Description"
                                    variant="underlined"
                                    density="compact"
                                    rows="2"
                                    hide-details
                                    auto-grow
                                    class="text-body-2"
                                    @click.stop
                                />

                                <div class="d-flex flex-wrap gap-1 mt-3">
                                    <v-chip v-for="tag in item.tags" :key="tag" size="x-small" variant="outlined">
                                        {{ tag }}
                                    </v-chip>
                                </div>
                            </div>
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </div>
      </v-card-text>

      <v-divider v-if="foundItems.length > 0 && !isAnalyzing"></v-divider>
      
      <v-card-actions v-if="foundItems.length > 0 && !isAnalyzing" class="pa-4">
        <v-btn 
            variant="text" 
            color="grey" 
            @click="$emit('update:show', false)"
        >
            Cancel
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn 
            color="primary" 
            variant="elevated" 
            size="large" 
            @click="saveSelected"
            :loading="isSaving"
            :disabled="selectedIndices.size === 0"
            prepend-icon="mdi-plus-box-multiple"
        >
            Import {{ selectedIndices.size }} Items
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.gap-2 {
    gap: 8px;
}
.gap-1 {
    gap: 4px;
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
</style>
