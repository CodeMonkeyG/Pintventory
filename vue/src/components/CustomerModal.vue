<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  show: Boolean,
  item: Object // If null, create mode
});

const emit = defineEmits(['close', 'save']);

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
  <div v-if="show" class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Customer' : 'Add New Customer' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <div class="tabs" v-if="isEdit">
          <button :class="['tab-btn', { active: activeTab === 'details' }]" @click="activeTab = 'details'">Details</button>
          <button :class="['tab-btn', { active: activeTab === 'history' }]" @click="activeTab = 'history'">Purchase History</button>
      </div>
      
      <div class="modal-body" v-if="activeTab === 'details'">
        <div class="form-group">
          <label>Name *</label>
          <input v-model="formData.name" type="text" placeholder="Company or Person Name" />
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Contact Person</label>
                <input v-model="formData.contact_name" type="text" />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input v-model="formData.email" type="email" />
            </div>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input v-model="formData.phone" type="text" />
        </div>

        <div class="form-group">
          <label>Address</label>
          <textarea v-model="formData.address" rows="2"></textarea>
        </div>

        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="formData.notes" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-body" v-else-if="activeTab === 'history'">
          <table class="history-table">
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
                  <tr v-if="!item.sales || item.sales.length === 0">
                      <td colspan="4" class="text-center">No purchases recorded.</td>
                  </tr>
              </tbody>
          </table>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" @click="$emit('close')">Close</button>
        <button v-if="activeTab === 'details'" class="btn-save" @click="save">{{ isEdit ? 'Save Changes' : 'Create Customer' }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  border: 1px solid black;
  background: white;
  padding: 20px;
  border-radius: 8px;
  width: 600px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.modal-body {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-height: 300px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

input, select, textarea {
    padding: 8px;
    border: 1px solid black;
    border-radius: 4px;
}

.modal-footer {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel {
  border: 1px solid black;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-save {
  border: 1px solid black;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    border-bottom: 1px solid black;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
}

.tab-btn.active {
    font-weight: bold;
    text-decoration: underline;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th, .history-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
</style>
