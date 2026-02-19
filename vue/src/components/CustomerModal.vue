<script setup>
import { ref, watch, computed } from 'vue';

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
  <v-dialog :modelValue="show" @update:modelValue="$emit('update:show', $event)" persistent max-width="600">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ isEdit ? 'Edit Customer' : 'Add New Customer' }}</span>
        <v-btn icon @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-tabs v-model="activeTab" v-if="isEdit">
        <v-tab value="details">Details</v-tab>
        <v-tab value="history">Purchase History</v-tab>
      </v-tabs>
      
      <v-card-text>
        <v-window v-model="activeTab">
          <v-window-item value="details">
            <div class="mt-4">
              <v-text-field
                v-model="formData.name"
                label="Name *"
                placeholder="Company or Person Name"
                required
              />
              
              <div class="d-flex gap-3">
                <v-text-field
                  v-model="formData.contact_name"
                  label="Contact Person"
                  class="flex-grow-1"
                />
                <v-text-field
                  v-model="formData.email"
                  label="Email"
                  type="email"
                  class="flex-grow-1"
                />
              </div>

              <v-text-field
                v-model="formData.phone"
                label="Phone"
              />

              <v-textarea
                v-model="formData.address"
                label="Address"
                rows="2"
              />

              <v-textarea
                v-model="formData.notes"
                label="Notes"
                rows="3"
              />
            </div>
          </v-window-item>

          <v-window-item value="history">
            <div class="mt-4">
              <v-table v-if="item && item.sales && item.sales.length > 0">
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
                    <td>{{ sale.inventory_item ? sale.inventory_item.title : 'Unknown' }}</td>
                    <td>{{ sale.quantity_sold }}</td>
                    <td>${{ (sale.quantity_sold * sale.unit_price).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </v-table>
              <div v-else class="text-center py-6">
                No purchases recorded.
              </div>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
      
      <v-card-actions>
        <v-spacer />
        <v-btn @click="$emit('close')">Close</v-btn>
        <v-btn v-if="activeTab === 'details'" color="primary" @click="save">
          {{ isEdit ? 'Save Changes' : 'Create Customer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
