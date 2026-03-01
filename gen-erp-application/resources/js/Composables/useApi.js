import { ref } from 'vue'
import api from '../Services/api.js'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  const get = async (url, params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(url, { params })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const post = async (url, data = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(url, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const put = async (url, data = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(url, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const del = async (url) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.delete(url)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return { loading, error, get, post, put, delete: del }
}
