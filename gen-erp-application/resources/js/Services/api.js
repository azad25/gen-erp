import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,  // Send cookies with requests
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// Add CSRF token from meta tag and Company ID from sessionStorage
api.interceptors.request.use(config => {
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token.content
  }
  
  // Also check for Bearer token (for API token auth)
  const apiToken = localStorage.getItem('api_token')
  if (apiToken) {
    config.headers.Authorization = `Bearer ${apiToken}`
  }
  
  // Add X-Company-ID header from sessionStorage
  const companyId = window.sessionStorage.getItem('active_company_id')
  if (companyId) {
    config.headers['X-Company-ID'] = companyId
  }
  
  // Debug logging
  console.log(`[API Request] ${config.method?.toUpperCase()} ${config.url}`, {
    companyId: companyId || 'not set',
    hasToken: !!apiToken,
    headers: config.headers
  })
  
  return config
})

api.interceptors.response.use(
  response => {
    console.log(`[API Response] ${response.config.method?.toUpperCase()} ${response.config.url} - ${response.status}`)
    return response
  },
  async error => {
    console.error(`[API Error] ${error.config?.method?.toUpperCase()} ${error.config?.url} - ${error.response?.status}`, error.response?.data)
    
    if (error.response?.status === 401) {
      // Session expired - redirect to login
      localStorage.removeItem('api_token')
      window.location.href = '/login'
    }
    
    if (error.response?.status === 403) {
      // Company not found or no access - log details
      console.error('403 Forbidden - Company context issue:', error.response?.data)
    }
    
    if (error.response?.status === 419) {
      // CSRF mismatch - refresh CSRF token and retry
      await axios.get('/sanctum/csrf-cookie')
      return api.request(error.config)
    }
    
    return Promise.reject(error)
  }
)

export default api
