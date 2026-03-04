import { ref, onMounted, onUnmounted } from 'vue'

export function useResponsive() {
  const windowWidth = ref(window.innerWidth)
  const windowHeight = ref(window.innerHeight)

  // Breakpoints (matching Tailwind CSS)
  const breakpoints = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536
  }

  // Computed properties for different screen sizes
  const isMobile = ref(windowWidth.value < breakpoints.md)
  const isTablet = ref(windowWidth.value >= breakpoints.md && windowWidth.value < breakpoints.lg)
  const isDesktop = ref(windowWidth.value >= breakpoints.lg)
  const isLargeScreen = ref(windowWidth.value >= breakpoints.xl)

  // Device type detection
  const deviceType = ref(getDeviceType())

  function getDeviceType() {
    if (windowWidth.value < breakpoints.md) return 'mobile'
    if (windowWidth.value < breakpoints.lg) return 'tablet'
    return 'desktop'
  }

  function updateScreenSize() {
    windowWidth.value = window.innerWidth
    windowHeight.value = window.innerHeight
    
    isMobile.value = windowWidth.value < breakpoints.md
    isTablet.value = windowWidth.value >= breakpoints.md && windowWidth.value < breakpoints.lg
    isDesktop.value = windowWidth.value >= breakpoints.lg
    isLargeScreen.value = windowWidth.value >= breakpoints.xl
    deviceType.value = getDeviceType()
  }

  // Touch device detection
  const isTouchDevice = ref('ontouchstart' in window || navigator.maxTouchPoints > 0)

  // Orientation detection
  const orientation = ref(getOrientation())

  function getOrientation() {
    return windowWidth.value > windowHeight.value ? 'landscape' : 'portrait'
  }

  function updateOrientation() {
    orientation.value = getOrientation()
  }

  // Grid columns based on screen size
  const getGridColumns = (mobile = 1, tablet = 2, desktop = 3, large = 4) => {
    if (isMobile.value) return mobile
    if (isTablet.value) return tablet
    if (isLargeScreen.value) return large
    return desktop
  }

  // Container padding based on screen size
  const getContainerPadding = () => {
    if (isMobile.value) return 'px-4'
    if (isTablet.value) return 'px-6'
    return 'px-8'
  }

  // Modal size based on screen size
  const getModalSize = () => {
    if (isMobile.value) return 'w-full h-full'
    if (isTablet.value) return 'w-11/12 max-w-2xl'
    return 'w-1/2 max-w-4xl'
  }

  // Table responsiveness
  const shouldUseCardLayout = ref(isMobile.value)

  // Sidebar behavior
  const shouldCollapseSidebar = ref(isMobile.value || isTablet.value)

  // Event listeners
  onMounted(() => {
    window.addEventListener('resize', updateScreenSize)
    window.addEventListener('orientationchange', updateOrientation)
    updateScreenSize()
  })

  onUnmounted(() => {
    window.removeEventListener('resize', updateScreenSize)
    window.removeEventListener('orientationchange', updateOrientation)
  })

  return {
    // Screen dimensions
    windowWidth,
    windowHeight,
    
    // Device detection
    isMobile,
    isTablet,
    isDesktop,
    isLargeScreen,
    deviceType,
    isTouchDevice,
    orientation,
    
    // Utility functions
    getGridColumns,
    getContainerPadding,
    getModalSize,
    shouldUseCardLayout,
    shouldCollapseSidebar,
    
    // Breakpoints
    breakpoints
  }
}