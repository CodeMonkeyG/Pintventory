import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import InventoryModal from '../InventoryModal.vue'

// Mocking the stores
vi.mock('../../stores/vendors', () => ({
  useVendorStore: () => ({
    allVendors: [],
    fetchAllVendors: vi.fn()
  })
}))
vi.mock('../../stores/customers', () => ({
  useCustomerStore: () => ({
    allCustomers: [],
    fetchAllCustomers: vi.fn()
  })
}))
vi.mock('../../stores/locations', () => ({
  useLocationStore: () => ({
    items: [],
    fetchItems: vi.fn()
  })
}))

// Mocking Vuetify's useDisplay
vi.mock('vuetify', () => ({
  useDisplay: () => ({
    mobile: false
  })
}))

const vComponents = {
  VDialog: { template: '<div v-if="$attrs.modelValue" class="v-dialog"><slot></slot></div>' },
  VCard: { template: '<div class="v-card"><slot></slot></div>' },
  VCardTitle: { template: '<div class="v-card-title"><slot></slot></div>' },
  VCardText: { template: '<div class="v-card-text"><slot></slot></div>' },
  VCardActions: { template: '<div class="v-card-actions"><slot></slot></div>' },
  VBtn: { template: '<button class="v-btn"><slot></slot></button>' },
  VIcon: { template: '<i class="v-icon"></i>' },
  VSpacer: { template: '<div class="v-spacer"></div>' },
  VTabs: { template: '<div class="v-tabs"><slot></slot></div>' },
  VTab: { template: '<div class="v-tab"><slot></slot></div>' },
  VWindow: { template: '<div class="v-window"><slot></slot></div>' },
  VWindowItem: { template: '<div class="v-window-item"><slot></slot></div>' },
  VTextField: { template: '<input class="v-text-field" />' },
  VSelect: { template: '<select class="v-select"></select>' },
  VTextarea: { template: '<textarea class="v-textarea"></textarea>' },
  VImg: { template: '<img class="v-img" />' },
  VDivider: { template: '<hr />' },
  VProgressLinear: { template: '<div class="v-progress-linear"></div>' },
  VExpandTransition: { template: '<div><slot></slot></div>' },
  VRow: { template: '<div class="v-row"><slot></slot></div>' },
  VCol: { template: '<div class="v-col"><slot></slot></div>' },
  VMenu: { template: '<div><slot name="activator" :props="{}"></slot><slot></slot></div>' },
  VList: { template: '<div><slot></slot></div>' },
  VListItem: { template: '<div><slot></slot></div>' },
  VListItemTitle: { template: '<div><slot></slot></div>' },
  VTable: { template: '<table><slot></slot></table>' },
  VToolbar: { template: '<div><slot></slot></div>' },
  VToolbarTitle: { template: '<div><slot></slot></div>' },
  VChip: { template: '<span><slot></slot></span>' },
}

describe('InventoryModal.vue', () => {
  it('renders correctly when shown', () => {
    const wrapper = mount(InventoryModal, {
      props: {
        show: true,
        item: null
      },
      global: {
        components: vComponents,
        stubs: {
            PhotoGallery: true
        }
      }
    })

    expect(wrapper.text()).toContain('Add New Inventory Item')
  })

  it('renders edit mode correctly', () => {
    const wrapper = mount(InventoryModal, {
      props: {
        show: true,
        item: { id: '1', title: 'Existing Item', status: 'in_stock' }
      },
      global: {
        components: vComponents,
        stubs: {
            PhotoGallery: true
        }
      }
    })

    expect(wrapper.text()).toContain('Edit Inventory Item')
  })
})
