<script setup>
import { ref, watch, computed } from 'vue';
import { useDisplay } from 'vuetify';

const { mobile } = useDisplay();

const props = defineProps({
  show: Boolean,
  item: Object
});

const emit = defineEmits(['close', 'save', 'update:show']);

const activeTab = ref('details');

const formData = ref({
  name: '',
  contact_name: '',
  email: '',
  phone: '',
  address: '',
  notes: ''
});

const isEdit = computed(() => !!props.item);

watch(() => props.item, (newItem) => {
  if (newItem) {
    formData.value = { ...newItem };
    activeTab.value = 'details';
  } else {
    formData.value = {
      name: '',
      contact_name: '',
      email: '',
      phone: '',
      address: '',
      notes: ''
    };
    activeTab.value = 'details';
  }
}, { immediate: true });

const save = () => {
  if (!formData.value.name) return alert('Name is required');
  emit('save', formData.value);
};

const formatDate = (d) => new Date(d).toLocaleDateString();
</script>

<template>
  <v-dialog 
    :modelValue="show" 
    @update:modelValue="$emit('update:show', $event)" 
    persistent 
    :fullscreen="mobile"
    :max-width="mobile ? undefined : '600'"
    :transition="mobile ? 'dialog-bottom-transition' : 'dialog-transition'"
  >
    <v-card :rounded="mobile ? '0' : 'lg'">
      <v-toolbar color="primary" v-if="mobile">
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>{{ isEdit ? 'Edit Customer' : 'Add Customer' }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="save">Save</v-btn>
      </v-toolbar>

      <v-card-title class="d-flex justify-space-between align-center px-6 pt-6 pb-2" v-else>
        <span class="text-h5">{{ isEdit ? 'Edit Customer' : 'Add New Customer' }}</span>
        <v-btn icon variant="text" @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-tabs v-model="activeTab" v-if="isEdit" color="primary" grow>
        <v-tab value="details">Details</v-tab>
        <v-tab value="history">History</v-tab>
      </v-tabs>
      
      <v-card-text :class="mobile ? 'pa-4' : 'pa-6'">
        <v-window v-model="activeTab">
          <v-window-item value="details">
            <v-row dense class="mt-2">
              <v-col cols="12">
                <v-text-field
                  v-model="formData.name"
                  label="Name *"
                  variant="outlined"
                  density="compact"
                  required
                />
              </v-col>
              
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="formData.contact_name"
                  label="Contact Person"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="formData.email"
                  label="Email"
                  type="email"
                  variant="outlined"
                  density="compact"
                />
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="formData.phone"
                  label="Phone"
                  variant="outlined"
                  density="compact"
                  prepend-inner-icon="mdi-phone"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.address"
                  label="Address"
                  rows="2"
                  variant="outlined"
                  density="compact"
                  auto-grow
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.notes"
                  label="Notes"
                  rows="3"
                  variant="outlined"
                  density="compact"
                  auto-grow
                />
              </v-col>
            </v-row>
          </v-window-item>

          <v-window-item value="history">
            <div class="mt-4" v-if="item && item.sales && item.sales.length > 0">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="sale in item.sales" :key="sale.id">
                    <td>{{ formatDate(sale.sold_at) }}</td>
                    <td class="text-truncate" style="max-width: 150px;">{{ sale.inventory_item ? sale.inventory_item.title : 'Unknown' }}</td>
                    <td>{{ sale.quantity_sold }}</td>
                    <td class="font-weight-bold">${{ (sale.quantity_sold * sale.unit_price).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
            <div v-else class="text-center py-12 text-grey border rounded-lg">
              <v-icon size="48" class="mb-2">mdi-history</v-icon>
              <div class="text-caption">No purchases recorded.</div>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
      
      <v-divider v-if="!mobile"></v-divider>
      <v-card-actions class="pa-4" v-if="!mobile">
        <v-spacer />
        <v-btn variant="text" @click="$emit('close')">Cancel</v-btn>
        <v-btn v-if="activeTab === 'details'" color="primary" variant="elevated" @click="save">
          {{ isEdit ? 'Save Changes' : 'Create Customer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
