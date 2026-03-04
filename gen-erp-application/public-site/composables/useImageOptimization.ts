interface ImageOptions {
  width?: number
  height?: number
  quality?: number
  format?: string | string[]
  fit?: 'cover' | 'contain' | 'fill' | 'inside' | 'outside'
  position?: string
  background?: string
}

interface ResponsiveImageSizes {
  mobile: string
  tablet: string
  desktop: string
  sizes: string
}

export const useImageOptimization = () => {
  // Generate responsive image sizes
  const generateResponsiveSizes = (
    baseSrc: string,
    options: ImageOptions = {}
  ): ResponsiveImageSizes => {
    const { $img } = useNuxtApp()
    
    const defaultOptions = {
      quality: 80,
      format: 'webp',
      fit: 'cover',
      ...options
    }
    
    return {
      mobile: $img(baseSrc, { 
        ...defaultOptions, 
        width: 640,
        height: options.height ? Math.round(options.height * 0.5) : undefined
      }),
      tablet: $img(baseSrc, { 
        ...defaultOptions, 
        width: 1024,
        height: options.height ? Math.round(options.height * 0.75) : undefined
      }),
      desktop: $img(baseSrc, { 
        ...defaultOptions, 
        width: 1920,
        height: options.height
      }),
      sizes: '(max-width: 640px) 640px, (max-width: 1024px) 1024px, 1920px'
    }
  }

  // Generate optimized image URL
  const getOptimizedImageUrl = (
    src: string,
    options: ImageOptions = {}
  ): string => {
    const { $img } = useNuxtApp()
    
    const defaultOptions = {
      quality: 80,
      format: 'webp',
      fit: 'cover',
      ...options
    }
    
    return $img(src, defaultOptions)
  }

  // Generate image srcset for different densities
  const generateSrcSet = (
    src: string,
    options: ImageOptions = {}
  ): string => {
    const { $img } = useNuxtApp()
    
    const defaultOptions = {
      quality: 80,
      format: 'webp',
      fit: 'cover',
      ...options
    }
    
    const densities = [1, 1.5, 2, 3]
    
    return densities
      .map(density => {
        const scaledOptions = {
          ...defaultOptions,
          width: options.width ? Math.round(options.width * density) : undefined,
          height: options.height ? Math.round(options.height * density) : undefined
        }
        
        const url = $img(src, scaledOptions)
        return `${url} ${density}x`
      })
      .join(', ')
  }

  // Get image dimensions from URL or calculate aspect ratio
  const getImageDimensions = async (src: string): Promise<{ width: number; height: number } | null> => {
    return new Promise((resolve) => {
      const img = new Image()
      
      img.onload = () => {
        resolve({
          width: img.naturalWidth,
          height: img.naturalHeight
        })
      }
      
      img.onerror = () => {
        resolve(null)
      }
      
      img.src = src
    })
  }

  // Calculate aspect ratio
  const calculateAspectRatio = (width: number, height: number): string => {
    const gcd = (a: number, b: number): number => {
      return b === 0 ? a : gcd(b, a % b)
    }
    
    const divisor = gcd(width, height)
    const aspectWidth = width / divisor
    const aspectHeight = height / divisor
    
    return `${aspectWidth}/${aspectHeight}`
  }

  // Generate placeholder image (blur or solid color)
  const generatePlaceholder = (
    src: string,
    type: 'blur' | 'color' = 'blur',
    color: string = '#f3f4f6'
  ): string => {
    const { $img } = useNuxtApp()
    
    if (type === 'blur') {
      return $img(src, {
        width: 20,
        height: 20,
        quality: 10,
        blur: 5,
        format: 'webp'
      })
    }
    
    // Generate solid color placeholder
    return `data:image/svg+xml;base64,${btoa(
      `<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="${color}"/>
      </svg>`
    )}`
  }

  // Preload critical images
  const preloadImage = (src: string, options: ImageOptions = {}): void => {
    const link = document.createElement('link')
    link.rel = 'preload'
    link.as = 'image'
    link.href = getOptimizedImageUrl(src, options)
    
    // Add responsive preload hints
    if (options.width) {
      link.setAttribute('imagesizes', `${options.width}px`)
    }
    
    document.head.appendChild(link)
  }

  // Lazy load images with Intersection Observer
  const useLazyLoading = (
    elementRef: Ref<HTMLElement | null>,
    callback: () => void,
    options: IntersectionObserverInit = {}
  ) => {
    const defaultOptions = {
      rootMargin: '50px',
      threshold: 0.1,
      ...options
    }

    const observer = ref<IntersectionObserver | null>(null)

    onMounted(() => {
      if (!elementRef.value) return

      observer.value = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            callback()
            observer.value?.unobserve(entry.target)
          }
        })
      }, defaultOptions)

      observer.value.observe(elementRef.value)
    })

    onUnmounted(() => {
      observer.value?.disconnect()
    })

    return observer
  }

  // Get optimal image format based on browser support
  const getOptimalFormat = (): string[] => {
    if (process.client) {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')
      
      // Check WebP support
      const supportsWebP = canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0
      
      // Check AVIF support (modern browsers)
      const supportsAVIF = canvas.toDataURL('image/avif').indexOf('data:image/avif') === 0
      
      if (supportsAVIF) return ['avif', 'webp', 'jpg']
      if (supportsWebP) return ['webp', 'jpg']
      return ['jpg']
    }
    
    // Server-side fallback
    return ['webp', 'jpg']
  }

  // Image performance monitoring
  const trackImagePerformance = (src: string, startTime: number) => {
    const loadTime = performance.now() - startTime
    
    // Log performance metrics (can be sent to analytics)
    console.log(`Image loaded: ${src} in ${loadTime.toFixed(2)}ms`)
    
    // Track Core Web Vitals - Largest Contentful Paint (LCP)
    if ('PerformanceObserver' in window) {
      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          if (entry.element && entry.element.src === src) {
            console.log(`LCP candidate: ${src} at ${entry.startTime.toFixed(2)}ms`)
          }
        })
      })
      
      observer.observe({ entryTypes: ['largest-contentful-paint'] })
    }
  }

  return {
    generateResponsiveSizes,
    getOptimizedImageUrl,
    generateSrcSet,
    getImageDimensions,
    calculateAspectRatio,
    generatePlaceholder,
    preloadImage,
    useLazyLoading,
    getOptimalFormat,
    trackImagePerformance
  }
}