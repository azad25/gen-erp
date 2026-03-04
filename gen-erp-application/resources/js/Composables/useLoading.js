import { ref, computed } from 'vue'

const loadingStates = ref(new Map())

export function useLoading() {
  const setLoading = (key, isLoading, message = '') => {
    if (isLoading) {
      loadingStates.value.set(key, {
        loading: true,
        message,
        startTime: Date.now()
      })
    } else {
      loadingStates.value.delete(key)
    }
  }
  
  const isLoading = (key) => {
    return loadingStates.value.has(key)
  }
  
  const getLoadingMessage = (key) => {
    const state = loadingStates.value.get(key)
    return state?.message || ''
  }
  
  const getLoadingDuration = (key) => {
    const state = loadingStates.value.get(key)
    if (!state) return 0
    return Date.now() - state.startTime
  }
  
  const isAnyLoading = computed(() => {
    return loadingStates.value.size > 0
  })
  
  const getAllLoadingStates = computed(() => {
    const states = {}
    for (const [key, value] of loadingStates.value.entries()) {
      states[key] = {
        ...value,
        duration: Date.now() - value.startTime
      }
    }
    return states
  })
  
  const clearAllLoading = () => {
    loadingStates.value.clear()
  }
  
  const withLoading = async (key, asyncFn, message = '') => {
    setLoading(key, true, message)
    try {
      const result = await asyncFn()
      return result
    } finally {
      setLoading(key, false)
    }
  }
  
  return {
    setLoading,
    isLoading,
    getLoadingMessage,
    getLoadingDuration,
    isAnyLoading,
    getAllLoadingStates,
    clearAllLoading,
    withLoading
  }
}