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
         * Create a new workspace
         */
        async createWorkspace(name) {
            try {
                const response = await api.post('/workspaces', { name });
                this.user = await this.fetchUser(); // Refresh user data to get updated workspaces list
                // Force reload of other stores to clear cached data from previous workspace
                window.location.reload(); 
            } catch (error) {
                console.error('Failed to create workspace:', error);
                alert('Failed to create workspace');
            }
        },

        /**
         * Switch active workspace
         */
        async switchWorkspace(workspaceId) {
            try {
                const response = await api.put('/user', {
                    current_workspace_id: workspaceId
                });
                this.user = response.data;
                // Force reload of other stores to clear cached data from previous workspace
                window.location.reload(); 
            } catch (error) {
                console.error('Failed to switch workspace:', error);
                alert('Failed to switch workspace');
            }
        },

        /**
         * Log out the current user
         */
        async logout() {
            try {
                await api.post('/logout');
            } catch (error) {
                console.error('Logout failed:', error);
            } finally {
                this.user = null;
                this.loggedIn = false;
            }
        },
    },
    getters: {
        /**
         * Get the current active workspace
         */
        currentWorkspace: (state) => state.user ? state.user.current_workspace : null,

        /**
         * Get all workspaces the user belongs to
         */
        workspaces: (state) => state.user ? state.user.workspaces : [],

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
