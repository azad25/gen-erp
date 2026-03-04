import { FullConfig } from '@playwright/test'

async function globalTeardown(config: FullConfig) {
  console.log('🧹 Starting global teardown for E2E tests...')
  
  try {
    // Perform any cleanup tasks here
    // For example, clean up test data, close connections, etc.
    
    // Clean up any test files or temporary data
    console.log('🗑️  Cleaning up test artifacts...')
    
    // Log test completion
    console.log('📊 Test run completed')
    
  } catch (error) {
    console.error('❌ Global teardown failed:', error)
    // Don't throw error in teardown to avoid masking test failures
  }
  
  console.log('✅ Global teardown completed')
}

export default globalTeardown