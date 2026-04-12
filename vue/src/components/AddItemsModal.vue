<script setup>
import { ref, watch } from 'vue';
import { useDisplay } from 'vuetify';
import SingleItemScanner from './SingleItemScanner.vue';
import MultiItemScanner from './MultiItemScanner.vue';
import InventoryModal from './InventoryModal.vue'; // We can reuse the content or wrap it

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['update:show', 'save']);

const { mobile } = useDisplay();
const tab = ref('single');

const handleSave = (itemData, newPhotos) => {
    emit('save', itemData, newPhotos);
};

const close = () => {
    emit('update:show', false);
};

// Reset tab when opening
watch(() => props.show, (val) => {
    if (val) {
        tab.value = 'single';
    }
});
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '700'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" density="comfortable">
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Add Items</v-toolbar-title>
      </v-toolbar>

      <v-tabs
        v-model="tab"
        bg-color="surface"
        color="primary"
        grow
        align-tabs="center"
      >
        <v-tab value="single" prepend-icon="mdi-camera">Single Scan</v-tab>
        <v-tab value="multi" prepend-icon="mdi-ImageFilterCenterFocusStrongOutline">Multi Scan</v-tab>
        <v-tab value="manual" prepend-icon="mdi-form-select">Manual</v-tab>
      </v-tabs>

      <v-divider></v-divider>

      <v-window v-model="tab" touch>
        <v-window-item value="single">
          <SingleItemScanner :active="tab === 'single' && show" @save="handleSave" />
        </v-window-item>

        <v-window-item value="multi">
          <MultiItemScanner :active="tab === 'multi' && show" @close="close" />
        </v-window-item>

        <v-window-item value="manual">
            <!-- Reuse manual entry logic from InventoryModal but stripped down or simply use the existing modal logic if possible -->
             <div class="pa-4 text-center py-12">
                 <v-icon size="80" color="primary" class="mb-6" opacity="0.3">mdi-form-select</v-icon>
                 <div class="text-h5 font-weight-bold mb-2">Manual Entry</div>
                 <div class="text-body-1 text-grey mb-8">Switch to the standard form to enter details manually.</div>
                 
                 <div class="max-width-300 mx-auto">
                    <v-btn color="primary" variant="outlined" size="large" block @click="$emit('manual-entry')" prepend-icon="mdi-pencil-box-outline">
                        Open Manual Form
                    </v-btn>
                 </div>
             </div>
        </v-window-item>
      </v-window>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.max-width-300 {
    max-width: 300px;
}
</style>
