import { ref, watch } from 'vue'

export function useSearch(initialQuery = '', debounceMs = 300) {
  const query = ref(initialQuery)
  const debouncedQuery = ref(initialQuery)
  let timeoutId = null

  const updateQuery = (value) => {
    query.value = value
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      debouncedQuery.value = value
    }, debounceMs)
  }

  const clear = () => {
    updateQuery('')
  }

  watch(query, (newVal) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      debouncedQuery.value = newVal
    }, debounceMs)
  })

  return { query, debouncedQuery, updateQuery, clear }
}
