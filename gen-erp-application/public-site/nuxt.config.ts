// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  
  // SSR Configuration
  ssr: true,
  nitro: {
    preset: 'node-server'
  },
  
  // CSS Framework
  css: ['~/assets/css/main.css'],
  
  // Modules
  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@nuxt/image',
    '@nuxtjs/seo'
  ],
  
  // Runtime Config
  runtimeConfig: {
    // Private keys (only available on server-side)
    apiSecret: process.env.API_SECRET,
    
    // Public keys (exposed to client-side)
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000'
    }
  },
  
  // App Configuration
  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1',
      title: 'CMS Site',
      meta: [
        { name: 'description', content: 'Dynamic CMS-powered website' }
      ]
    }
  },
  
  // Image Optimization
  image: {
    quality: 80,
    format: ['avif', 'webp', 'jpg', 'png'],
    screens: {
      xs: 320,
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1280,
      xxl: 1536
    },
    densities: [1, 2],
    presets: {
      avatar: {
        modifiers: {
          format: 'webp',
          width: 50,
          height: 50,
          quality: 80
        }
      },
      thumbnail: {
        modifiers: {
          format: 'webp',
          width: 300,
          height: 200,
          quality: 75,
          fit: 'cover'
        }
      },
      hero: {
        modifiers: {
          format: 'webp',
          width: 1920,
          height: 1080,
          quality: 85,
          fit: 'cover'
        }
      },
      gallery: {
        modifiers: {
          format: 'webp',
          width: 800,
          height: 600,
          quality: 80,
          fit: 'cover'
        }
      }
    },
    // Enable image optimization for external domains
    domains: [
      'images.unsplash.com',
      'via.placeholder.com',
      'picsum.photos'
    ],
    // Provider-specific options
    providers: {
      ipx: {
        modifiers: {
          quality: 80,
          format: 'webp'
        }
      }
    }
  },
  
  // SEO Configuration
  seo: {
    redirectToCanonicalSiteUrl: true
  },
  
  // Build Configuration
  build: {
    transpile: ['@headlessui/vue']
  }
})
