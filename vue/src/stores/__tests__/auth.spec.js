import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth'
import api from '../../axios'

vi.mock('../../axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
  }
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initial state is logged out', () => {
    const store = useAuthStore()
    expect(store.user).toBeNull()
    expect(store.loggedIn).toBe(false)
  })

  it('fetchUser sets user and loggedIn', async () => {
    const mockUser = { id: 1, name: 'Test User', role: 'admin' }
    api.get.mockResolvedValueOnce({ data: mockUser })

    const store = useAuthStore()
    await store.fetchUser()

    expect(api.get).toHaveBeenCalledWith('/user')
    expect(store.user).toEqual(mockUser)
    expect(store.loggedIn).toBe(true)
  })

  it('isAdmin returns true for admin role', () => {
    const store = useAuthStore()
    store.user = { role: 'admin' }
    expect(store.isAdmin).toBe(true)
    
    store.user = { role: 'staff' }
    expect(store.isAdmin).toBe(false)
  })

  it('logout clears state', async () => {
    const store = useAuthStore()
    store.user = { id: 1 }
    store.loggedIn = true
    
    api.post.mockResolvedValueOnce({})

    await store.logout()

    expect(api.post).toHaveBeenCalledWith('/logout')
    expect(store.user).toBeNull()
    expect(store.loggedIn).toBe(false)
  })
})
