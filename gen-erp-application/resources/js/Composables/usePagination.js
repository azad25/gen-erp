import { ref, computed } from 'vue'

export function usePagination(initialPage = 1, perPage = 15) {
  const page = ref(initialPage)
  const total = ref(0)
  const lastPage = ref(1)

  const from = computed(() => (page.value - 1) * perPage + 1)
  const to = computed(() => Math.min(page.value * perPage, total.value))

  const links = computed(() => {
    const links = []
    
    if (page.value > 1) {
      links.push({ label: '«', url: `?page=${page.value - 1}`, active: false })
    }
    
    for (let i = 1; i <= lastPage.value; i++) {
      links.push({ label: i.toString(), url: `?page=${i}`, active: i === page.value })
    }
    
    if (page.value < lastPage.value) {
      links.push({ label: '»', url: `?page=${page.value + 1}`, active: false })
    }
    
    return links
  })

  const setTotal = (count) => {
    total.value = count
    lastPage.value = Math.ceil(count / perPage)
  }

  const setPage = (newPage) => {
    page.value = Math.max(1, Math.min(newPage, lastPage.value))
  }

  return { page, total, lastPage, from, to, links, setTotal, setPage }
}
