import axios from 'axios';
window.axios = axios;

// Configure axios for Sanctum SPA authentication
window.axios.defaults.withCredentials = true;  // Send cookies with requests
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Get CSRF token from meta tag and set it
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

// Handle 401/419 errors globally
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            // Session expired - redirect to login
            window.location.href = '/login';
        }
        
        if (error.response?.status === 419) {
            // CSRF mismatch - refresh CSRF token and retry
            await window.axios.get('/sanctum/csrf-cookie');
            // Update CSRF token from cookie
            const newToken = document.head.querySelector('meta[name="csrf-token"]');
            if (newToken) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken.content;
            }
            return window.axios.request(error.config);
        }
        
        return Promise.reject(error);
    }
);
