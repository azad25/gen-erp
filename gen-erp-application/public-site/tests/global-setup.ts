import { chromium, FullConfig } from '@playwright/test'

async function globalSetup(config: FullConfig) {
  console.log('🚀 Starting global setup for E2E tests...')
  
  // Launch browser for setup
  const browser = await chromium.launch()
  const page = await browser.newPage()
  
  try {
    // Wait for the dev server to be ready
    console.log('⏳ Waiting for dev server to be ready...')
    await page.goto('http://localhost:3000', { waitUntil: 'networkidle' })
    console.log('✅ Dev server is ready')
    
    // Perform any global setup tasks here
    // For example, seed test data, authenticate, etc.
    
    // Check if service worker is available
    const swAvailable = await page.evaluate(() => {
      return 'serviceWorker' in navigator
    })
    
    if (swAvailable) {
      console.log('✅ Service Worker support detected')
    } else {
      console.log('⚠️  Service Worker not supported in test environment')
    }
    
    // Check if performance API is available
    const perfAvailable = await page.evaluate(() => {
      return 'performance' in window && 'PerformanceObserver' in window
    })
    
    if (perfAvailable) {
      console.log('✅ Performance API support detected')
    } else {
      console.log('⚠️  Performance API not fully supported in test environment')
    }
    
  } catch (error) {
    console.error('❌ Global setup failed:', error)
    throw error
  } finally {
    await browser.close()
  }
  
  console.log('✅ Global setup completed successfully')
}

export default globalSetup