<script setup>
import { ref, onMounted, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    id: { type: String, required: true },
    type: { type: String, default: 'item' }, // 'item' or 'location'
    title: { type: String, default: '' },
    sku: { type: String, default: '' },
    size: { type: Number, default: 200 }
});

const qrDataUrl = ref('');

const generateQR = async () => {
    try {
        // Data format: pintventory:{type}:{id}
        // This is a custom URI scheme that our internal scanner will recognize
        const data = `pintventory:${props.type}:${props.id}`;
        qrDataUrl.value = await QRCode.toDataURL(data, {
            width: props.size,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff'
            }
        });
    } catch (err) {
        console.error('Failed to generate QR code:', err);
    }
};

onMounted(generateQR);
watch(() => props.id, generateQR);
</script>

<template>
  <div class="qr-label-container d-flex flex-column align-center pa-4 bg-white rounded border">
    <div v-if="title" class="text-caption font-weight-bold mb-1 text-center truncate-2-lines" style="max-width: 150px;">
        {{ title }}
    </div>
    
    <v-img v-if="qrDataUrl" :src="qrDataUrl" :width="size" :height="size" />
    <v-progress-circular v-else indeterminate color="primary" />

    <div v-if="sku" class="text-caption font-mono mt-1 text-center">
        {{ sku }}
    </div>
    <div class="text-overline mt-1 opacity-50">PINTVENTORY</div>
  </div>
</template>

<style scoped>
.qr-label-container {
    display: inline-flex;
    color: black !important;
}
.truncate-2-lines {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.font-mono {
    font-family: monospace;
}
</style>
