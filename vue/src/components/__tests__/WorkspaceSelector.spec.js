import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../stores/auth'
import WorkspaceSelector from '../WorkspaceSelector.vue'

// Basic mock for Vuetify components
const vComponents = {
  VMenu: { 
    template: '<div><slot name="activator" :props="{}"></slot><div class="v-menu-content"><slot></slot></div></div>' 
  },
  VBtn: { template: '<button class="v-btn"><slot></slot></button>' },
  VList: { template: '<div class="v-list"><slot></slot></div>' },
  VListItem: { template: '<div class="v-list-item"><slot name="prepend"></slot><slot></slot><slot name="append"></slot></div>' },
  VListItemTitle: { template: '<div class="v-list-item-title"><slot></slot></div>' },
  VListSubheader: { template: '<div class="v-list-subheader"><slot></slot></div>' },
  VDivider: { template: '<hr />' },
  VDialog: { template: '<div v-if="$attrs.modelValue" class="v-dialog"><slot></slot></div>' },
  VCard: { template: '<div class="v-card"><slot></slot></div>' },
  VCardText: { template: '<div class="v-card-text"><slot></slot></div>' },
  VCardActions: { template: '<div class="v-card-actions"><slot></slot></div>' },
  VTextField: { template: '<input class="v-text-field" />' },
  VSpacer: { template: '<div class="v-spacer"></div>' },
  VIcon: { template: '<i class="v-icon"></i>' },
  VChip: { template: '<span class="v-chip"><slot></slot></span>' },
}

describe('WorkspaceSelector.vue', () => {
  it('renders the current workspace name when logged in', () => {
    const auth = useAuthStore()
    auth.loggedIn = true
    auth.user = {
      current_workspace: { id: 1, name: 'Main Workspace' },
      workspaces: [{ id: 1, name: 'Main Workspace' }]
    }

    const wrapper = mount(WorkspaceSelector, {
      global: {
        components: vComponents
      }
    })

    expect(wrapper.text()).toContain('Main Workspace')
  })

  it('shows "Select Workspace" if no workspace is active', () => {
    const auth = useAuthStore()
    auth.loggedIn = true
    auth.user = {
      current_workspace: null,
      workspaces: []
    }

    const wrapper = mount(WorkspaceSelector, {
      global: {
        components: vComponents
      }
    })

    expect(wrapper.text()).toContain('Select Workspace')
  })

  it('calls switchWorkspace when a workspace is selected', async () => {
    const auth = useAuthStore()
    auth.loggedIn = true
    auth.user = {
      current_workspace: { id: 1, name: 'WS1' },
      workspaces: [
        { id: 1, name: 'WS1' },
        { id: 2, name: 'WS2' }
      ]
    }
    
    auth.switchWorkspace = vi.fn()

    const wrapper = mount(WorkspaceSelector, {
      global: {
        components: vComponents
      }
    })

    // Find the item for WS2
    const items = wrapper.findAll('.v-list-item')
    const ws2Item = items.find(i => i.text().includes('WS2'))
    
    expect(ws2Item).toBeDefined()
    await ws2Item.trigger('click')

    expect(auth.switchWorkspace).toHaveBeenCalledWith(2)
  })
})
