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

// Add company ID header for API requests
window.axios.interceptors.request.use(config => {
    // Add company ID from sessionStorage for API calls
    const companyId = sessionStorage.getItem('active_company_id');
    if (config.url && config.url.includes('/api/')) {
        if (companyId) {
            config.headers['X-Company-ID'] = companyId;
            console.log('[Axios] Adding company ID header:', companyId, 'for', config.url);
        } else {
            console.warn('[Axios] No company ID found for API request:', config.url);
            console.warn('[Axios] This may cause 401/403 errors');
        }
    }
    return config;
}, error => {
    return Promise.reject(error);
});

// Handle 401/419 errors globally
window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        
        if (error.response?.status === 401) {
            // Check if this is an API request or a page navigation
            const isApiRequest = originalRequest.url && originalRequest.url.includes('/api/');
            
            if (isApiRequest) {
                // For API requests, don't redirect immediately - let the component handle it
                console.error('[Axios] 401 Unauthorized on API request:', originalRequest.url);
                return Promise.reject(error);
            } else {
                // For non-API requests, redirect to login
                console.error('[Axios] 401 Unauthorized - redirecting to login');
                window.location.href = '/login';
            }
        }
        
        if (error.response?.status === 419) {
            // CSRF mismatch - refresh CSRF token and retry
            try {
                await window.axios.get('/sanctum/csrf-cookie');
                // Update CSRF token from cookie
                const newToken = document.head.querySelector('meta[name="csrf-token"]');
                if (newToken) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken.content;
                }
                return window.axios.request(originalRequest);
            } catch (csrfError) {
                console.error('[Axios] Failed to refresh CSRF token:', csrfError);
                return Promise.reject(error);
            }
        }
        
        if (error.response?.status === 403) {
            // Forbidden - might be company context issue
            console.error('[Axios] 403 Forbidden:', originalRequest.url);
            const isApiRequest = originalRequest.url && originalRequest.url.includes('/api/');
            
            if (isApiRequest) {
                // For API requests, don't redirect - let component handle gracefully
                return Promise.reject(error);
            }
        }
        
        return Promise.reject(error);
    }
);
