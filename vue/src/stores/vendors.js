import { defineStore } from 'pinia';
import api from '../axios';

export const useVendorStore = defineStore('vendors', {
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
            is_preferred: false,
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
                    is_preferred: this.filters.is_preferred ? 1 : 0
                };
                
                const response = await api.get('/vendors', { params });
                
                this.items = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    per_page: response.data.per_page,
                    total: response.data.total,
                };
            } catch (error) {
                console.error('Failed to fetch vendors:', error);
            } finally {
                this.loading = false;
            }
        },
        async createItem(itemData) {
            try {
                const response = await api.post('/vendors', itemData);
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/vendors/${id}`, itemData);
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },
        async deleteItem(id) {
            try {
                await api.delete(`/vendors/${id}`);
                await this.fetchItems(this.pagination.current_page);
            } catch (error) {
                throw error;
            }
        },
        setFilter(key, value) {
            this.filters[key] = value;
            this.fetchItems(1);
        }
    }
});
