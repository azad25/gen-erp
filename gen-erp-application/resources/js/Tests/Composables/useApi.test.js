import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useApi } from '@/Composables/useApi'

// Mock fetch
global.fetch = vi.fn()

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      auth: {
        api_token: 'test-token'
      }
    }
  })
}))

describe('useApi', () => {
  let api

  beforeEach(() => {
    api = useApi()
    fetch.mockClear()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('GET requests', () => {
    it('makes successful GET request', async () => {
      const mockResponse = { data: { id: 1, name: 'Test' } }
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await api.get('/api/test')

      expect(fetch).toHaveBeenCalledWith('/api/test', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Authorization': 'Bearer test-token'
        }
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles GET request with query parameters', async () => {
      const mockResponse = { data: [] }
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      })

      await api.get('/api/test', { page: 1, limit: 10 })

      expect(fetch).toHaveBeenCalledWith('/api/test?page=1&limit=10', expect.any(Object))
    })

    it('handles GET request errors', async () => {
      fetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ message: 'Not found' })
      })

      await expect(api.get('/api/test')).rejects.toThrow('Not found')
      expect(api.error.value).toBe('Not found')
    })
  })

  describe('POST requests', () => {
    it('makes successful POST request', async () => {
      const mockResponse = { data: { id: 1, name: 'Created' } }
      const postData = { name: 'Test Item' }
      
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await api.post('/api/test', postData)

      expect(fetch).toHaveBeenCalledWith('/api/test', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Authorization': 'Bearer test-token'
        },
        body: JSON.stringify(postData)
      })
      expect(result).toEqual(mockResponse)
    })

    it('handles POST request validation errors', async () => {
      fetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () => Promise.resolve({
          message: 'Validation failed',
          errors: { name: ['Name is required'] }
        })
      })

      await expect(api.post('/api/test', {})).rejects.toThrow('Validation failed')
    })
  })

  describe('PUT requests', () => {
    it('makes successful PUT request', async () => {
      const mockResponse = { data: { id: 1, name: 'Updated' } }
      const putData = { name: 'Updated Item' }
      
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await api.put('/api/test/1', putData)

      expect(fetch).toHaveBeenCalledWith('/api/test/1', {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Authorization': 'Bearer test-token'
        },
        body: JSON.stringify(putData)
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('DELETE requests', () => {
    it('makes successful DELETE request', async () => {
      const mockResponse = { message: 'Deleted successfully' }
      
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await api.delete('/api/test/1')

      expect(fetch).toHaveBeenCalledWith('/api/test/1', {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Authorization': 'Bearer test-token'
        }
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('Loading state', () => {
    it('sets loading to true during request', async () => {
      fetch.mockImplementationOnce(() => 
        new Promise(resolve => setTimeout(() => resolve({
          ok: true,
          json: () => Promise.resolve({ data: 'test' })
        }), 100))
      )

      expect(api.loading.value).toBe(false)
      
      const promise = api.get('/api/test')
      expect(api.loading.value).toBe(true)
      
      await promise
      expect(api.loading.value).toBe(false)
    })

    it('sets loading to false after error', async () => {
      fetch.mockRejectedValueOnce(new Error('Network error'))

      expect(api.loading.value).toBe(false)
      
      try {
        await api.get('/api/test')
      } catch (error) {
        // Expected error
      }
      
      expect(api.loading.value).toBe(false)
    })
  })

  describe('Error handling', () => {
    it('handles network errors', async () => {
      fetch.mockRejectedValueOnce(new Error('Network error'))

      await expect(api.get('/api/test')).rejects.toThrow('Network error')
      expect(api.error.value).toBe('Network error')
    })

    it('handles JSON parsing errors', async () => {
      fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: () => Promise.reject(new Error('Invalid JSON'))
      })

      await expect(api.get('/api/test')).rejects.toThrow('HTTP 500')
      expect(api.error.value).toBe('HTTP 500')
    })

    it('clears error on successful request', async () => {
      // First request fails
      fetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: () => Promise.resolve({ message: 'Not found' })
      })

      try {
        await api.get('/api/test')
      } catch (error) {
        // Expected error
      }
      
      expect(api.error.value).toBe('Not found')

      // Second request succeeds
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: 'success' })
      })

      await api.get('/api/test')
      expect(api.error.value).toBe(null)
    })
  })

  describe('Authentication', () => {
    it('uses token from Inertia props', async () => {
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: 'test' })
      })

      await api.get('/api/test')

      expect(fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': 'Bearer test-token'
          })
        })
      )
    })

    it('falls back to meta tag when no Inertia token', async () => {
      // Mock document.querySelector
      const mockMetaTag = { content: 'meta-token' }
      vi.spyOn(document, 'querySelector').mockReturnValue(mockMetaTag)

      // Create new API instance without Inertia token
      vi.doMock('@inertiajs/vue3', () => ({
        usePage: () => ({
          props: {
            auth: {}
          }
        })
      }))

      const { useApi: useApiWithoutToken } = await import('@/Composables/useApi')
      const apiWithoutToken = useApiWithoutToken()

      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: 'test' })
      })

      await apiWithoutToken.get('/api/test')

      expect(fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': 'Bearer meta-token'
          })
        })
      )

      vi.restoreAllMocks()
    })

    it('works without authentication token', async () => {
      // Mock no token available
      vi.doMock('@inertiajs/vue3', () => ({
        usePage: () => ({
          props: {
            auth: {}
          }
        })
      }))

      vi.spyOn(document, 'querySelector').mockReturnValue(null)

      const { useApi: useApiWithoutAuth } = await import('@/Composables/useApi')
      const apiWithoutAuth = useApiWithoutAuth()

      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: 'test' })
      })

      await apiWithoutAuth.get('/api/test')

      expect(fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.not.objectContaining({
            'Authorization': expect.any(String)
          })
        })
      )

      vi.restoreAllMocks()
    })
  })

  describe('Request headers', () => {
    it('includes required headers', async () => {
      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: 'test' })
      })

      await api.get('/api/test')

      expect(fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          })
        })
      )
    })
  })
})