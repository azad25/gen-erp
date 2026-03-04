import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  const showToast = (message, type = 'info', duration = 5000, options = {}) => {
    const id = ++toastId
    const toast = {
      id,
      message,
      type,
      duration,
      startTime: Date.now(),
      visible: true,
      actions: options.actions || [],
      persistent: options.persistent || false
    }
    
    toasts.value.push(toast)
    
    // Auto-remove toast after duration (unless persistent)
    if (duration > 0 && !toast.persistent) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
    
    return id
  }
  
  const showSuccess = (message, duration = 4000, options = {}) => {
    return showToast(message, 'success', duration, options)
  }
  
  const showError = (message, duration = 6000, options = {}) => {
    return showToast(message, 'error', duration, options)
  }
  
  const showWarning = (message, duration = 5000, options = {}) => {
    return showToast(message, 'warning', duration, options)
  }
  
  const showInfo = (message, duration = 4000, options = {}) => {
    return showToast(message, 'info', duration, options)
  }
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const clearAllToasts = () => {
    toasts.value = []
  }
  
  const updateToast = (id, updates) => {
    const toast = toasts.value.find(t => t.id === id)
    if (toast) {
      Object.assign(toast, updates)
    }
  }
  
  return {
    toasts,
    showToast,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    removeToast,
    clearAllToasts,
    updateToast
  }
}