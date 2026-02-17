<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  show: Boolean,
  item: Object // If null, create mode
});

const emit = defineEmits(['close', 'save']);

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
  } else {
    formData.value = {
      name: '',
      contact_name: '',
      email: '',
      phone: '',
      address: '',
      notes: ''
    };
  }
}, { immediate: true });

const save = () => {
  if (!formData.value.name) return alert('Name is required');
  emit('save', formData.value);
};
</script>

<template>
  <div v-if="show" class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Customer' : 'Add New Customer' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>
      
      <div class="modal-body">
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
      
      <div class="modal-footer">
        <button class="btn-cancel" @click="$emit('close')">Cancel</button>
        <button class="btn-save" @click="save">{{ isEdit ? 'Save Changes' : 'Create Customer' }}</button>
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
  /* No color as requested */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  border: 1px solid black;
  background: white; /* Need background to cover content behind */
  padding: 20px;
  border-radius: 8px;
  width: 500px;
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
</style>
