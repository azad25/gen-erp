import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  const getAuthHeaders = () => {
    const page = usePage()
    const token = page.props.auth?.api_token || document.querySelector('meta[name="api-token"]')?.content
    
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(token && { 'Authorization': `Bearer ${token}` })
    }
  }

  const handleResponse = async (response) => {
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Network error' }))
      throw new Error(errorData.message || `HTTP ${response.status}`)
    }
    return response.json()
  }

  const get = async (url, params = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const queryString = new URLSearchParams(params).toString()
      const fullUrl = queryString ? `${url}?${queryString}` : url
      
      const response = await fetch(fullUrl, {
        method: 'GET',
        headers: getAuthHeaders()
      })
      
      return await handleResponse(response)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const post = async (url, data = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })
      
      return await handleResponse(response)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const put = async (url, data = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(url, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(data)
      })
      
      return await handleResponse(response)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const del = async (url) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })
      
      return await handleResponse(response)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    get,
    post,
    put,
    delete: del
  }
}