import { ref } from 'vue'
import { useToast } from './useToast'

const errors = ref([])
const { showToast } = useToast()

export function useErrorHandler() {
  const handleError = (error, context = '') => {
    console.error(`Error ${context}:`, error)
    
    let message = 'An unexpected error occurred'
    
    if (error.response) {
      // HTTP error response
      const status = error.response.status
      const data = error.response.data
      
      if (status === 401) {
        message = 'Authentication required. Please log in again.'
        // Redirect to login or refresh token
        window.location.href = '/login'
        return
      } else if (status === 403) {
        message = 'You do not have permission to perform this action.'
      } else if (status === 404) {
        message = 'The requested resource was not found.'
      } else if (status === 422) {
        // Validation errors
        if (data.errors) {
          const validationErrors = Object.values(data.errors).flat()
          message = validationErrors.join(', ')
        } else {
          message = data.message || 'Validation failed.'
        }
      } else if (status === 429) {
        message = 'Too many requests. Please try again later.'
      } else if (status >= 500) {
        message = 'Server error. Please try again later.'
      } else {
        message = data.message || `HTTP ${status} error occurred.`
      }
    } else if (error.message) {
      // Network or other errors
      if (error.message.includes('Network Error')) {
        message = 'Network error. Please check your connection.'
      } else if (error.message.includes('timeout')) {
        message = 'Request timed out. Please try again.'
      } else {
        message = error.message
      }
    }
    
    const errorObj = {
      id: Date.now(),
      message,
      context,
      timestamp: new Date(),
      originalError: error
    }
    
    errors.value.unshift(errorObj)
    
    // Keep only last 50 errors
    if (errors.value.length > 50) {
      errors.value = errors.value.slice(0, 50)
    }
    
    // Show toast notification
    showToast(message, 'error')
    
    return errorObj
  }
  
  const handleApiError = (error, operation = '') => {
    return handleError(error, `API ${operation}`)
  }
  
  const handleValidationError = (errors, context = 'Validation') => {
    const messages = Object.values(errors).flat()
    const message = messages.join(', ')
    
    showToast(message, 'error')
    
    return {
      id: Date.now(),
      message,
      context,
      timestamp: new Date(),
      validationErrors: errors
    }
  }
  
  const clearErrors = () => {
    errors.value = []
  }
  
  const removeError = (id) => {
    const index = errors.value.findIndex(error => error.id === id)
    if (index > -1) {
      errors.value.splice(index, 1)
    }
  }
  
  const getRecentErrors = (limit = 10) => {
    return errors.value.slice(0, limit)
  }
  
  return {
    errors,
    handleError,
    handleApiError,
    handleValidationError,
    clearErrors,
    removeError,
    getRecentErrors
  }
}