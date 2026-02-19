import { defineStore } from 'pinia';
import api from '../axios';

/**
 * Authentication Store - Manages user authentication state and permissions
 * 
 * Handles user login state, role-based access control, and logout functionality.
 * 
 * @type {import('pinia').Store}
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        /** @type {Object|null} Current authenticated user object with role and permissions */
        user: null,
        /** @type {boolean} Whether the user is currently authenticated */
        loggedIn: false,
    }),
    actions: {
        /**
         * Fetch current authenticated user from server
         * 
         * Updates user state and login status. On error, clears user data.
         * 
         * @returns {Promise<void>}
         */
        async fetchUser() {
            try {
                const response = await api.get('/user');
                this.user = response.data;
                this.loggedIn = true;
            } catch (error) {
                this.user = null;
                this.loggedIn = false;
                console.error('Failed to fetch user:', error);
            }
        },

        /**
         * Log out the current user
         * 
         * Calls the logout endpoint and clears all user data from state.
         * 
         * @returns {Promise<void>}
         */
        async logout() {
            try {
                await api.post('/logout');
                this.user = null;
                this.loggedIn = false;
            } catch (error) {
                console.error('Logout failed:', error);
            }
        },
    },
    getters: {
        /**
         * Check if the current user has admin role
         * 
         * @param {Object} state - The store state
         * @returns {boolean} True if user is an admin
         */
        isAdmin: (state) => state.user && state.user.role === 'admin',

        /**
         * Check if the current user has owner role
         * 
         * @param {Object} state - The store state
         * @returns {boolean} True if user is an owner
         */
        isOwner: (state) => state.user && state.user.role === 'owner',

        /**
         * Check if the current user has staff role
         * 
         * @param {Object} state - The store state
         * @returns {boolean} True if user is staff
         */
        isStaff: (state) => state.user && state.user.role === 'staff',

        /**
         * Check if the current user has read-only role
         * 
         * @param {Object} state - The store state
         * @returns {boolean} True if user has read-only access
         */
        isReadOnly: (state) => state.user && state.user.role === 'read-only',
    },
});
