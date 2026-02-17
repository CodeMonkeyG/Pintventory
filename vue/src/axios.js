import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    withCredentials: true, // Send cookies with requests
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Intercept requests to get CSRF token for Laravel Sanctum
api.interceptors.request.use(async config => {
    // Only fetch CSRF token if it's a POST, PUT, PATCH, DELETE request
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(config.method.toUpperCase()) && !document.cookie.includes('XSRF-TOKEN')) {
        try {
            // Use global axios to avoid appending baseURL (/api)
            await axios.get('/sanctum/csrf-cookie', { 
                withCredentials: true,
                baseURL: '/' // Ensure it goes to root
            }); 
        } catch (error) {
            console.error('Failed to retrieve CSRF token:', error);
        }
    }
    return config;
});

export default api;
