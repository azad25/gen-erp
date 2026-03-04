interface MenuItem {
  id: string
  label: string
  url: string
  page_id?: string
  target: string
  sort_order: number
  parent_id?: string
  children?: MenuItem[]
}

interface Menu {
  id: string
  name: string
  location: string
  items: MenuItem[]
}

interface MenuResponse {
  data: Menu[]
}

export const useMenu = () => {
  const { $fetch } = useNuxtApp()
  const tenant = useTenant()
  
  const menus = ref<Menu[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch menus for the current tenant
  const fetchMenus = async () => {
    if (!tenant.value?.slug) {
      console.warn('No tenant slug available for menu fetching')
      return
    }

    try {
      loading.value = true
      error.value = null
      
      const response = await $fetch<MenuResponse>(`/api/public/${tenant.value.slug}/menus`)
      
      // Process menu items to build hierarchy
      const processedMenus = response.data.map(menu => ({
        ...menu,
        items: buildMenuHierarchy(menu.items)
      }))
      
      menus.value = processedMenus
    } catch (err) {
      console.error('Failed to fetch menus:', err)
      error.value = 'Failed to load navigation menus'
      
      // Use default menu structure in development
      if (process.dev) {
        menus.value = getDefaultMenus()
      }
    } finally {
      loading.value = false
    }
  }

  // Build hierarchical menu structure from flat array
  const buildMenuHierarchy = (items: MenuItem[]): MenuItem[] => {
    const itemMap = new Map<string, MenuItem>()
    const rootItems: MenuItem[] = []

    // First pass: create map of all items
    items.forEach(item => {
      itemMap.set(item.id, { ...item, children: [] })
    })

    // Second pass: build hierarchy
    items.forEach(item => {
      const menuItem = itemMap.get(item.id)!
      
      if (item.parent_id && itemMap.has(item.parent_id)) {
        const parent = itemMap.get(item.parent_id)!
        if (!parent.children) parent.children = []
        parent.children.push(menuItem)
      } else {
        rootItems.push(menuItem)
      }
    })

    // Sort items by sort_order
    const sortItems = (items: MenuItem[]) => {
      items.sort((a, b) => a.sort_order - b.sort_order)
      items.forEach(item => {
        if (item.children && item.children.length > 0) {
          sortItems(item.children)
        }
      })
    }

    sortItems(rootItems)
    return rootItems
  }

  // Get menu by location
  const getMenuByLocation = (location: string): Menu | undefined => {
    return menus.value.find(menu => menu.location === location)
  }

  // Get header menu
  const getHeaderMenu = (): Menu | undefined => {
    return getMenuByLocation('header') || getMenuByLocation('primary')
  }

  // Get footer menu
  const getFooterMenu = (): Menu | undefined => {
    return getMenuByLocation('footer') || getMenuByLocation('secondary')
  }

  // Default menu structure for development/fallback
  const getDefaultMenus = (): Menu[] => [
    {
      id: 'default-header',
      name: 'Header Menu',
      location: 'header',
      items: [
        {
          id: '1',
          label: 'Home',
          url: '/',
          target: '_self',
          sort_order: 1
        },
        {
          id: '2',
          label: 'About',
          url: '/about',
          target: '_self',
          sort_order: 2
        },
        {
          id: '3',
          label: 'Services',
          url: '/services',
          target: '_self',
          sort_order: 3,
          children: [
            {
              id: '3-1',
              label: 'Web Development',
              url: '/services/web-development',
              target: '_self',
              sort_order: 1,
              parent_id: '3'
            },
            {
              id: '3-2',
              label: 'Mobile Apps',
              url: '/services/mobile-apps',
              target: '_self',
              sort_order: 2,
              parent_id: '3'
            },
            {
              id: '3-3',
              label: 'Consulting',
              url: '/services/consulting',
              target: '_self',
              sort_order: 3,
              parent_id: '3'
            }
          ]
        },
        {
          id: '4',
          label: 'Portfolio',
          url: '/portfolio',
          target: '_self',
          sort_order: 4
        },
        {
          id: '5',
          label: 'Blog',
          url: '/blog',
          target: '_self',
          sort_order: 5
        },
        {
          id: '6',
          label: 'Contact',
          url: '/contact',
          target: '_self',
          sort_order: 6
        }
      ]
    },
    {
      id: 'default-footer',
      name: 'Footer Menu',
      location: 'footer',
      items: [
        {
          id: 'f1',
          label: 'Privacy Policy',
          url: '/privacy',
          target: '_self',
          sort_order: 1
        },
        {
          id: 'f2',
          label: 'Terms of Service',
          url: '/terms',
          target: '_self',
          sort_order: 2
        },
        {
          id: 'f3',
          label: 'Support',
          url: '/support',
          target: '_self',
          sort_order: 3
        }
      ]
    }
  ]

  // Check if a URL is active (current page)
  const isActiveUrl = (url: string, currentPath: string): boolean => {
    if (url === currentPath) return true
    if (url !== '/' && currentPath.startsWith(url)) return true
    return false
  }

  // Check if a menu item or its children are active
  const isMenuItemActive = (item: MenuItem, currentPath: string): boolean => {
    if (isActiveUrl(item.url, currentPath)) return true
    
    if (item.children && item.children.length > 0) {
      return item.children.some(child => isMenuItemActive(child, currentPath))
    }
    
    return false
  }

  return {
    menus: readonly(menus),
    loading: readonly(loading),
    error: readonly(error),
    fetchMenus,
    getMenuByLocation,
    getHeaderMenu,
    getFooterMenu,
    isActiveUrl,
    isMenuItemActive
  }
}