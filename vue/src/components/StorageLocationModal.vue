<script setup>
import { ref, watch, computed } from 'vue';
import { useDisplay } from 'vuetify';

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
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" v-if="mobile">
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>{{ isEdit ? 'Edit Location' : 'Add Location' }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="save">Save</v-btn>
      </v-toolbar>

      <v-card-title class="d-flex justify-space-between align-center px-6 pt-6 pb-2" v-else>
        <span class="text-h5">{{ isEdit ? 'Edit Storage Location' : 'Add New Storage Location' }}</span>
        <v-btn icon variant="text" @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      
      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
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
      
      <v-divider v-if="!mobile"></v-divider>
      <v-card-actions class="pa-4" v-if="!mobile">
        <v-spacer />
        <v-btn variant="text" @click="$emit('close')">Cancel</v-btn>
        <v-btn color="primary" variant="elevated" @click="save">
          {{ isEdit ? 'Save Changes' : 'Create Location' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
