<script setup>
import { ref, watch, onUnmounted } from 'vue';
import api from '../axios';
import { useDisplay } from 'vuetify';
import { resizeImage } from '../utils/helpers';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['close', 'save', 'update:show']);

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
    evaluation: ''
});

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
        // const resizedFile = await resizeImage(file);
        const resizedFile = file;
        capturedPhoto.value = {
            file: resizedFile,
            url: URL.createObjectURL(resizedFile)
        };

        const formDataPayload = new FormData();
        formDataPayload.append('image', resizedFile);

        const response = await api.post('/ai/image-identify', formDataPayload, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;
        analysisResult.value = {
            title: data.title || '',
            description: data.description || '',
            item_type: data.item_type || 'standard',
            quantity_on_hand: data.item_type === 'unique' ? 1 : 1,
            unit: 'pcs',
            tags: Array.isArray(data.tags) ? data.tags.join(', ') : (data.tags || ''),
            evaluation: typeof data.evaluation === 'object' ? JSON.stringify(data.evaluation, null, 2) : (data.evaluation || '')
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
        evaluation: ''
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
        
        emit('save', payload, [capturedPhoto.value.file]);
        reset();
    } catch (error) {
        alert('Failed to save: ' + error.message);
    } finally {
        isSaving.value = false;
    }
};

watch(() => props.show, (val) => {
    if (val) {
        reset();
        // Delay trigger slightly to allow modal animation
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
    :max-width="mobile ? undefined : '600'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" density="comfortable">
        <v-btn icon @click="$emit('update:show', false)">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Hunting Mode</v-toolbar-title>
      </v-toolbar>

      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
        <input type="file" ref="cameraInput" @change="handlePhotoCapture" accept="image/*" capture="environment" hidden />

        <div v-if="!capturedPhoto && !isAnalyzing" class="text-center py-12">
            <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-camera-plus</v-icon>
            <div class="text-h6 text-grey">Ready to hunt?</div>
            <div class="text-body-2 text-grey mb-6">Snap a photo to identify an item instantly.</div>
            <v-btn color="primary" size="large" @click="triggerCamera" prepend-icon="mdi-camera">
                Snap Picture
            </v-btn>
        </div>

        <div v-if="isAnalyzing" class="text-center py-12">
            <v-progress-circular indeterminate size="64" color="primary" class="mb-4" />
            <div class="text-h6">Identifying item...</div>
            <div class="text-body-2 text-grey">Our AI is analyzing your photo.</div>
        </div>

        <div v-if="capturedPhoto && !isAnalyzing">
            <v-img :src="capturedPhoto.url" height="200" cover rounded="lg" class="mb-4 border" />
            
            <v-text-field
                v-model="analysisResult.title"
                label="Suggested Title"
                variant="outlined"
                density="compact"
                hide-details
                class="mb-3"
            />

            <v-textarea
                v-model="analysisResult.description"
                label="Description"
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
                color="success"
                icon="mdi-information-outline"
                class="mb-4"
            >
                <div class="text-caption font-weight-bold uppercase mb-1">AI Evaluation</div>
                <div class="text-body-2" style="white-space: pre-wrap;">{{ analysisResult.evaluation }}</div>
            </v-alert>
        </div>
      </v-card-text>

      <v-divider v-if="capturedPhoto && !isAnalyzing"></v-divider>
      
      <v-card-actions v-if="capturedPhoto && !isAnalyzing" class="pa-4 flex-column gap-2">
        <v-btn 
            color="primary" 
            variant="elevated" 
            block 
            size="large" 
            @click="saveToInventory"
            :loading="isSaving"
            prepend-icon="mdi-check"
        >
            Add to Inventory
        </v-btn>
        <v-btn 
            color="secondary" 
            variant="tonal" 
            block 
            @click="nextItem"
            prepend-icon="mdi-camera-retake"
        >
            Next Item (Discard & Snap)
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.gap-2 {
    gap: 8px;
}
.uppercase {
    text-transform: uppercase;
}
</style>