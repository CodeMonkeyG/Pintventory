import { defineStore } from 'pinia';
import api from '../axios';

/**
 * Inventory Store - Manages inventory item state, caching, and API interactions
 * 
 * @type {import('pinia').Store}
 */
export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        /** @type {Object[]} List of inventory items for current page */
        items: [],
        /** @type {Object.<string, Object>} Cache for individual item details keyed by ID */
        itemDetails: {},
        /** @type {Object} Pagination metadata */
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
        },
        /** @type {boolean} Loading state */
        loading: false,
        /** @type {Object} Active filters and sort options */
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
        /**
         * Fetch paginated inventory items with current filters applied
         * 
         * @param {number} [page=1] - Page number to fetch
         * @returns {Promise<void>}
         */
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

        /**
         * Fetch detailed item information with caching
         * 
         * @param {string} id - The inventory item ID
         * @returns {Promise<Object>} The item details object
         */
        async fetchItemDetail(id) {
            // Return cached detail if available
            if (this.itemDetails[id]) {
                return this.itemDetails[id];
            }

            try {
                const response = await api.get(`/inventory-items/${id}`);
                this.itemDetails[id] = response.data;
                return response.data;
            } catch (error) {
                console.error('Failed to fetch item detail:', error);
                throw error;
            }
        },

        /**
         * Create a new inventory item
         * 
         * @param {Object} itemData - Item data to create
         * @returns {Promise<Object>} The created item
         */
        async createItem(itemData) {
            try {
                const response = await api.post('/inventory-items', itemData);
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Update an existing inventory item
         * 
         * @param {string} id - The item ID to update
         * @param {Object} itemData - Updated item data
         * @returns {Promise<Object>} The updated item
         */
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/inventory-items/${id}`, itemData);
                // Invalidate cache for this item
                delete this.itemDetails[id];
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Delete an inventory item
         * 
         * @param {string} id - The item ID to delete
         * @returns {Promise<void>}
         */
        async deleteItem(id) {
            try {
                await api.delete(`/inventory-items/${id}`);
                delete this.itemDetails[id];
                await this.fetchItems(this.pagination.current_page);
            } catch (error) {
                throw error;
            }
        },

        /**
         * Upload a photo for an inventory item
         * 
         * @param {string} itemId - The inventory item ID
         * @param {File} file - The photo file to upload
         * @returns {Promise<Object>} The created photo metadata
         */
        async uploadPhoto(itemId, file) {
            try {
                const formData = new FormData();
                formData.append('photo', file);
                const response = await api.post(`/inventory-items/${itemId}/photos`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                // Invalidate cache
                delete this.itemDetails[itemId];
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Delete a photo
         * 
         * @param {string} photoId - The photo ID to delete
         * @returns {Promise<void>}
         */
        async deletePhoto(photoId) {
            try {
                await api.delete(`/photos/${photoId}`);
                // Invalidate all caches as we don't know which item this belongs to
                this.itemDetails = {};
            } catch (error) {
                throw error;
            }
        },

        /**
         * Update a filter and reset to first page
         * 
         * @param {string} key - Filter key to update
         * @param {*} value - New filter value
         * @returns {void}
         */
        setFilter(key, value) {
            this.filters[key] = value;
            this.fetchItems(1); // Reset to first page on filter change
        }
    }
});