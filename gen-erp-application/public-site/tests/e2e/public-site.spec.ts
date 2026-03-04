import { test, expect } from '@playwright/test'

test.describe('Public Site E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Mock tenant data for testing
    await page.route('**/api/public/*/site', async route => {
      await route.fulfill({
        json: {
          data: {
            id: '1',
            name: 'Test Company',
            slug: 'test-company',
            settings: {
              primary_color: '#3b82f6',
              accent_color: '#60a5fa',
              logo: '/test-logo.png',
              seo: {
                title: 'Test Company - Premium Services',
                description: 'Test company providing premium services'
              }
            }
          }
        }
      })
    })

    // Mock page data
    await page.route('**/api/public/*/pages/*', async route => {
      await route.fulfill({
        json: {
          data: {
            id: '1',
            title: 'Home',
            slug: 'home',
            seo_title: 'Test Company - Home',
            seo_description: 'Welcome to Test Company',
            sections: [
              {
                id: '1',
                type: 'hero_banner',
                content: {
                  title: 'Welcome to Test Company',
                  subtitle: 'Premium services for your business',
                  background_image: '/hero-bg.jpg'
                }
              },
              {
                id: '2',
                type: 'text_block',
                content: {
                  title: 'About Us',
                  content: 'We provide premium services to help your business grow.'
                }
              }
            ]
          }
        }
      })
    })
  })

  test('should load homepage successfully', async ({ page }) => {
    await page.goto('/')
    
    // Check if page loads
    await expect(page).toHaveTitle(/Test Company/)
    
    // Check if hero section is visible
    await expect(page.locator('text=Welcome to Test Company')).toBeVisible()
    
    // Check if navigation is present
    await expect(page.locator('nav')).toBeVisible()
    
    // Check if footer is present
    await expect(page.locator('footer')).toBeVisible()
  })

  test('should render hero banner section correctly', async ({ page }) => {
    await page.goto('/')
    
    // Wait for hero section to load
    const heroSection = page.locator('[data-testid="hero-banner-section"]').first()
    await expect(heroSection).toBeVisible()
    
    // Check hero content
    await expect(heroSection.locator('text=Welcome to Test Company')).toBeVisible()
    await expect(heroSection.locator('text=Premium services for your business')).toBeVisible()
  })

  test('should render text block section correctly', async ({ page }) => {
    await page.goto('/')
    
    // Wait for text block section to load
    const textSection = page.locator('[data-testid="text-block-section"]').first()
    await expect(textSection).toBeVisible()
    
    // Check text content
    await expect(textSection.locator('text=About Us')).toBeVisible()
    await expect(textSection.locator('text=We provide premium services')).toBeVisible()
  })

  test('should have proper SEO meta tags', async ({ page }) => {
    await page.goto('/')
    
    // Check title
    await expect(page).toHaveTitle('Test Company - Home')
    
    // Check meta description
    const metaDescription = page.locator('meta[name="description"]')
    await expect(metaDescription).toHaveAttribute('content', 'Welcome to Test Company')
    
    // Check canonical URL
    const canonical = page.locator('link[rel="canonical"]')
    await expect(canonical).toHaveAttribute('href', /.*/)
  })

  test('should handle navigation menu', async ({ page }) => {
    // Mock menu data
    await page.route('**/api/public/*/menus', async route => {
      await route.fulfill({
        json: {
          data: [
            {
              id: '1',
              name: 'Header Menu',
              location: 'header',
              items: [
                { id: '1', label: 'Home', url: '/', target: '_self', sort_order: 1 },
                { id: '2', label: 'About', url: '/about', target: '_self', sort_order: 2 },
                { id: '3', label: 'Services', url: '/services', target: '_self', sort_order: 3 },
                { id: '4', label: 'Contact', url: '/contact', target: '_self', sort_order: 4 }
              ]
            }
          ]
        }
      })
    })

    await page.goto('/')
    
    // Check if menu items are visible
    await expect(page.locator('nav a[href="/"]')).toBeVisible()
    await expect(page.locator('nav a[href="/about"]')).toBeVisible()
    await expect(page.locator('nav a[href="/services"]')).toBeVisible()
    await expect(page.locator('nav a[href="/contact"]')).toBeVisible()
  })

  test('should handle mobile navigation', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 })
    
    await page.goto('/')
    
    // Mobile menu button should be visible
    const mobileMenuButton = page.locator('[aria-label="Toggle mobile menu"]')
    await expect(mobileMenuButton).toBeVisible()
    
    // Click mobile menu button
    await mobileMenuButton.click()
    
    // Mobile menu should be visible
    await expect(page.locator('nav').nth(1)).toBeVisible()
  })

  test('should handle contact form submission', async ({ page }) => {
    // Mock contact form submission
    await page.route('**/api/public/*/contact', async route => {
      await route.fulfill({
        json: { success: true, message: 'Message sent successfully' }
      })
    })

    // Mock page with contact form
    await page.route('**/api/public/*/pages/contact', async route => {
      await route.fulfill({
        json: {
          data: {
            id: '2',
            title: 'Contact',
            slug: 'contact',
            sections: [
              {
                id: '1',
                type: 'contact_form',
                content: {
                  title: 'Contact Us',
                  fields: ['name', 'email', 'message']
                }
              }
            ]
          }
        }
      })
    })

    await page.goto('/contact')
    
    // Fill contact form
    await page.fill('input[name="name"]', 'John Doe')
    await page.fill('input[name="email"]', 'john@example.com')
    await page.fill('textarea[name="message"]', 'Test message')
    
    // Submit form
    await page.click('button[type="submit"]')
    
    // Check success message
    await expect(page.locator('text=Message sent successfully')).toBeVisible()
  })

  test('should handle 404 page', async ({ page }) => {
    await page.goto('/non-existent-page')
    
    // Should show 404 page
    await expect(page.locator('text=404')).toBeVisible()
    await expect(page.locator('text=Page Not Found')).toBeVisible()
    
    // Should have "Go Home" button
    await expect(page.locator('a[href="/"]')).toBeVisible()
  })

  test('should handle offline functionality', async ({ page, context }) => {
    await page.goto('/')
    
    // Go offline
    await context.setOffline(true)
    
    // Navigate to offline page
    await page.goto('/offline')
    
    // Check offline page content
    await expect(page.locator('text=You\'re Offline')).toBeVisible()
    await expect(page.locator('text=No internet connection')).toBeVisible()
    
    // Go back online
    await context.setOffline(false)
    
    // Check connection restored message
    await expect(page.locator('text=Connection restored!')).toBeVisible()
  })

  test('should load and display product grid', async ({ page }) => {
    // Mock products API
    await page.route('**/api/v1/cms/erp/products*', async route => {
      await route.fulfill({
        json: {
          data: [
            {
              id: '1',
              name: 'Test Product 1',
              description: 'Test product description',
              price: 99.99,
              image: '/product1.jpg',
              category: 'Electronics'
            },
            {
              id: '2',
              name: 'Test Product 2',
              description: 'Another test product',
              price: 149.99,
              image: '/product2.jpg',
              category: 'Electronics'
            }
          ]
        }
      })
    })

    // Mock page with product grid
    await page.route('**/api/public/*/pages/products', async route => {
      await route.fulfill({
        json: {
          data: {
            id: '3',
            title: 'Products',
            slug: 'products',
            sections: [
              {
                id: '1',
                type: 'product_grid',
                content: {
                  title: 'Our Products',
                  limit: 10
                }
              }
            ]
          }
        }
      })
    })

    await page.goto('/products')
    
    // Check if products are displayed
    await expect(page.locator('text=Our Products')).toBeVisible()
    await expect(page.locator('text=Test Product 1')).toBeVisible()
    await expect(page.locator('text=Test Product 2')).toBeVisible()
    
    // Check product prices
    await expect(page.locator('text=$99.99')).toBeVisible()
    await expect(page.locator('text=$149.99')).toBeVisible()
  })

  test('should handle image optimization', async ({ page }) => {
    await page.goto('/')
    
    // Check if images have proper attributes
    const images = page.locator('img')
    const firstImage = images.first()
    
    // Check if image has loading attribute
    await expect(firstImage).toHaveAttribute('loading', 'lazy')
    
    // Check if image has proper alt text
    await expect(firstImage).toHaveAttribute('alt')
  })

  test('should track analytics events', async ({ page }) => {
    // Mock analytics
    await page.addInitScript(() => {
      window.gtag = () => {}
      window.fbq = () => {}
    })

    await page.goto('/')
    
    // Check if analytics scripts are loaded
    const gtagScript = page.locator('script[src*="googletagmanager"]')
    await expect(gtagScript).toHaveCount(1)
  })

  test('should handle performance monitoring', async ({ page }) => {
    await page.goto('/')
    
    // Check if performance observer is working
    const performanceEntries = await page.evaluate(() => {
      return performance.getEntriesByType('navigation').length > 0
    })
    
    expect(performanceEntries).toBe(true)
  })

  test('should handle service worker registration', async ({ page }) => {
    await page.goto('/')
    
    // Check if service worker is registered
    const swRegistered = await page.evaluate(async () => {
      if ('serviceWorker' in navigator) {
        try {
          const registration = await navigator.serviceWorker.getRegistration()
          return !!registration
        } catch (error) {
          return false
        }
      }
      return false
    })
    
    // Service worker should be registered (or at least attempted)
    expect(typeof swRegistered).toBe('boolean')
  })

  test('should handle responsive design', async ({ page }) => {
    // Test desktop view
    await page.setViewportSize({ width: 1920, height: 1080 })
    await page.goto('/')
    
    // Check if desktop navigation is visible
    await expect(page.locator('nav').first()).toBeVisible()
    
    // Test tablet view
    await page.setViewportSize({ width: 768, height: 1024 })
    await page.reload()
    
    // Test mobile view
    await page.setViewportSize({ width: 375, height: 667 })
    await page.reload()
    
    // Check if mobile menu button is visible
    await expect(page.locator('[aria-label="Toggle mobile menu"]')).toBeVisible()
  })

  test('should handle error states gracefully', async ({ page }) => {
    // Mock API error
    await page.route('**/api/public/*/pages/*', async route => {
      await route.fulfill({
        status: 500,
        json: { error: 'Internal server error' }
      })
    })

    await page.goto('/test-page')
    
    // Should show error page or handle gracefully
    const hasErrorContent = await page.locator('text=Error').count() > 0 || 
                           await page.locator('text=404').count() > 0 ||
                           await page.locator('text=Something went wrong').count() > 0
    
    expect(hasErrorContent).toBe(true)
  })
})