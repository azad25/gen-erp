export default defineNuxtPlugin(() => {
  const { tenant } = useTenant()
  const config = useRuntimeConfig()
  
  // Initialize Google Analytics
  const initGoogleAnalytics = (measurementId: string) => {
    // Load gtag script
    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`
    document.head.appendChild(script)
    
    // Initialize gtag
    window.dataLayer = window.dataLayer || []
    window.gtag = function() {
      window.dataLayer.push(arguments)
    }
    
    window.gtag('js', new Date())
    window.gtag('config', measurementId, {
      // Enhanced ecommerce and privacy settings
      send_page_view: true,
      anonymize_ip: true,
      allow_google_signals: false,
      allow_ad_personalization_signals: false,
      // Custom parameters
      custom_map: {
        custom_parameter_1: 'tenant_id',
        custom_parameter_2: 'tenant_slug'
      }
    })
    
    // Set tenant-specific custom dimensions
    if (tenant.value) {
      window.gtag('config', measurementId, {
        custom_parameter_1: tenant.value.id,
        custom_parameter_2: tenant.value.slug
      })
    }
  }
  
  // Initialize Facebook Pixel
  const initFacebookPixel = (pixelId: string) => {
    // Load Facebook Pixel script
    const script = document.createElement('script')
    script.innerHTML = `
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '${pixelId}');
      fbq('track', 'PageView');
    `
    document.head.appendChild(script)
    
    // Add noscript fallback
    const noscript = document.createElement('noscript')
    noscript.innerHTML = `
      <img height="1" width="1" style="display:none"
           src="https://www.facebook.com/tr?id=${pixelId}&ev=PageView&noscript=1"/>
    `
    document.body.appendChild(noscript)
  }
  
  // Initialize analytics when tenant is available
  watchEffect(() => {
    if (tenant.value?.settings?.analytics) {
      const analytics = tenant.value.settings.analytics
      
      // Google Analytics
      if (analytics.google_analytics_id) {
        initGoogleAnalytics(analytics.google_analytics_id)
      }
      
      // Facebook Pixel
      if (analytics.facebook_pixel_id) {
        initFacebookPixel(analytics.facebook_pixel_id)
      }
    }
  })
  
  // Provide analytics utilities
  return {
    provide: {
      analytics: {
        // Track page view
        trackPageView: (path: string, title?: string) => {
          if (window.gtag) {
            window.gtag('config', tenant.value?.settings?.analytics?.google_analytics_id, {
              page_path: path,
              page_title: title
            })
          }
          
          if (window.fbq) {
            window.fbq('track', 'PageView')
          }
        },
        
        // Track custom event
        trackEvent: (eventName: string, parameters: Record<string, any> = {}) => {
          if (window.gtag) {
            window.gtag('event', eventName, {
              ...parameters,
              tenant_id: tenant.value?.id,
              tenant_slug: tenant.value?.slug
            })
          }
          
          if (window.fbq) {
            window.fbq('track', eventName, parameters)
          }
        },
        
        // Track ecommerce events
        trackPurchase: (transactionId: string, value: number, currency: string = 'USD', items: any[] = []) => {
          if (window.gtag) {
            window.gtag('event', 'purchase', {
              transaction_id: transactionId,
              value: value,
              currency: currency,
              items: items
            })
          }
          
          if (window.fbq) {
            window.fbq('track', 'Purchase', {
              value: value,
              currency: currency
            })
          }
        },
        
        // Track form submissions
        trackFormSubmit: (formName: string, formData: Record<string, any> = {}) => {
          if (window.gtag) {
            window.gtag('event', 'form_submit', {
              form_name: formName,
              ...formData
            })
          }
          
          if (window.fbq) {
            window.fbq('track', 'Lead', {
              content_name: formName
            })
          }
        },
        
        // Track file downloads
        trackDownload: (fileName: string, fileUrl: string) => {
          if (window.gtag) {
            window.gtag('event', 'file_download', {
              file_name: fileName,
              file_url: fileUrl
            })
          }
        },
        
        // Track outbound links
        trackOutboundLink: (url: string, linkText?: string) => {
          if (window.gtag) {
            window.gtag('event', 'click', {
              event_category: 'outbound',
              event_label: url,
              transport_type: 'beacon'
            })
          }
        },
        
        // Track search
        trackSearch: (searchTerm: string, resultsCount?: number) => {
          if (window.gtag) {
            window.gtag('event', 'search', {
              search_term: searchTerm,
              results_count: resultsCount
            })
          }
        },
        
        // Track video interactions
        trackVideo: (action: 'play' | 'pause' | 'complete', videoTitle: string, progress?: number) => {
          if (window.gtag) {
            window.gtag('event', 'video_' + action, {
              video_title: videoTitle,
              video_progress: progress
            })
          }
        }
      }
    }
  }
})

// Extend global types
declare global {
  interface Window {
    dataLayer: any[]
    gtag: (...args: any[]) => void
    fbq: (...args: any[]) => void
  }
}