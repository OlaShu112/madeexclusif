
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStorefrontStore } from '@/stores/storefront'

export function useBusinessContext() {
  const route = useRoute()
  const router = useRouter()
  const storefront = useStorefrontStore()

  const storeSlug = computed(() =>
    String(route.params.store || '')
  )

  const isIndependentStore = computed(() =>
    route.meta.layout === 'storefront' &&
    Boolean(storeSlug.value)
  )

  const business = computed(() => {
    if (!isIndependentStore.value) {
      return null
    }

    return storefront.store?.slug === storeSlug.value
      ? storefront.store
      : null
  })

  const businessName = computed(() =>
    isIndependentStore.value
      ? business.value?.name || 'Store'
      : 'Made Exclusif'
  )

  const businessLogo = computed(() =>
    isIndependentStore.value
      ? business.value?.logo_url || null
      : '/images/made-exclusif-logo.png'
  )

  const businessTheme = computed(() =>
    business.value?.website_theme || {}
  )

  function customerRoute(name, query = {}) {
    if (isIndependentStore.value) {
      return {
        name: `store-${name}`,
        params: {
          store: storeSlug.value
        },
        query
      }
    }

    return {
      name,
      query
    }
  }

  function homeRoute() {
    return customerRoute('home')
  }

  function accountRoute() {
    return customerRoute('account')
  }

  function loginRoute(query = {}) {
    return customerRoute('login', query)
  }

  function registerRoute(query = {}) {
    return customerRoute('register', query)
  }

  function forgotPasswordRoute(query = {}) {
    return customerRoute('forgot-password', query)
  }

  function resetPasswordRoute(query = {}) {
    return customerRoute('reset-password', query)
  }

  function safeRedirect(value) {
    if (
      typeof value !== 'string' ||
      !value.startsWith('/') ||
      value.startsWith('//') ||
      value.includes('\\') ||
      /[\u0000-\u001F\u007F]/.test(value)
    ) {
      return accountRoute()
    }

    const destination = router.resolve(value)

    if (
      !destination.matched.length ||
      destination.name === 'not-found' ||
      destination.meta.guestOnly
    ) {
      return accountRoute()
    }

    if (isIndependentStore.value) {
      if (
        destination.meta.layout !== 'storefront' ||
        String(destination.params.store || '') !==
          storeSlug.value
      ) {
        return accountRoute()
      }
    }

    return destination.fullPath
  }

  return {
    storeSlug,
    isIndependentStore,
    business,
    businessName,
    businessLogo,
    businessTheme,
    customerRoute,
    homeRoute,
    accountRoute,
    loginRoute,
    registerRoute,
    forgotPasswordRoute,
    resetPasswordRoute,
    safeRedirect
  }
}
