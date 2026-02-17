import { defineStore } from 'pinia';
import api from '../axios';

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        items: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
        },
        loading: false,
        filters: {
            search: '',
            status: '',
            tag: '',
            low_stock: false,
            sort_by: 'updated_at',
            sort_dir: 'desc',
        }
    }),
    actions: {
        async fetchItems(page = 1) {
            this.loading = true;
            try {
                const params = {
                    page,
                    per_page: this.pagination.per_page,
                    ...this.filters,
                    low_stock: this.filters.low_stock ? 1 : 0
                };
                
                const response = await api.get('/inventory-items', { params });
                
                this.items = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    per_page: response.data.per_page,
                    total: response.data.total,
                };
            } catch (error) {
                console.error('Failed to fetch inventory:', error);
            } finally {
                this.loading = false;
            }
        },
        async createItem(itemData) {
            try {
                const response = await api.post('/inventory-items', itemData);
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/inventory-items/${id}`, itemData);
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },
        async deleteItem(id) {
            try {
                await api.delete(`/inventory-items/${id}`);
                await this.fetchItems(this.pagination.current_page);
            } catch (error) {
                throw error;
            }
        },
        setFilter(key, value) {
            this.filters[key] = value;
            this.fetchItems(1); // Reset to first page on filter change
        }
    }
});
