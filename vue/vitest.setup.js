import { config } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach } from 'vitest'

// Create a new pinia instance before each test
beforeEach(() => {
  const pinia = createPinia()
  setActivePinia(pinia)
  config.global.plugins = [pinia]
})

// Mocking Vuetify
config.global.mocks = {
  $vuetify: {
    display: {
      mdAndUp: true,
      smAndDown: false,
    },
    theme: {
      global: {
        name: 'light'
      }
    }
  }
}
