import { defineStore } from 'pinia';
import api from '../axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loggedIn: false,
    }),
    actions: {
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
        async logout() {
            try {
                await api.post('/logout'); // Laravel's default logout endpoint
                this.user = null;
                this.loggedIn = false;
            } catch (error) {
                console.error('Logout failed:', error);
            }
        },
    },
    getters: {
        isAdmin: (state) => state.user && state.user.role === 'admin',
        isOwner: (state) => state.user && state.user.role === 'owner',
        isStaff: (state) => state.user && state.user.role === 'staff',
        isReadOnly: (state) => state.user && state.user.role === 'read-only',
    },
});
