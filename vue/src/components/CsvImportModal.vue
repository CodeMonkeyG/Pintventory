<script setup>
import { ref, computed, watch } from 'vue';
import Papa from 'papaparse';
import { useInventoryStore } from '../stores/inventory';
import { useLocationStore } from '../stores/locations';

const props = defineProps({
    active: Boolean
});

const emit = defineEmits(['close', 'success']);

const inventoryStore = useInventoryStore();
const locationStore = useLocationStore();

const step = ref(1);
const file = ref(null);
const csvData = ref([]);
const csvHeaders = ref([]);
const mapping = ref({
    title: '',
    sku: '',
    description: '',
    status: '',
    item_type: '',
    quantity_on_hand: '',
    reorder_point: '',
    unit: '',
    tags: '',
    storage_location: ''
});

const availableFields = [
    { title: 'Item Title (Required)', value: 'title' },
    { title: 'SKU', value: 'sku' },
    { title: 'Description', value: 'description' },
    { title: 'Status', value: 'status' },
    { title: 'Item Type', value: 'item_type' },
    { title: 'Quantity on Hand', value: 'quantity_on_hand' },
    { title: 'Reorder Point', value: 'reorder_point' },
    { title: 'Unit (e.g. pcs, box)', value: 'unit' },
    { title: 'Tags (comma separated)', value: 'tags' },
    { title: 'Storage Location (Name)', value: 'storage_location' }
];

const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    if (!selectedFile) return;
    
    Papa.parse(selectedFile, {
        header: true,
        skipEmptyLines: true,
        complete: (results) => {
            csvData.value = results.data;
            csvHeaders.value = results.meta.fields;
            autoMapHeaders();
            step.value = 2;
        },
        error: (error) => {
            alert('Failed to parse CSV: ' + error.message);
        }
    });
};

const autoMapHeaders = () => {
    const headers = csvHeaders.value.map(h => h.toLowerCase().trim());
    Object.keys(mapping.value).forEach(field => {
        const index = headers.findIndex(h => h === field.toLowerCase() || h.includes(field.toLowerCase()));
        if (index !== -1) {
            mapping.value[field] = csvHeaders.value[index];
        }
    });
};

const processedData = computed(() => {
    if (!mapping.value.title) return [];
    
    return csvData.value.map(row => {
        const item = {};
        Object.entries(mapping.value).forEach(([field, csvHeader]) => {
            if (csvHeader) {
                let value = row[csvHeader];
                
                // Type casting
                if (['quantity_on_hand', 'reorder_point'].includes(field)) {
                    value = parseInt(value) || 0;
                } else if (field === 'tags' && value) {
                    value = value.split(',').map(t => t.trim()).filter(t => t);
                } else if (field === 'storage_location' && value) {
                    const loc = locationStore.items.find(l => l.name.toLowerCase() === value.toLowerCase());
                    if (loc) {
                        item.storage_location_id = loc.id;
                    }
                }
                
                if (field !== 'storage_location') {
                    item[field] = value;
                }
            }
        });
        return item;
    });
});

const importData = async () => {
    if (!mapping.value.title) {
        alert('You must map the Title field.');
        return;
    }

    try {
        const result = await inventoryStore.bulkStore(processedData.value);
        alert(`Successfully imported ${result.count} items.`);
        emit('success');
        reset();
    } catch (error) {
        alert('Import failed: ' + error.message);
    }
};

const reset = () => {
    step.value = 1;
    file.value = null;
    csvData.value = [];
    csvHeaders.value = [];
    mapping.value = {
        title: '', sku: '', description: '', status: '', item_type: '',
        quantity_on_hand: '', reorder_point: '', unit: '', tags: '', storage_location: ''
    };
};

watch(() => props.active, (val) => {
    if (!val) reset();
});
</script>

<template>
  <div class="pa-4">
    <div v-if="step === 1" class="text-center py-12">
      <v-icon size="80" color="primary" class="mb-6" opacity="0.3">mdi-file-import-outline</v-icon>
      <div class="text-h5 font-weight-bold mb-2">Import from CSV</div>
      <div class="text-body-1 text-grey mb-8">Upload a CSV file to bulk add items to your inventory.</div>
      
      <v-file-input
        label="Choose CSV File"
        accept=".csv"
        variant="outlined"
        prepend-icon="mdi-file-csv"
        class="max-width-400 mx-auto"
        @change="handleFileChange"
      ></v-file-input>
      
      <div class="text-caption text-grey mt-4">
        Ensure your CSV has a header row. You'll map columns in the next step.
      </div>
    </div>

    <div v-if="step === 2">
      <div class="d-flex align-center justify-space-between mb-4">
        <div class="text-h6 font-weight-bold">Map CSV Columns</div>
        <v-btn variant="text" size="small" @click="step = 1">Change File</v-btn>
      </div>

      <v-alert type="info" variant="tonal" class="mb-4 text-caption" density="compact">
        Match your CSV columns to Pintventory fields. Only mapped fields will be imported.
      </v-alert>

      <v-row dense>
        <v-col v-for="field in availableFields" :key="field.value" cols="12" sm="6">
          <v-select
            v-model="mapping[field.value === 'storage_location_id' ? 'storage_location' : field.value]"
            :label="field.title"
            :items="csvHeaders"
            variant="outlined"
            density="compact"
            clearable
            placeholder="Select column..."
          />
        </v-col>
      </v-row>

      <div class="mt-6 border rounded-lg overflow-hidden">
          <div class="bg-grey-lighten-4 pa-2 text-caption font-weight-bold border-b d-flex justify-space-between align-center">
              PREVIEW (First 3 items)
              <v-chip size="x-small" color="primary">{{ processedData.length }} total items found</v-chip>
          </div>
          <v-table density="compact">
              <thead>
                  <tr>
                      <th v-for="field in availableFields.filter(f => mapping[f.value === 'storage_location_id' ? 'storage_location' : f.value])" :key="field.value">
                          {{ field.title.split(' ')[0] }}
                      </th>
                  </tr>
              </thead>
              <tbody>
                  <tr v-for="(item, i) in processedData.slice(0, 3)" :key="i">
                      <td v-for="field in availableFields.filter(f => mapping[f.value === 'storage_location_id' ? 'storage_location' : f.value])" :key="field.value">
                          <span class="text-caption truncate-cell">{{ item[field.value] }}</span>
                      </td>
                  </tr>
              </tbody>
          </v-table>
      </div>

      <v-btn
        color="primary"
        block
        size="large"
        class="mt-6"
        @click="importData"
        :loading="inventoryStore.importing"
        :disabled="!mapping.title"
        prepend-icon="mdi-check-all"
      >
        Import {{ processedData.length }} Items
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.max-width-400 {
    max-width: 400px;
}
.truncate-cell {
    display: inline-block;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
