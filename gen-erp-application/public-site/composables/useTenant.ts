export interface Tenant {
  id: string
  name: string
  slug: string
  domain?: string
  subdomain?: string
  settings: {
    theme: {
      primary_color: string
      secondary_color: string
      accent_color: string
      font_family: string
    }
    seo: {
      title: string
      description: string
      keywords: string[]
    }
    contact: {
      email: string
      phone: string
      address: string
    }
    social: {
      facebook?: string
      twitter?: string
      instagram?: string
      linkedin?: string
    }
  }
}

export const useTenant = () => {
  const tenant = ref<Tenant | null>(null)
  const loading = ref(true)
  const error = ref<string | null>(null)

  const resolveTenant = async () => {
    try {
      loading.value = true
      error.value = null

      // Get the current host
      const host = process.client ? window.location.host : useRequestHeaders().host
      
      if (!host) {
        throw new Error('Unable to determine host')
      }

      // Parse domain/subdomain
      const parts = host.split('.')
      let domain = ''
      let subdomain = ''

      if (parts.length >= 2) {
        if (parts.length === 2) {
          // example.com
          domain = host
        } else {
          // subdomain.example.com
          subdomain = parts[0]
          domain = parts.slice(1).join('.')
        }
      }

      // Fetch tenant data from API
      const config = useRuntimeConfig()
      const { data } = await $fetch<{ data: Tenant }>(`${config.public.apiBase}/public/tenant/resolve`, {
        method: 'POST',
        body: {
          domain,
          subdomain
        }
      })

      tenant.value = data
      
      // Apply theme variables
      if (data.settings.theme && process.client) {
        applyTheme(data.settings.theme)
      }

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to resolve tenant'
      console.error('Tenant resolution error:', err)
    } finally {
      loading.value = false
    }
  }

  const applyTheme = (theme: Tenant['settings']['theme']) => {
    if (!process.client) return

    const root = document.documentElement
    
    if (theme.primary_color) {
      root.style.setProperty('--primary-color', theme.primary_color)
    }
    if (theme.secondary_color) {
      root.style.setProperty('--secondary-color', theme.secondary_color)
    }
    if (theme.accent_color) {
      root.style.setProperty('--accent-color', theme.accent_color)
    }
    if (theme.font_family) {
      root.style.setProperty('--font-family', theme.font_family)
      document.body.style.fontFamily = theme.font_family
    }
  }

  const getTenantMeta = () => {
    if (!tenant.value) return {}

    return {
      title: tenant.value.settings.seo.title || tenant.value.name,
      description: tenant.value.settings.seo.description,
      keywords: tenant.value.settings.seo.keywords?.join(', '),
      ogTitle: tenant.value.settings.seo.title || tenant.value.name,
      ogDescription: tenant.value.settings.seo.description,
      twitterTitle: tenant.value.settings.seo.title || tenant.value.name,
      twitterDescription: tenant.value.settings.seo.description
    }
  }

  return {
    tenant: readonly(tenant),
    loading: readonly(loading),
    error: readonly(error),
    resolveTenant,
    getTenantMeta
  }
}