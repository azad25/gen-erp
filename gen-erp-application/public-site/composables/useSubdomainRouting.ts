interface SubdomainConfig {
  subdomain: string | null
  domain: string
  isCustomDomain: boolean
  tenantSlug: string | null
}

interface DomainMapping {
  domain: string
  subdomain?: string
  tenantSlug: string
  isActive: boolean
}

export const useSubdomainRouting = () => {
  const config = useRuntimeConfig()
  const { tenant } = useTenant()
  
  // Get current subdomain configuration
  const getSubdomainConfig = (): SubdomainConfig => {
    const currentSubdomain = useState<string | null>('currentSubdomain', () => null)
    const currentDomain = useState<string>('currentDomain', () => '')
    
    // Determine if this is a custom domain
    const baseDomain = config.public.baseDomain || 'yourplatform.com'
    const isCustomDomain = !currentDomain.value.includes(baseDomain)
    
    return {
      subdomain: currentSubdomain.value,
      domain: currentDomain.value,
      isCustomDomain,
      tenantSlug: tenant.value?.slug || null
    }
  }
  
  // Generate tenant URL
  const generateTenantUrl = (tenantSlug: string, path: string = '/'): string => {
    const config = getSubdomainConfig()
    const baseDomain = useRuntimeConfig().public.baseDomain || 'yourplatform.com'
    
    // If tenant has a custom domain, use it
    if (tenant.value?.settings?.domain?.custom_domain) {
      return `https://${tenant.value.settings.domain.custom_domain}${path}`
    }
    
    // Otherwise use subdomain
    return `https://${tenantSlug}.${baseDomain}${path}`
  }
  
  // Check if current request is for a specific tenant
  const isValidTenantRequest = (): boolean => {
    const config = getSubdomainConfig()
    
    // If we have a tenant and either a subdomain or custom domain, it's valid
    return !!(tenant.value && (config.subdomain || config.isCustomDomain))
  }
  
  // Redirect to correct tenant URL if needed
  const redirectToTenantUrl = (tenantSlug: string, path: string = '/') => {
    const correctUrl = generateTenantUrl(tenantSlug, path)
    const currentUrl = window.location.href
    
    if (correctUrl !== currentUrl) {
      window.location.href = correctUrl
    }
  }
  
  // Get all domain mappings for a tenant (requires API call)
  const getTenantDomains = async (tenantId: string): Promise<DomainMapping[]> => {
    try {
      const response = await $fetch(`/api/v1/cms/domains/${tenantId}`)
      return response.data || []
    } catch (error) {
      console.error('Failed to fetch tenant domains:', error)
      return []
    }
  }
  
  // Validate custom domain (requires server-side verification)
  const validateCustomDomain = async (domain: string, tenantId: string): Promise<boolean> => {
    try {
      const response = await $fetch('/api/v1/cms/domains/validate', {
        method: 'POST',
        body: {
          domain,
          tenant_id: tenantId
        }
      })
      
      return response.valid || false
    } catch (error) {
      console.error('Failed to validate custom domain:', error)
      return false
    }
  }
  
  // Add custom domain for tenant (requires server-side implementation)
  const addCustomDomain = async (domain: string, tenantId: string): Promise<boolean> => {
    try {
      const response = await $fetch('/api/v1/cms/domains', {
        method: 'POST',
        body: {
          domain,
          tenant_id: tenantId
        }
      })
      
      return response.success || false
    } catch (error) {
      console.error('Failed to add custom domain:', error)
      return false
    }
  }
  
  // Remove custom domain
  const removeCustomDomain = async (domainId: string): Promise<boolean> => {
    try {
      const response = await $fetch(`/api/v1/cms/domains/${domainId}`, {
        method: 'DELETE'
      })
      
      return response.success || false
    } catch (error) {
      console.error('Failed to remove custom domain:', error)
      return false
    }
  }
  
  // Generate SSL certificate for custom domain (requires server-side implementation)
  const generateSSLCertificate = async (domain: string): Promise<boolean> => {
    try {
      const response = await $fetch('/api/v1/cms/domains/ssl', {
        method: 'POST',
        body: { domain }
      })
      
      return response.success || false
    } catch (error) {
      console.error('Failed to generate SSL certificate:', error)
      return false
    }
  }
  
  // Check SSL certificate status
  const checkSSLStatus = async (domain: string): Promise<any> => {
    try {
      const response = await $fetch(`/api/v1/cms/domains/ssl/status?domain=${encodeURIComponent(domain)}`)
      return response.data || null
    } catch (error) {
      console.error('Failed to check SSL status:', error)
      return null
    }
  }
  
  // Get canonical URL for current page
  const getCanonicalUrl = (path: string = '/'): string => {
    const config = getSubdomainConfig()
    
    if (tenant.value?.settings?.domain?.custom_domain) {
      return `https://${tenant.value.settings.domain.custom_domain}${path}`
    }
    
    if (config.subdomain) {
      const baseDomain = useRuntimeConfig().public.baseDomain || 'yourplatform.com'
      return `https://${config.subdomain}.${baseDomain}${path}`
    }
    
    return `${useRuntimeConfig().public.siteUrl}${path}`
  }
  
  // Setup canonical URL in head
  const setupCanonicalUrl = (path?: string) => {
    const canonicalUrl = getCanonicalUrl(path)
    
    useHead({
      link: [
        {
          rel: 'canonical',
          href: canonicalUrl
        }
      ]
    })
  }
  
  // Handle subdomain-specific routing
  const handleSubdomainRouting = () => {
    const config = getSubdomainConfig()
    
    // If we have a subdomain but no tenant, show 404
    if (config.subdomain && !tenant.value) {
      throw createError({
        statusCode: 404,
        statusMessage: `Subdomain "${config.subdomain}" not found`
      })
    }
    
    // If we have a custom domain but no tenant, show 404
    if (config.isCustomDomain && !tenant.value) {
      throw createError({
        statusCode: 404,
        statusMessage: `Domain "${config.domain}" not found`
      })
    }
    
    // Setup canonical URL for current page
    setupCanonicalUrl()
  }
  
  return {
    getSubdomainConfig,
    generateTenantUrl,
    isValidTenantRequest,
    redirectToTenantUrl,
    getTenantDomains,
    validateCustomDomain,
    addCustomDomain,
    removeCustomDomain,
    generateSSLCertificate,
    checkSSLStatus,
    getCanonicalUrl,
    setupCanonicalUrl,
    handleSubdomainRouting
  }
}

// TODO: Server-side implementation needed
/*
Backend API endpoints to implement:

1. GET /api/v1/cms/domains/{tenantId}
   - Get all domains for a tenant
   - Return subdomain and custom domains

2. POST /api/v1/cms/domains/validate
   - Validate custom domain ownership
   - Check DNS records and SSL certificate

3. POST /api/v1/cms/domains
   - Add custom domain for tenant
   - Setup DNS and SSL certificate

4. DELETE /api/v1/cms/domains/{domainId}
   - Remove custom domain
   - Clean up DNS and SSL

5. POST /api/v1/cms/domains/ssl
   - Generate SSL certificate for domain
   - Use Let's Encrypt or similar service

6. GET /api/v1/cms/domains/ssl/status
   - Check SSL certificate status
   - Return expiry date and validity

Database schema needed:
- tenant_domains table
- SSL certificate storage
- DNS record management

Infrastructure requirements:
- Wildcard SSL certificate for subdomains
- DNS management (Cloudflare API)
- Load balancer configuration
- SSL certificate automation (Let's Encrypt)
*/