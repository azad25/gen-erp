export default defineNuxtPlugin(() => {
  const { initializePerformanceMonitoring, preloadCriticalResources } = usePerformance()
  
  // Initialize performance monitoring
  initializePerformanceMonitoring()
  
  // Preload critical resources
  const criticalResources = [
    { href: '/fonts/inter-400.woff2', as: 'font', type: 'font/woff2' },
    { href: '/fonts/inter-600.woff2', as: 'font', type: 'font/woff2' },
    { href: '/fonts/inter-700.woff2', as: 'font', type: 'font/woff2' }
  ]
  
  preloadCriticalResources(criticalResources)
  
  // Add performance observer for navigation timing
  if ('PerformanceObserver' in window) {
    const navigationObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      entries.forEach((entry) => {
        console.log('Navigation timing:', {
          name: entry.name,
          duration: entry.duration,
          startTime: entry.startTime
        })
      })
    })
    
    navigationObserver.observe({ entryTypes: ['navigation'] })
  }
})