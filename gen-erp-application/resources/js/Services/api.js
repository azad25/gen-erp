import axios from 'axios'

// Create axios instance
const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})

// Token management
const TOKEN_KEY = 'auth_token'
const COMPANY_KEY = 'active_company'

export const tokenManager = {
    get: () => localStorage.getItem(TOKEN_KEY),
    set: (token) => localStorage.setItem(TOKEN_KEY, token),
    remove: () => {
        localStorage.removeItem(TOKEN_KEY)
        localStorage.removeItem(COMPANY_KEY)
    },
    getCompany: () => {
        const company = localStorage.getItem(COMPANY_KEY)
        return company ? JSON.parse(company) : null
    },
    setCompany: (company) => localStorage.setItem(COMPANY_KEY, JSON.stringify(company))
}

// Request interceptor to add auth token
api.interceptors.request.use(
    (config) => {
        const token = tokenManager.get()
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        
        // Add company ID header if available
        const company = tokenManager.getCompany()
        if (company?.id) {
            config.headers['X-Company-ID'] = company.id
        }
        
        return config
    },
    (error) => Promise.reject(error)
)

// Response interceptor to handle auth errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            tokenManager.remove()
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Auth API methods
export const authAPI = {
    async login(credentials) {
        const response = await api.post('/auth/login', credentials)
        
        if (response.data.success) {
            const { token, active_company, two_factor_required, temp_token } = response.data.data
            
            if (two_factor_required) {
                // Store temporary token for 2FA challenge
                tokenManager.set(temp_token)
                return { 
                    success: true, 
                    requires2FA: true,
                    message: response.data.message 
                }
            }
            
            // Store auth token and company
            tokenManager.set(token)
            if (active_company) {
                tokenManager.setCompany(active_company)
            }
            
            return {
                success: true,
                user: response.data.data.user,
                company: active_company,
                companies: response.data.data.companies,
                requiresCompanySelection: response.data.data.requires_company_selection
            }
        }
        
        return { success: false, message: response.data.message }
    },

    async twoFactorChallenge(code) {
        const response = await api.post('/auth/two-factor/challenge', { code })
        
        if (response.data.success) {
            const { token, active_company } = response.data.data
            
            // Replace temporary token with full access token
            tokenManager.set(token)
            if (active_company) {
                tokenManager.setCompany(active_company)
            }
            
            return {
                success: true,
                user: response.data.data.user,
                company: active_company,
                companies: response.data.data.companies,
                requiresCompanySelection: response.data.data.requires_company_selection
            }
        }
        
        return { success: false, message: response.data.message }
    },

    async register(userData) {
        const response = await api.post('/auth/register', userData)
        
        if (response.data.success) {
            const { token, user, company } = response.data.data
            
            tokenManager.set(token)
            tokenManager.setCompany(company)
            
            return { success: true, user, company }
        }
        
        return { success: false, message: response.data.message }
    },

    async logout() {
        try {
            await api.post('/auth/logout')
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            tokenManager.remove()
            window.location.href = '/login'
        }
    },

    async getUser() {
        const response = await api.get('/auth/user')
        return response.data
    },

    async switchCompany(companyId) {
        const response = await api.post(`/auth/switch-company/${companyId}`)
        
        if (response.data.success) {
            tokenManager.setCompany(response.data.data.company)
            return response.data
        }
        
        throw new Error(response.data.message)
    },

    isAuthenticated() {
        return !!tokenManager.get()
    }
}

// Business API methods
export const businessAPI = {
    // Customers
    async getCustomers(params = {}) {
        const response = await api.get('/customers', { params })
        return response.data
    },

    async createCustomer(data) {
        const response = await api.post('/customers', data)
        return response.data
    },

    async updateCustomer(id, data) {
        const response = await api.put(`/customers/${id}`, data)
        return response.data
    },

    async deleteCustomer(id) {
        const response = await api.delete(`/customers/${id}`)
        return response.data
    },

    // Products
    async getProducts(params = {}) {
        const response = await api.get('/products', { params })
        return response.data
    },

    async createProduct(data) {
        const response = await api.post('/products', data)
        return response.data
    },

    async updateProduct(id, data) {
        const response = await api.put(`/products/${id}`, data)
        return response.data
    },

    async deleteProduct(id) {
        const response = await api.delete(`/products/${id}`)
        return response.data
    },

    // Sales Orders
    async getSalesOrders(params = {}) {
        const response = await api.get('/sales-orders', { params })
        return response.data
    },

    async createSalesOrder(data) {
        const response = await api.post('/sales-orders', data)
        return response.data
    },

    async confirmSalesOrder(id) {
        const response = await api.post(`/sales-orders/${id}/confirm`)
        return response.data
    },

    async convertToInvoice(id) {
        const response = await api.post(`/sales-orders/${id}/convert-to-invoice`)
        return response.data
    },

    // Invoices
    async getInvoices(params = {}) {
        const response = await api.get('/invoices', { params })
        return response.data
    },

    async createInvoice(data) {
        const response = await api.post('/invoices', data)
        return response.data
    },

    async updateInvoice(id, data) {
        const response = await api.put(`/invoices/${id}`, data)
        return response.data
    },

    async deleteInvoice(id) {
        const response = await api.delete(`/invoices/${id}`)
        return response.data
    },

    // Dashboard
    async getDashboard() {
        const response = await api.get('/dashboard')
        return response.data
    }
}

export default api
