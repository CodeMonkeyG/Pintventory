import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import UnifiedScanner from '../UnifiedScanner.vue';
import { createTestingPinia } from '@pinia/testing';
import { useInventoryStore } from '../../stores/inventory';
import { useRouter } from 'vue-router';

// Mock html5-qrcode
const mockStart = vi.fn().mockResolvedValue(true);
const mockStop = vi.fn().mockResolvedValue(true);

vi.mock('html5-qrcode', () => {
    return {
        Html5Qrcode: vi.fn().mockImplementation(() => {
            return {
                start: mockStart,
                stop: mockStop
            };
        })
    };
});

vi.mock('vue-router', () => ({
    useRouter: vi.fn(() => ({
        push: vi.fn()
    }))
}));

describe('UnifiedScanner.vue', () => {
    let wrapper;
    let inventoryStore;
    let router;

    beforeEach(() => {
        vi.clearAllMocks();
        
        router = { push: vi.fn() };
        useRouter.mockReturnValue(router);

        wrapper = mount(UnifiedScanner, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
            props: {
                active: true
            }
        });
        
        inventoryStore = useInventoryStore();
    });

    it('initializes and starts the scanner on mount if active', () => {
        expect(mockStart).toHaveBeenCalled();
    });

    it('emits found-item on Pintventory item QR scan', async () => {
        // Simulate successful scan
        const onScanSuccess = mockStart.mock.calls[0][2];
        await onScanSuccess('pintventory:item:123');

        expect(mockStop).toHaveBeenCalled();
        expect(wrapper.emitted('found-item')).toBeTruthy();
        expect(wrapper.emitted('found-item')[0]).toEqual(['123']);
    });

    it('emits found-location on Pintventory location QR scan', async () => {
        const onScanSuccess = mockStart.mock.calls[0][2];
        await onScanSuccess('pintventory:location:456');

        expect(mockStop).toHaveBeenCalled();
        expect(wrapper.emitted('found-location')).toBeTruthy();
        expect(wrapper.emitted('found-location')[0]).toEqual(['456']);
    });

    it('searches inventory and routes on standard barcode scan', async () => {
        const onScanSuccess = mockStart.mock.calls[0][2];
        await onScanSuccess('8675309');

        expect(mockStop).toHaveBeenCalled();
        expect(inventoryStore.setFilter).toHaveBeenCalledWith('search', '8675309');
        expect(wrapper.emitted('close')).toBeTruthy();
        expect(router.push).toHaveBeenCalledWith('/inventory');
    });
});
