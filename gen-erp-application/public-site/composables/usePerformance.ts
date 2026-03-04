interface PerformanceMetrics {
  fcp: number // First Contentful Paint
  lcp: number // Largest Contentful Paint
  fid: number // First Input Delay
  cls: number // Cumulative Layout Shift
  ttfb: number // Time to First Byte
}

interface ResourceTiming {
  name: string
  duration: number
  size: number
  type: string
}

export const usePerformance = () => {
  const metrics = ref<Partial<PerformanceMetrics>>({})
  const resourceTimings = ref<ResourceTiming[]>([])
  
  // Measure Core Web Vitals
  const measureCoreWebVitals = () => {
    if (!process.client || !('PerformanceObserver' in window)) return
    
    // First Contentful Paint (FCP)
    const fcpObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      const fcpEntry = entries.find(entry => entry.name === 'first-contentful-paint')
      if (fcpEntry) {
        metrics.value.fcp = fcpEntry.startTime
        console.log(`FCP: ${fcpEntry.startTime.toFixed(2)}ms`)
      }
    })
    fcpObserver.observe({ entryTypes: ['paint'] })
    
    // Largest Contentful Paint (LCP)
    const lcpObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      const lastEntry = entries[entries.length - 1]
      metrics.value.lcp = lastEntry.startTime
      console.log(`LCP: ${lastEntry.startTime.toFixed(2)}ms`)
    })
    lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] })
    
    // First Input Delay (FID)
    const fidObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      entries.forEach((entry: any) => {
        metrics.value.fid = entry.processingStart - entry.startTime
        console.log(`FID: ${metrics.value.fid.toFixed(2)}ms`)
      })
    })
    fidObserver.observe({ entryTypes: ['first-input'] })
    
    // Cumulative Layout Shift (CLS)
    let clsValue = 0
    const clsObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      entries.forEach((entry: any) => {
        if (!entry.hadRecentInput) {
          clsValue += entry.value
        }
      })
      metrics.value.cls = clsValue
      console.log(`CLS: ${clsValue.toFixed(4)}`)
    })
    clsObserver.observe({ entryTypes: ['layout-shift'] })
    
    // Time to First Byte (TTFB)
    const navigationEntry = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    if (navigationEntry) {
      metrics.value.ttfb = navigationEntry.responseStart - navigationEntry.requestStart
      console.log(`TTFB: ${metrics.value.ttfb.toFixed(2)}ms`)
    }
  }
  
  // Measure resource loading performance
  const measureResourceTimings = () => {
    if (!process.client) return
    
    const resources = performance.getEntriesByType('resource') as PerformanceResourceTiming[]
    
    resourceTimings.value = resources.map(resource => ({
      name: resource.name,
      duration: resource.duration,
      size: resource.transferSize || 0,
      type: getResourceType(resource.name)
    }))
  }
  
  // Get resource type from URL
  const getResourceType = (url: string): string => {
    if (url.match(/\.(js|mjs)$/)) return 'script'
    if (url.match(/\.css$/)) return 'stylesheet'
    if (url.match(/\.(jpg|jpeg|png|gif|webp|avif|svg)$/)) return 'image'
    if (url.match(/\.(woff|woff2|ttf|otf)$/)) return 'font'
    if (url.match(/\.(mp4|webm|ogg)$/)) return 'video'
    return 'other'
  }
  
  // Preload critical resources
  const preloadCriticalResources = (resources: Array<{ href: string; as: string; type?: string }>) => {
    if (!process.client) return
    
    resources.forEach(resource => {
      const link = document.createElement('link')
      link.rel = 'preload'
      link.href = resource.href
      link.as = resource.as
      if (resource.type) link.type = resource.type
      document.head.appendChild(link)
    })
  }
  
  // Prefetch next page resources
  const prefetchResources = (urls: string[]) => {
    if (!process.client) return
    
    urls.forEach(url => {
      const link = document.createElement('link')
      link.rel = 'prefetch'
      link.href = url
      document.head.appendChild(link)
    })
  }
  
  // Lazy load images with Intersection Observer
  const lazyLoadImages = () => {
    if (!process.client || !('IntersectionObserver' in window)) return
    
    const images = document.querySelectorAll('img[data-src]')
    
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target as HTMLImageElement
          img.src = img.dataset.src!
          img.removeAttribute('data-src')
          imageObserver.unobserve(img)
        }
      })
    }, {
      rootMargin: '50px'
    })
    
    images.forEach(img => imageObserver.observe(img))
  }
  
  // Optimize font loading
  const optimizeFontLoading = (fonts: Array<{ family: string; weight?: string; style?: string }>) => {
    if (!process.client) return
    
    fonts.forEach(font => {
      const link = document.createElement('link')
      link.rel = 'preload'
      link.as = 'font'
      link.type = 'font/woff2'
      link.crossOrigin = 'anonymous'
      
      const fontWeight = font.weight || '400'
      const fontStyle = font.style || 'normal'
      link.href = `/fonts/${font.family}-${fontWeight}-${fontStyle}.woff2`
      
      document.head.appendChild(link)
    })
  }
  
  // Implement service worker for caching
  const registerServiceWorker = async () => {
    if (!process.client || !('serviceWorker' in navigator)) return
    
    try {
      const registration = await navigator.serviceWorker.register('/sw.js')
      console.log('Service Worker registered:', registration)
      
      // Update service worker when new version is available
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing
        if (newWorker) {
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // New version available, prompt user to refresh
              console.log('New version available, please refresh the page')
            }
          })
        }
      })
    } catch (error) {
      console.error('Service Worker registration failed:', error)
    }
  }
  
  // Monitor memory usage
  const monitorMemoryUsage = () => {
    if (!process.client || !('memory' in performance)) return
    
    const memory = (performance as any).memory
    
    return {
      usedJSHeapSize: memory.usedJSHeapSize,
      totalJSHeapSize: memory.totalJSHeapSize,
      jsHeapSizeLimit: memory.jsHeapSizeLimit,
      usagePercentage: (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100
    }
  }
  
  // Debounce function for performance
  const debounce = <T extends (...args: any[]) => any>(
    func: T,
    wait: number
  ): ((...args: Parameters<T>) => void) => {
    let timeout: NodeJS.Timeout
    
    return (...args: Parameters<T>) => {
      clearTimeout(timeout)
      timeout = setTimeout(() => func.apply(null, args), wait)
    }
  }
  
  // Throttle function for performance
  const throttle = <T extends (...args: any[]) => any>(
    func: T,
    limit: number
  ): ((...args: Parameters<T>) => void) => {
    let inThrottle: boolean
    
    return (...args: Parameters<T>) => {
      if (!inThrottle) {
        func.apply(null, args)
        inThrottle = true
        setTimeout(() => inThrottle = false, limit)
      }
    }
  }
  
  // Bundle analyzer (development only)
  const analyzeBundleSize = () => {
    if (process.dev && process.client) {
      const scripts = Array.from(document.querySelectorAll('script[src]'))
      const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
      
      console.group('Bundle Analysis')
      console.log('Scripts:', scripts.length)
      console.log('Stylesheets:', styles.length)
      console.log('Total Resources:', resourceTimings.value.length)
      console.groupEnd()
    }
  }
  
  // Send performance metrics to analytics
  const sendPerformanceMetrics = () => {
    const { trackEvent } = useAnalytics()
    
    if (Object.keys(metrics.value).length > 0) {
      trackEvent('performance_metrics', {
        fcp: metrics.value.fcp,
        lcp: metrics.value.lcp,
        fid: metrics.value.fid,
        cls: metrics.value.cls,
        ttfb: metrics.value.ttfb,
        page_path: useRoute().path
      })
    }
  }
  
  // Initialize performance monitoring
  const initializePerformanceMonitoring = () => {
    if (!process.client) return
    
    // Measure Core Web Vitals
    measureCoreWebVitals()
    
    // Measure resource timings after page load
    window.addEventListener('load', () => {
      setTimeout(() => {
        measureResourceTimings()
        analyzeBundleSize()
        sendPerformanceMetrics()
      }, 1000)
    })
    
    // Register service worker
    registerServiceWorker()
    
    // Lazy load images
    lazyLoadImages()
  }
  
  return {
    metrics: readonly(metrics),
    resourceTimings: readonly(resourceTimings),
    measureCoreWebVitals,
    measureResourceTimings,
    preloadCriticalResources,
    prefetchResources,
    lazyLoadImages,
    optimizeFontLoading,
    registerServiceWorker,
    monitorMemoryUsage,
    debounce,
    throttle,
    analyzeBundleSize,
    sendPerformanceMetrics,
    initializePerformanceMonitoring
  }
}