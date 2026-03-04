export default defineNuxtRouteMiddleware((to) => {
  // Only run on client-side for now
  if (process.server) return
  
  const { resolveTenantFromDomain } = useTenant()
  
  // Get the current hostname
  const hostname = window.location.hostname
  
  // Skip localhost and IP addresses in development
  if (hostname === 'localhost' || hostname.match(/^\d+\.\d+\.\d+\.\d+$/)) {
    return
  }
  
  // Parse subdomain from hostname
  const parts = hostname.split('.')
  
  // Check if this is a subdomain (more than 2 parts for .com domains)
  if (parts.length > 2) {
    const subdomain = parts[0]
    
    // Skip common subdomains that aren't tenant-specific
    const systemSubdomains = ['www', 'api', 'admin', 'app', 'mail', 'ftp', 'cdn', 'static']
    
    if (!systemSubdomains.includes(subdomain)) {
      // Resolve tenant from subdomain
      resolveTenantFromDomain(hostname)
      
      // Store subdomain info for later use
      useState('currentSubdomain', () => subdomain)
      useState('currentDomain', () => hostname)
    }
  } else {
    // This is a root domain, check if it's a custom domain
    resolveTenantFromDomain(hostname)
    
    // Store domain info
    useState('currentSubdomain', () => null)
    useState('currentDomain', () => hostname)
  }
})