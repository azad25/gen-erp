// Service Worker for caching and offline functionality
const CACHE_NAME = 'cms-site-v1'
const STATIC_CACHE = 'static-v1'
const DYNAMIC_CACHE = 'dynamic-v1'

// Files to cache immediately
const STATIC_FILES = [
  '/',
  '/offline.html',
  '/_nuxt/entry.css',
  '/_nuxt/entry.js'
]

// Install event - cache static files
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...')
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('Caching static files')
        return cache.addAll(STATIC_FILES)
      })
      .catch((error) => {
        console.error('Failed to cache static files:', error)
      })
  )
  
  // Skip waiting to activate immediately
  self.skipWaiting()
})

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...')
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Deleting old cache:', cacheName)
              return caches.delete(cacheName)
            }
          })
        )
      })
  )
  
  // Take control of all clients immediately
  self.clients.claim()
})

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)
  
  // Skip non-GET requests
  if (request.method !== 'GET') return
  
  // Skip external requests
  if (url.origin !== self.location.origin) return
  
  // Handle API requests differently
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirst(request))
    return
  }
  
  // Handle static assets
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(request))
    return
  }
  
  // Handle page requests
  event.respondWith(staleWhileRevalidate(request))
})

// Cache first strategy (for static assets)
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request)
    if (cachedResponse) {
      return cachedResponse
    }
    
    const networkResponse = await fetch(request)
    
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(STATIC_CACHE)
      cache.put(request, networkResponse.clone())
    }
    
    return networkResponse
  } catch (error) {
    console.error('Cache first failed:', error)
    return new Response('Offline', { status: 503 })
  }
}

// Network first strategy (for API requests)
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request)
    
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE)
      cache.put(request, networkResponse.clone())
    }
    
    return networkResponse
  } catch (error) {
    console.error('Network first failed, trying cache:', error)
    
    const cachedResponse = await caches.match(request)
    if (cachedResponse) {
      return cachedResponse
    }
    
    return new Response('Offline', { status: 503 })
  }
}

// Stale while revalidate strategy (for pages)
async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE)
  const cachedResponse = await cache.match(request)
  
  // Fetch from network in background
  const networkResponsePromise = fetch(request)
    .then((networkResponse) => {
      if (networkResponse.ok) {
        cache.put(request, networkResponse.clone())
      }
      return networkResponse
    })
    .catch((error) => {
      console.error('Network request failed:', error)
      return null
    })
  
  // Return cached response immediately if available
  if (cachedResponse) {
    return cachedResponse
  }
  
  // Otherwise wait for network response
  const networkResponse = await networkResponsePromise
  if (networkResponse) {
    return networkResponse
  }
  
  // Fallback to offline page
  return caches.match('/offline.html') || new Response('Offline', { status: 503 })
}

// Check if URL is a static asset
function isStaticAsset(pathname) {
  const staticExtensions = ['.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.woff', '.woff2', '.ttf', '.otf']
  return staticExtensions.some(ext => pathname.endsWith(ext)) || pathname.startsWith('/_nuxt/')
}

// Handle background sync (for form submissions when offline)
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync())
  }
})

async function doBackgroundSync() {
  // Handle queued form submissions or other background tasks
  console.log('Performing background sync...')
  
  // This would typically process queued requests from IndexedDB
  // For now, just log that sync occurred
}

// Handle push notifications (if needed)
self.addEventListener('push', (event) => {
  if (!event.data) return
  
  const data = event.data.json()
  const options = {
    body: data.body,
    icon: '/icon-192x192.png',
    badge: '/badge-72x72.png',
    tag: data.tag || 'default',
    data: data.data || {}
  }
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  )
})

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  
  const urlToOpen = event.notification.data?.url || '/'
  
  event.waitUntil(
    clients.matchAll({ type: 'window' })
      .then((clientList) => {
        // Check if there's already a window open
        for (const client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus()
          }
        }
        
        // Open new window
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen)
        }
      })
  )
})