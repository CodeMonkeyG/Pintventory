import { defineStore } from 'pinia';
import api from '../axios';

/**
 * Storage Location Store - Manages physical storage locations for inventory
 * 
 * Handles fetching, creating, updating, and deleting storage locations.
 * 
 * @type {import('pinia').Store}
 */
export const useLocationStore = defineStore('locations', {
    state: () => ({
        /** @type {Object[]} List of all storage locations for the authenticated user */
        items: [],
        /** @type {boolean} Loading state for fetch operations */
        loading: false,
        /** @type {Object} Active filters for locations */
        filters: {
            /** @type {string} Search query for filtering names and descriptions */
            search: '',
        }
    }),
    actions: {
        /**
         * Fetch all storage locations for the current user
         * 
         * @returns {Promise<void>}
         */
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

        /**
         * Create a new storage location
         * 
         * @param {Object} itemData - The data for the new location (name, description)
         * @returns {Promise<Object>} The created location object
         */
        async createItem(itemData) {
            try {
                const response = await api.post('/storage-locations', itemData);
                await this.fetchItems();
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Update an existing storage location
         * 
         * @param {number|string} id - The location ID
         * @param {Object} itemData - The updated location data
         * @returns {Promise<Object>} The updated location object
         */
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/storage-locations/${id}`, itemData);
                await this.fetchItems();
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Delete a storage location
         * 
         * @param {number|string} id - The location ID to delete
         * @returns {Promise<void>}
         */
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
        /**
         * Get filtered list of storage locations based on search query
         * 
         * @param {Object} state - The store state
         * @returns {Object[]} Filtered list of locations
         */
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
