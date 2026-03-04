interface AnalyticsEvent {
  name: string
  parameters?: Record<string, any>
}

interface EcommerceItem {
  item_id: string
  item_name: string
  category: string
  quantity: number
  price: number
}

interface PurchaseData {
  transaction_id: string
  value: number
  currency: string
  items: EcommerceItem[]
}

export const useAnalytics = () => {
  const { $analytics } = useNuxtApp()
  const route = useRoute()
  
  // Track page views automatically
  const trackPageView = (path?: string, title?: string) => {
    const pagePath = path || route.fullPath
    const pageTitle = title || document.title
    
    $analytics?.trackPageView(pagePath, pageTitle)
  }
  
  // Track custom events
  const trackEvent = (eventName: string, parameters: Record<string, any> = {}) => {
    $analytics?.trackEvent(eventName, parameters)
  }
  
  // Track button clicks
  const trackButtonClick = (buttonName: string, location?: string) => {
    trackEvent('button_click', {
      button_name: buttonName,
      location: location || route.path
    })
  }
  
  // Track link clicks
  const trackLinkClick = (linkUrl: string, linkText?: string, isExternal: boolean = false) => {
    if (isExternal) {
      $analytics?.trackOutboundLink(linkUrl, linkText)
    } else {
      trackEvent('internal_link_click', {
        link_url: linkUrl,
        link_text: linkText,
        source_page: route.path
      })
    }
  }
  
  // Track form interactions
  const trackFormStart = (formName: string) => {
    trackEvent('form_start', {
      form_name: formName,
      page_path: route.path
    })
  }
  
  const trackFormSubmit = (formName: string, formData: Record<string, any> = {}) => {
    $analytics?.trackFormSubmit(formName, {
      ...formData,
      page_path: route.path
    })
  }
  
  const trackFormError = (formName: string, errorField: string, errorMessage: string) => {
    trackEvent('form_error', {
      form_name: formName,
      error_field: errorField,
      error_message: errorMessage,
      page_path: route.path
    })
  }
  
  // Track search
  const trackSearch = (searchTerm: string, resultsCount?: number, searchType?: string) => {
    $analytics?.trackSearch(searchTerm, resultsCount)
    
    trackEvent('search', {
      search_term: searchTerm,
      results_count: resultsCount,
      search_type: searchType || 'site_search',
      page_path: route.path
    })
  }
  
  // Track ecommerce events
  const trackViewItem = (item: EcommerceItem) => {
    trackEvent('view_item', {
      currency: 'USD',
      value: item.price,
      items: [item]
    })
  }
  
  const trackAddToCart = (item: EcommerceItem) => {
    trackEvent('add_to_cart', {
      currency: 'USD',
      value: item.price * item.quantity,
      items: [item]
    })
  }
  
  const trackRemoveFromCart = (item: EcommerceItem) => {
    trackEvent('remove_from_cart', {
      currency: 'USD',
      value: item.price * item.quantity,
      items: [item]
    })
  }
  
  const trackBeginCheckout = (items: EcommerceItem[], value: number) => {
    trackEvent('begin_checkout', {
      currency: 'USD',
      value: value,
      items: items
    })
  }
  
  const trackPurchase = (purchaseData: PurchaseData) => {
    $analytics?.trackPurchase(
      purchaseData.transaction_id,
      purchaseData.value,
      purchaseData.currency,
      purchaseData.items
    )
  }
  
  // Track content engagement
  const trackScrollDepth = (percentage: number) => {
    trackEvent('scroll', {
      percent_scrolled: percentage,
      page_path: route.path
    })
  }
  
  const trackTimeOnPage = (seconds: number) => {
    trackEvent('page_engagement', {
      engagement_time_msec: seconds * 1000,
      page_path: route.path
    })
  }
  
  // Track file downloads
  const trackDownload = (fileName: string, fileUrl: string, fileType?: string) => {
    $analytics?.trackDownload(fileName, fileUrl)
    
    trackEvent('file_download', {
      file_name: fileName,
      file_url: fileUrl,
      file_type: fileType,
      page_path: route.path
    })
  }
  
  // Track video interactions
  const trackVideoPlay = (videoTitle: string, videoDuration?: number) => {
    $analytics?.trackVideo('play', videoTitle)
    
    trackEvent('video_start', {
      video_title: videoTitle,
      video_duration: videoDuration,
      page_path: route.path
    })
  }
  
  const trackVideoPause = (videoTitle: string, progress: number) => {
    $analytics?.trackVideo('pause', videoTitle, progress)
  }
  
  const trackVideoComplete = (videoTitle: string) => {
    $analytics?.trackVideo('complete', videoTitle, 100)
  }
  
  // Track social sharing
  const trackSocialShare = (platform: string, contentType: string, contentId?: string) => {
    trackEvent('share', {
      method: platform,
      content_type: contentType,
      content_id: contentId,
      page_path: route.path
    })
  }
  
  // Track newsletter signup
  const trackNewsletterSignup = (email?: string, source?: string) => {
    trackEvent('newsletter_signup', {
      source: source || route.path,
      method: 'email'
    })
    
    // Also track as form submission
    trackFormSubmit('newsletter', {
      source: source || route.path
    })
  }
  
  // Track contact form
  const trackContactForm = (formData: Record<string, any>) => {
    trackFormSubmit('contact', formData)
    
    trackEvent('generate_lead', {
      currency: 'USD',
      value: 1, // Assign a value to leads
      source: route.path
    })
  }
  
  // Utility to track element visibility
  const trackElementVisibility = (
    elementRef: Ref<HTMLElement | null>,
    eventName: string,
    parameters: Record<string, any> = {}
  ) => {
    const observer = ref<IntersectionObserver | null>(null)
    
    onMounted(() => {
      if (!elementRef.value) return
      
      observer.value = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              trackEvent(eventName, {
                ...parameters,
                page_path: route.path,
                element_visible: true
              })
              observer.value?.unobserve(entry.target)
            }
          })
        },
        { threshold: 0.5 }
      )
      
      observer.value.observe(elementRef.value)
    })
    
    onUnmounted(() => {
      observer.value?.disconnect()
    })
  }
  
  // Auto-track scroll depth
  const useScrollTracking = () => {
    const scrollDepths = [25, 50, 75, 90, 100]
    const trackedDepths = new Set<number>()
    
    const handleScroll = throttle(() => {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop
      const documentHeight = document.documentElement.scrollHeight - window.innerHeight
      const scrollPercent = Math.round((scrollTop / documentHeight) * 100)
      
      scrollDepths.forEach(depth => {
        if (scrollPercent >= depth && !trackedDepths.has(depth)) {
          trackedDepths.add(depth)
          trackScrollDepth(depth)
        }
      })
    }, 1000)
    
    onMounted(() => {
      window.addEventListener('scroll', handleScroll, { passive: true })
    })
    
    onUnmounted(() => {
      window.removeEventListener('scroll', handleScroll)
    })
  }
  
  // Throttle utility
  const throttle = (func: Function, limit: number) => {
    let inThrottle: boolean
    return function(this: any, ...args: any[]) {
      if (!inThrottle) {
        func.apply(this, args)
        inThrottle = true
        setTimeout(() => inThrottle = false, limit)
      }
    }
  }
  
  return {
    trackPageView,
    trackEvent,
    trackButtonClick,
    trackLinkClick,
    trackFormStart,
    trackFormSubmit,
    trackFormError,
    trackSearch,
    trackViewItem,
    trackAddToCart,
    trackRemoveFromCart,
    trackBeginCheckout,
    trackPurchase,
    trackScrollDepth,
    trackTimeOnPage,
    trackDownload,
    trackVideoPlay,
    trackVideoPause,
    trackVideoComplete,
    trackSocialShare,
    trackNewsletterSignup,
    trackContactForm,
    trackElementVisibility,
    useScrollTracking
  }
}