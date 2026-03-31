import { defineStore } from 'pinia';
import api from '../axios';

export const useLocationStore = defineStore('locations', {
    state: () => ({
        items: [],
        loading: false,
        filters: {
            search: '',
        }
    }),
    actions: {
        async fetchItems() {
            this.loading = true;
            try {
                const response = await api.get('/storage-locations');
                // StorageLocationController returns a simple array, not paginated for now as it's likely a small list
                this.items = response.data;
            } catch (error) {
                console.error('Failed to fetch storage locations:', error);
            } finally {
                this.loading = false;
            }
        },

        async createItem(itemData) {
            try {
                const response = await api.post('/storage-locations', itemData);
                await this.fetchItems();
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/storage-locations/${id}`, itemData);
                await this.fetchItems();
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async deleteItem(id) {
            try {
                await api.delete(`/storage-locations/${id}`);
                await this.fetchItems();
            } catch (error) {
                throw error;
            }
        }
    },
    getters: {
        filteredItems: (state) => {
            if (!state.filters.search) return state.items;
            const search = state.filters.search.toLowerCase();
            return state.items.filter(item => 
                item.name.toLowerCase().includes(search) || 
                (item.description && item.description.toLowerCase().includes(search))
            );
        }
    }
});
