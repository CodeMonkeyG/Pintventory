import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import QrLabel from '../QrLabel.vue';
import QRCode from 'qrcode';

vi.mock('qrcode', () => {
    return {
        default: {
            toDataURL: vi.fn().mockResolvedValue('data:image/png;base64,mockqr')
        }
    };
});

describe('QrLabel.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders and calls generateQR with correct item format', async () => {
        const wrapper = mount(QrLabel, {
            props: {
                id: '123',
                type: 'item',
                title: 'Test Item',
                sku: 'SKU-123'
            }
        });

        // Wait for next tick so onMounted has a chance to execute async functions
        await new Promise(resolve => setTimeout(resolve, 0));

        expect(QRCode.toDataURL).toHaveBeenCalledWith(
            'pintventory:item:123',
            expect.objectContaining({ width: 200 })
        );

        expect(wrapper.text()).toContain('Test Item');
        expect(wrapper.text()).toContain('SKU-123');
        expect(wrapper.text()).toContain('PINTVENTORY');
    });

    it('renders and calls generateQR with correct location format', async () => {
        const wrapper = mount(QrLabel, {
            props: {
                id: '456',
                type: 'location',
                title: 'Shelf A',
                size: 150
            }
        });

        await new Promise(resolve => setTimeout(resolve, 0));

        expect(QRCode.toDataURL).toHaveBeenCalledWith(
            'pintventory:location:456',
            expect.objectContaining({ width: 150 })
        );

        expect(wrapper.text()).toContain('Shelf A');
    });

    it('regenerates QR code when id prop changes', async () => {
        const wrapper = mount(QrLabel, {
            props: {
                id: '123',
            }
        });

        await new Promise(resolve => setTimeout(resolve, 0));
        expect(QRCode.toDataURL).toHaveBeenCalledTimes(1);

        await wrapper.setProps({ id: '999' });
        await new Promise(resolve => setTimeout(resolve, 0));
        
        expect(QRCode.toDataURL).toHaveBeenCalledTimes(2);
        expect(QRCode.toDataURL).toHaveBeenCalledWith(
            'pintventory:item:999',
            expect.any(Object)
        );
    });
});
