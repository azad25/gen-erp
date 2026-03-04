import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePageBuilderStore = defineStore('pageBuilder', () => {
  // State
  const currentPage = ref(null)
  const sections = ref([])
  const selectedSection = ref(null)
  const selectedSectionIndex = ref(null)
  const isDirty = ref(false)
  const isLoading = ref(false)
  const currentDevice = ref('desktop')
  
  // Getters
  const hasUnsavedChanges = computed(() => isDirty.value)
  const sectionCount = computed(() => sections.value.length)
  
  // Actions
  const initializePage = (page, pageSections) => {
    currentPage.value = page
    sections.value = [...pageSections]
    selectedSection.value = null
    selectedSectionIndex.value = null
    isDirty.value = false
  }
  
  const addSection = (sectionType, index = null) => {
    const newSection = {
      id: Date.now(),
      type: sectionType.type,
      content: sectionType.defaultContent || {},
      sort_order: index !== null ? index : sections.value.length
    }
    
    if (index !== null) {
      sections.value.splice(index, 0, newSection)
      updateSortOrders()
    } else {
      sections.value.push(newSection)
    }
    
    markDirty()
    return newSection
  }
  
  const removeSection = (index) => {
    if (index >= 0 && index < sections.value.length) {
      sections.value.splice(index, 1)
      updateSortOrders()
      
      // Clear selection if removed section was selected
      if (selectedSectionIndex.value === index) {
        selectedSection.value = null
        selectedSectionIndex.value = null
      }
      
      markDirty()
    }
  }
  
  const duplicateSection = (index) => {
    if (index >= 0 && index < sections.value.length) {
      const originalSection = sections.value[index]
      const duplicatedSection = {
        ...originalSection,
        id: Date.now(),
        sort_order: index + 1
      }
      
      sections.value.splice(index + 1, 0, duplicatedSection)
      updateSortOrders()
      markDirty()
      
      return duplicatedSection
    }
  }
  
  const moveSection = (fromIndex, toIndex) => {
    if (fromIndex >= 0 && fromIndex < sections.value.length &&
        toIndex >= 0 && toIndex < sections.value.length &&
        fromIndex !== toIndex) {
      
      const section = sections.value.splice(fromIndex, 1)[0]
      sections.value.splice(toIndex, 0, section)
      updateSortOrders()
      
      // Update selected section index if needed
      if (selectedSectionIndex.value === fromIndex) {
        selectedSectionIndex.value = toIndex
      }
      
      markDirty()
    }
  }
  
  const updateSectionContent = (index, content) => {
    if (index >= 0 && index < sections.value.length) {
      sections.value[index].content = { ...content }
      markDirty()
    }
  }
  
  const selectSection = (section, index) => {
    selectedSection.value = section
    selectedSectionIndex.value = index
  }
  
  const clearSelection = () => {
    selectedSection.value = null
    selectedSectionIndex.value = null
  }
  
  const updateSortOrders = () => {
    sections.value.forEach((section, index) => {
      section.sort_order = index
    })
  }
  
  const markDirty = () => {
    isDirty.value = true
  }
  
  const markClean = () => {
    isDirty.value = false
  }
  
  const setDevice = (device) => {
    currentDevice.value = device
  }
  
  const setLoading = (loading) => {
    isLoading.value = loading
  }
  
  const getSectionById = (id) => {
    return sections.value.find(section => section.id === id)
  }
  
  const getSectionIndex = (id) => {
    return sections.value.findIndex(section => section.id === id)
  }
  
  const reorderSections = (newOrder) => {
    sections.value = [...newOrder]
    updateSortOrders()
    markDirty()
  }
  
  const resetStore = () => {
    currentPage.value = null
    sections.value = []
    selectedSection.value = null
    selectedSectionIndex.value = null
    isDirty.value = false
    isLoading.value = false
    currentDevice.value = 'desktop'
  }
  
  return {
    // State
    currentPage,
    sections,
    selectedSection,
    selectedSectionIndex,
    isDirty,
    isLoading,
    currentDevice,
    
    // Getters
    hasUnsavedChanges,
    sectionCount,
    
    // Actions
    initializePage,
    addSection,
    removeSection,
    duplicateSection,
    moveSection,
    updateSectionContent,
    selectSection,
    clearSelection,
    updateSortOrders,
    markDirty,
    markClean,
    setDevice,
    setLoading,
    getSectionById,
    getSectionIndex,
    reorderSections,
    resetStore
  }
})