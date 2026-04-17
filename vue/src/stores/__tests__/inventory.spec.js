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

  it('bulkStore calls API and refetches items', async () => {
    api.post.mockResolvedValueOnce({ data: { count: 2 } })
    api.get.mockResolvedValueOnce({ data: { data: [] } })

    const store = useInventoryStore()
    const items = [{ title: 'Item 1' }, { title: 'Item 2' }]

    const result = await store.bulkStore(items)

    expect(api.post).toHaveBeenCalledWith('/inventory-items/bulk-store', { items })
    expect(result).toEqual({ count: 2 })
    expect(store.importing).toBe(false)
  })

  it('bulkUpdate calls API and invalidates caches', async () => {
    api.post.mockResolvedValueOnce({ data: { message: '2 items updated' } })
    api.get.mockResolvedValueOnce({ data: { data: [] } })

    const store = useInventoryStore()
    store.itemDetails['item-1'] = { id: 'item-1' }
    const ids = ['item-1', 'item-2']
    const updateData = { status: 'out_of_stock' }

    const result = await store.bulkUpdate(ids, updateData)

    expect(api.post).toHaveBeenCalledWith('/inventory-items/bulk-update', { ids, data: updateData })
    expect(store.itemDetails).toEqual({})
    expect(result).toEqual({ message: '2 items updated' })
    expect(store.bulkLoading).toBe(false)
  })

  it('bulkDelete calls API and invalidates caches', async () => {
    api.post.mockResolvedValueOnce({ data: { message: '2 items deleted' } })
    api.get.mockResolvedValueOnce({ data: { data: [] } })

    const store = useInventoryStore()
    store.itemDetails['item-1'] = { id: 'item-1' }
    const ids = ['item-1', 'item-2']

    const result = await store.bulkDelete(ids, true)

    expect(api.post).toHaveBeenCalledWith('/inventory-items/bulk-delete', { ids, permanent: true })
    expect(store.itemDetails).toEqual({})
    expect(result).toEqual({ message: '2 items deleted' })
    expect(store.bulkLoading).toBe(false)
  })
})
