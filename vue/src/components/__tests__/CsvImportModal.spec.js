import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CsvImportModal from '../CsvImportModal.vue';
import { createTestingPinia } from '@pinia/testing';
import { useInventoryStore } from '../../stores/inventory';
import { useLocationStore } from '../../stores/locations';
import Papa from 'papaparse';

vi.mock('papaparse', () => ({
    default: {
        parse: vi.fn()
    }
}));

// Mock window.alert
global.alert = vi.fn();

describe('CsvImportModal.vue', () => {
    let wrapper;
    let inventoryStore;
    let locationStore;

    beforeEach(() => {
        wrapper = mount(CsvImportModal, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
            props: {
                active: true
            }
        });
        
        inventoryStore = useInventoryStore();
        locationStore = useLocationStore();
        
        locationStore.items = [
            { id: 'loc-1', name: 'Shelf A' }
        ];
    });

    it('renders the initial upload step', () => {
        expect(wrapper.text()).toContain('Import from CSV');
        expect(wrapper.text()).toContain('Choose CSV File');
    });

    it('parses CSV and transitions to mapping step', async () => {
        const file = new File(['title,sku\nItem 1,123'], 'test.csv', { type: 'text/csv' });
        
        // Mock Papa.parse to immediately call the complete callback
        Papa.parse.mockImplementation((file, config) => {
            config.complete({
                data: [{ title: 'Item 1', sku: '123' }],
                meta: { fields: ['title', 'sku'] }
            });
        });

        // Trigger file input
        const fileInput = wrapper.findComponent({ name: 'v-file-input' });
        await fileInput.vm.$emit('change', { target: { files: [file] } });

        // Should transition to step 2
        expect(wrapper.text()).toContain('Map CSV Columns');
        expect(wrapper.vm.step).toBe(2);
        expect(wrapper.vm.csvHeaders).toEqual(['title', 'sku']);
    });

    it('calls bulkStore on importData', async () => {
        wrapper.vm.step = 2;
        wrapper.vm.mapping.title = 'title';
        wrapper.vm.mapping.sku = 'sku';
        wrapper.vm.csvData = [{ title: 'Item 1', sku: '123' }];
        
        inventoryStore.bulkStore.mockResolvedValue({ count: 1 });
        
        await wrapper.vm.importData();
        
        expect(inventoryStore.bulkStore).toHaveBeenCalledWith([{ title: 'Item 1', sku: '123' }]);
        expect(wrapper.emitted()).toHaveProperty('success');
    });
});
