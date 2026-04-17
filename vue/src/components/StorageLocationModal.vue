<script setup>
import { ref, watch, computed } from 'vue';
import { useDisplay } from 'vuetify';
import QrLabel from './QrLabel.vue';

const { mobile } = useDisplay();

const props = defineProps({
  show: Boolean,
  item: Object
});

const emit = defineEmits(['close', 'save', 'update:show']);

const formData = ref({
  name: '',
  description: ''
});

const showQrModal = ref(false);

const isEdit = computed(() => !!props.item);

watch(() => props.item, (newItem) => {
  if (newItem) {
    formData.value = { 
        name: newItem.name || '',
        description: newItem.description || ''
    };
  } else {
    formData.value = {
      name: '',
      description: ''
    };
  }
}, { immediate: true });

watch(() => props.show, (val) => {
  if (!val) {
    formData.value = {
      name: '',
      description: ''
    };
  }
});

const save = () => {
  if (!formData.value.name) return alert('Name is required');
  emit('save', formData.value);
};

const printLabel = () => {
    const printable = document.getElementById('qr-printable-loc');
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Location Label</title>');
    printWindow.document.write('<style>body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; } @page { margin: 0; size: auto; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printable.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
};
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '500'"
    :transition="mobile ? 'dialog-bottom-transition' : 'dialog-transition'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'" class="d-flex flex-column" :style="mobile ? 'height: 100dvh;' : 'max-height: 90vh;'">
      <v-toolbar color="primary" :density="mobile ? 'comfortable' : 'default'">
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>{{ isEdit ? 'Edit Location' : 'Add Location' }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn 
          v-if="isEdit" 
          variant="text" 
          :icon="mobile" 
          :prepend-icon="!mobile ? 'mdi-qrcode' : undefined" 
          @click="showQrModal = true"
          class="mr-2"
        >
          {{ mobile ? '' : 'Print Label' }}
          <v-icon v-if="mobile">mdi-qrcode</v-icon>
        </v-btn>
        <v-btn variant="text" @click="save" prepend-icon="mdi-check" class="px-4">Save</v-btn>
      </v-toolbar>
      
      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'" class="flex-grow-1 overflow-y-auto">
        <v-row dense class="mt-2">
          <v-col cols="12">
            <v-text-field
              v-model="formData.name"
              label="Name *"
              placeholder="Shelf A, Warehouse B, etc."
              variant="outlined"
              density="compact"
              required
            />
          </v-col>
          
          <v-col cols="12">
            <v-textarea
              v-model="formData.description"
              label="Description"
              rows="3"
              variant="outlined"
              density="compact"
              auto-grow
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>

  <!-- QR Code Print Dialog -->
  <v-dialog v-model="showQrModal" max-width="350">
    <v-card>
      <v-card-title class="d-flex align-center">
          Location Label
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" size="small" @click="showQrModal = false"></v-btn>
      </v-card-title>
      <v-card-text class="d-flex flex-column align-center">
          <div id="qr-printable-loc" class="bg-white pa-4 rounded border">
              <QrLabel 
                  v-if="item" 
                  :id="item.id" 
                  type="location" 
                  :title="formData.name" 
                  :size="200"
              />
          </div>
          <div class="text-caption text-grey mt-4 text-center">
              Scan this label to see all items currently stored in this location.
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
.gap-2 {
    gap: 8px;
}
</style>
