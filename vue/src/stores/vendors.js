import { defineStore } from 'pinia';
import api from '../axios';

/**
 * Vendor Store - Manages vendor data and list operations
 * 
 * Handles paginated vendor lists, filtering, preferred vendor tracking,
 * and CRUD operations with caching for all-vendors scenarios.
 * 
 * @type {import('pinia').Store}
 */
export const useVendorStore = defineStore('vendors', {
    state: () => ({
        /** @type {Object[]} List of vendors for current page */
        items: [],
        /** @type {Object[]} Cache of all vendors (used in modals and dropdowns) */
        allVendors: [],
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
            is_preferred: false,
            sort_by: 'updated_at',
            sort_dir: 'desc',
        }
    }),
    actions: {
        /**
         * Fetch paginated vendors with current filters applied
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

        /**
         * Fetch all vendors with caching for modals/dropdowns
         * 
         * Returns cached vendors if available without re-fetching.
         * 
         * @returns {Promise<Object[]>} All vendors or empty array on error
         */
        async fetchAllVendors() {
            // Return cached vendors if available
            if (this.allVendors.length > 0) {
                return this.allVendors;
            }

            try {
                const response = await api.get('/vendors', { params: { per_page: 100 } });
                this.allVendors = response.data.data;
                return this.allVendors;
            } catch (error) {
                console.error('Failed to fetch all vendors:', error);
                return [];
            }
        },

        /**
         * Create a new vendor
         * 
         * @param {Object} itemData - Vendor data to create
         * @returns {Promise<Object>} The created vendor
         */
        async createItem(itemData) {
            try {
                const response = await api.post('/vendors', itemData);
                this.allVendors = []; // Invalidate cache
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Update an existing vendor
         * 
         * @param {string} id - The vendor ID to update
         * @param {Object} itemData - Updated vendor data
         * @returns {Promise<Object>} The updated vendor
         */
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/vendors/${id}`, itemData);
                this.allVendors = []; // Invalidate cache
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Delete a vendor
         * 
         * @param {string} id - The vendor ID to delete
         * @returns {Promise<void>}
         */
        async deleteItem(id) {
            try {
                await api.delete(`/vendors/${id}`);
                this.allVendors = []; // Invalidate cache
                await this.fetchItems(this.pagination.current_page);
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
            this.fetchItems(1);
        }
    }
});
