import { defineStore } from 'pinia';
import api from '../axios';

/**
 * Customer Store - Manages customer data and list operations
 * 
 * Handles paginated customer lists, filtering, sorting, CRUD operations,
 * and caching for all-customers scenarios used in modals.
 * 
 * @type {import('pinia').Store}
 */
export const useCustomerStore = defineStore('customers', {
    state: () => ({
        /** @type {Object[]} List of customers for current page */
        items: [],
        /** @type {Object[]} Cache of all customers (used in modals and dropdowns) */
        allCustomers: [],
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
            sort_by: 'updated_at',
            sort_dir: 'desc',
        }
    }),
    actions: {
        /**
         * Fetch paginated customers with current filters applied
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
                };
                
                const response = await api.get('/customers', { params });
                
                this.items = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    per_page: response.data.per_page,
                    total: response.data.total,
                };
            } catch (error) {
                console.error('Failed to fetch customers:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch all customers with caching for modals/dropdowns
         * 
         * Returns cached customers if available without re-fetching.
         * Useful for customer selection in forms and modal dropdowns.
         * 
         * @returns {Promise<Object[]>} All customers or empty array on error
         */
        async fetchAllCustomers() {
            // Return cached customers if available
            if (this.allCustomers.length > 0) {
                return this.allCustomers;
            }

            try {
                const response = await api.get('/customers', { params: { per_page: 100 } });
                this.allCustomers = response.data.data;
                return this.allCustomers;
            } catch (error) {
                console.error('Failed to fetch all customers:', error);
                return [];
            }
        },

        /**
         * Create a new customer
         * 
         * @param {Object} itemData - Customer data to create
         * @returns {Promise<Object>} The created customer
         */
        async createItem(itemData) {
            try {
                const response = await api.post('/customers', itemData);
                this.allCustomers = []; // Invalidate cache
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Update an existing customer
         * 
         * @param {string} id - The customer ID to update
         * @param {Object} itemData - Updated customer data
         * @returns {Promise<Object>} The updated customer
         */
        async updateItem(id, itemData) {
            try {
                const response = await api.put(`/customers/${id}`, itemData);
                this.allCustomers = []; // Invalidate cache
                await this.fetchItems(this.pagination.current_page);
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Delete a customer
         * 
         * @param {string} id - The customer ID to delete
         * @returns {Promise<void>}
         */
        async deleteItem(id) {
            try {
                await api.delete(`/customers/${id}`);
                this.allCustomers = []; // Invalidate cache
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
