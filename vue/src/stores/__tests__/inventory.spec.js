import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useInventoryStore } from '../inventory'
import api from '../../axios'

vi.mock('../../axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  }
}))

describe('Inventory Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchItems updates items and pagination', async () => {
    const mockData = {
      data: [{ id: 1, title: 'Item 1' }],
      current_page: 1,
      last_page: 2,
      per_page: 25,
      total: 50
    }
    api.get.mockResolvedValueOnce({ data: mockData })

    const store = useInventoryStore()
    await store.fetchItems(1)

    expect(api.get).toHaveBeenCalledWith('/inventory-items', expect.any(Object))
    expect(store.items).toEqual(mockData.data)
    expect(store.pagination.total).toBe(50)
    expect(store.loading).toBe(false)
  })

  it('fetchItemDetail uses cache', async () => {
    const store = useInventoryStore()
    const mockItem = { id: 'item-1', title: 'Cached Item' }
    store.itemDetails['item-1'] = mockItem

    const result = await store.fetchItemDetail('item-1')

    expect(api.get).not.toHaveBeenCalled()
    expect(result).toEqual(mockItem)
  })

  it('fetchItemDetail fetches if not in cache', async () => {
    const mockItem = { id: 'item-1', title: 'New Item' }
    api.get.mockResolvedValueOnce({ data: mockItem })

    const store = useInventoryStore()
    const result = await store.fetchItemDetail('item-1')

    expect(api.get).toHaveBeenCalledWith('/inventory-items/item-1')
    expect(store.itemDetails['item-1']).toEqual(mockItem)
    expect(result).toEqual(mockItem)
  })

  it('deleteItem calls API and invalidates cache', async () => {
    api.delete.mockResolvedValueOnce({})
    // mock fetchItems call that happens after delete
    api.get.mockResolvedValueOnce({ data: { data: [] } })

    const store = useInventoryStore()
    store.itemDetails['item-1'] = { id: 'item-1' }

    await store.deleteItem('item-1')

    expect(api.delete).toHaveBeenCalledWith('/inventory-items/item-1')
    expect(store.itemDetails['item-1']).toBeUndefined()
  })
})
