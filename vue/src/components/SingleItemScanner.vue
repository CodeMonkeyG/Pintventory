<script setup>
import { ref, watch, onUnmounted, onMounted } from 'vue';
import api from '../axios';
import { useDisplay } from 'vuetify';

const props = defineProps({
  active: Boolean
});

const emit = defineEmits(['save']);

const { mobile } = useDisplay();
const isAnalyzing = ref(false);
const isSaving = ref(false);
const cameraInput = ref(null);
const capturedPhoto = ref(null);

const analysisResult = ref({
    title: '',
    description: '',
    item_type: 'standard',
    quantity_on_hand: 1,
    unit: 'pcs',
    tags: '',
    evaluation: '',
    market_analysis: null
});

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

const triggerCamera = () => {
    if (cameraInput.value) {
        cameraInput.value.click();
    }
};

const handlePhotoCapture = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isAnalyzing.value = true;
    try {
        capturedPhoto.value = {
            file: file,
            url: URL.createObjectURL(file)
        };

        const formDataPayload = new FormData();
        formDataPayload.append('image', file);

        const response = await api.post('/ai/image-identify', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;
        analysisResult.value = {
            title: data.title || '',
            description: data.description || '',
            item_type: data.item_type || 'standard',
            quantity_on_hand: 1,
            unit: 'pcs',
            tags: Array.isArray(data.tags) ? data.tags.join(', ') : (data.tags || ''),
            evaluation: typeof data.evaluation === 'object' ? JSON.stringify(data.evaluation, null, 2) : (data.evaluation || ''),
            market_analysis: data.market_analysis || null
        };
    } catch (error) {
        console.error('Analysis failed', error);
        alert('AI Analysis failed: ' + (error.response?.data?.message || error.message));
    } finally {
        isAnalyzing.value = false;
        event.target.value = null;
    }
};

const reset = () => {
    if (capturedPhoto.value?.url) {
        URL.revokeObjectURL(capturedPhoto.value.url);
    }
    capturedPhoto.value = null;
    analysisResult.value = {
        title: '',
        description: '',
        item_type: 'standard',
        quantity_on_hand: 1,
        unit: 'pcs',
        tags: '',
        evaluation: '',
        market_analysis: null
    };
    isAnalyzing.value = false;
    isSaving.value = false;
};

const nextItem = () => {
    reset();
    triggerCamera();
};

const saveToInventory = async () => {
    if (!analysisResult.value.title) {
        alert('Title is required to save.');
        return;
    }

    isSaving.value = true;
    try {
        const payload = {
            ...analysisResult.value,
            status: 'in_stock',
            tags: analysisResult.value.tags ? analysisResult.value.tags.split(',').map(t => t.trim()).filter(t => t) : []
        };
        
        await emit('save', payload, [capturedPhoto.value.file]);
        reset();
    } catch (error) {
        alert('Failed to save: ' + error.message);
    } finally {
        isSaving.value = false;
    }
};

defineExpose({
    isAnalyzing,
    isSaving,
    capturedPhoto,
    reset,
    saveToInventory
});

onUnmounted(() => {
    reset();
});
</script>

<template>
  <div class="pa-4">
    <input type="file" ref="cameraInput" @change="handlePhotoCapture" accept="image/*" hidden />

    <div v-if="!capturedPhoto && !isAnalyzing" class="text-center py-12">
        <v-icon size="80" color="primary" class="mb-6" opacity="0.3">mdi-camera-plus</v-icon>
        <div class="text-h5 font-weight-bold mb-2">Single Item Scan</div>
        <div class="text-body-1 text-grey mb-8">Snap a photo to identify an item instantly.</div>
        
        <div class="d-flex flex-column gap-3 max-width-300 mx-auto">
            <v-btn color="primary" size="x-large" @click="triggerCamera" prepend-icon="mdi-camera" elevation="4">
                Take Photo
            </v-btn>
            <v-btn variant="tonal" color="primary" size="large" @click="triggerCamera" prepend-icon="mdi-image-multiple">
                Open Gallery
            </v-btn>
        </div>
    </div>

    <div v-if="isAnalyzing" class="text-center py-12">
        <v-progress-circular indeterminate size="64" color="primary" class="mb-4" />
        <div class="text-h6">Identifying item...</div>
        <div class="text-body-2 text-grey">Our AI is analyzing your photo.</div>
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
                title="Click to view full image"
            >
              <div class="fill-height d-flex align-end justify-end pa-1">
                <v-icon size="16" color="white" class="bg-black-opacity-50 rounded-circle">mdi-magnify-plus</v-icon>
              </div>
            </v-img>
            <div class="text-right">
                <v-btn variant="tonal" size="small" color="primary" @click="triggerCamera" prepend-icon="mdi-camera-retake" class="mb-2">
                    Retake
                </v-btn>
                <div class="text-h6 font-weight-bold">Item Identified</div>
                <div class="text-caption text-grey">Ready to import</div>
            </div>
        </div>
        
        <v-text-field
            v-model="analysisResult.title"
            label="Item Name"
            variant="outlined"
            density="compact"
            hide-details
            class="mb-4 font-weight-bold"
        />

        <div v-if="analysisResult.market_analysis" class="mb-6">
            <div class="text-subtitle-2 font-weight-bold mb-3 text-grey-darken-1 d-flex align-center">
                <v-icon size="18" class="mr-2">mdi-chart-line</v-icon>
                ESTIMATED MARKET VALUE
            </div>
            <v-row dense>
                <v-col v-for="(data, platform) in analysisResult.market_analysis" :key="platform" cols="6" sm="4">
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
                                density="compact"
                            ></v-btn>
                        </div>
                        <div class="text-body-1 font-weight-black">{{ data.range }}</div>
                    </v-card>
                </v-col>
            </v-row>
        </div>

        <v-textarea
            v-model="analysisResult.description"
            label="Detailed Description"
            variant="outlined"
            density="compact"
            rows="3"
            hide-details
            class="mb-3"
            auto-grow
        />

        <v-row dense>
            <v-col cols="6">
                <v-select
                    v-model="analysisResult.item_type"
                    label="Type"
                    :items="[
                        { title: 'Standard', value: 'standard' },
                        { title: 'Unique', value: 'unique' }
                    ]"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
            </v-col>
            <v-col cols="6">
                <v-text-field
                    v-model.number="analysisResult.quantity_on_hand"
                    label="Qty"
                    type="number"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
            </v-col>
        </v-row>

        <v-text-field
            v-model="analysisResult.tags"
            label="Tags"
            variant="outlined"
            density="compact"
            hide-details
            class="mt-3 mb-3"
        />

        <v-alert
            v-if="analysisResult.evaluation"
            variant="tonal"
            color="info"
            icon="mdi-information-outline"
            class="mb-4"
        >
            <div class="text-caption font-weight-bold uppercase mb-1">AI Evaluation & Details</div>
            <div class="text-body-2" style="white-space: pre-wrap;">{{ analysisResult.evaluation }}</div>
        </v-alert>
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
.uppercase {
    text-transform: uppercase;
}
.cursor-pointer {
  cursor: pointer;
}
.bg-black-opacity-50 {
    background: rgba(0, 0, 0, 0.5) !important;
}
</style>
