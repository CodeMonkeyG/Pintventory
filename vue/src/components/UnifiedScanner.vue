<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { useRouter } from 'vue-router';
import { useInventoryStore } from '../stores/inventory';

const props = defineProps({
    active: Boolean
});

const emit = defineEmits(['close', 'found-item', 'found-location']);

const router = useRouter();
const inventoryStore = useInventoryStore();

const scannerId = 'unified-reader';
const html5QrCode = ref(null);
const isScanning = ref(false);
const scanResult = ref(null);
const error = ref(null);
const cameraReady = ref(false);

const startScanner = async () => {
    if (html5QrCode.value && isScanning.value) return;
    
    error.value = null;
    try {
        html5QrCode.value = new Html5Qrcode(scannerId);
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        await html5QrCode.value.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        );
        isScanning.value = true;
        cameraReady.value = true;
    } catch (err) {
        console.error('Failed to start scanner:', err);
        error.value = "Camera access denied or not available.";
    }
};

const stopScanner = async () => {
    if (html5QrCode.value && isScanning.value) {
        try {
            await html5QrCode.value.stop();
            isScanning.value = false;
        } catch (err) {
            console.error('Failed to stop scanner:', err);
        }
    }
};

const onScanSuccess = async (decodedText, decodedResult) => {
    // Pintventory QR: pintventory:{type}:{id}
    if (decodedText.startsWith('pintventory:')) {
        const parts = decodedText.split(':');
        const type = parts[1];
        const id = parts[2];
        
        await stopScanner();
        
        if (type === 'item') {
            emit('found-item', id);
        } else if (type === 'location') {
            emit('found-location', id);
        }
    } else {
        // Assume it's a standard barcode (UPC/EAN)
        // Search inventory by SKU
        await stopScanner();
        
        // Use the inventory store's search logic
        inventoryStore.setFilter('search', decodedText);
        emit('close');
        router.push('/inventory');
    }
};

const onScanFailure = (err) => {
    // Too noisy to log every failure
};

watch(() => props.active, (val) => {
    if (val) {
        setTimeout(startScanner, 300); // Small delay for tab animation
    } else {
        stopScanner();
    }
});

onMounted(() => {
    if (props.active) startScanner();
});

onUnmounted(stopScanner);
</script>

<template>
  <div class="pa-4 d-flex flex-column align-center">
    <div v-if="error" class="text-center py-12">
        <v-icon size="64" color="error" class="mb-4">mdi-camera-off</v-icon>
        <div class="text-h6 text-error">{{ error }}</div>
        <v-btn color="primary" class="mt-4" @click="startScanner">Retry</v-btn>
    </div>

    <div v-show="!error" class="scanner-container">
        <div :id="scannerId" class="reader"></div>
        
        <div v-if="!cameraReady" class="text-center py-12">
            <v-progress-circular indeterminate color="primary" size="64" />
            <div class="text-h6 mt-4">Initializing camera...</div>
        </div>

        <div v-if="cameraReady" class="text-center mt-6">
            <v-icon size="48" color="primary" class="mb-2" opacity="0.3">mdi-qrcode-scan</v-icon>
            <div class="text-h6 font-weight-bold">Scan QR or Barcode</div>
            <div class="text-body-2 text-grey">Pintventory labels will open automatically. Barcodes will search by SKU.</div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.scanner-container {
    width: 100%;
    max-width: 500px;
}
.reader {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: black;
}
:deep(#unified-reader__dashboard) {
    display: none !important;
}
:deep(#unified-reader video) {
    border-radius: 12px;
}
</style>
