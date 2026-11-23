import { defineStore } from 'pinia'
import { api } from '@/api/http'

/**
 * Made Exclusif - Storefront State Management
 *
 * Manages the currently selected seller storefront.
 *
 * Supports:
 * - Store branding
 * - Store switching
 * - Loading states
 * - Store availability
 * - API error handling
 */

export const useStorefrontStore = defineStore('storefront', {
  state: () => ({
    store: null,
    loading: false,
    notFound: false,
    error: null,
  }),

  getters: {
    storeName: (state) => state.store?.name ?? 'Made Exclusif',

    storeSlug: (state) => state.store?.slug ?? null,

    storeLogo: (state) => state.store?.logo_url ?? null,

    storeAccent: (state) => state.store?.accent ?? '#2f3a45',

    storeType: (state) => state.store?.type ?? null,

    isMainStore: (state) => state.store?.is_main === true,

    isStoreAvailable: (state) =>
      state.store !== null && state.store.status === 'active',
  },

  actions: {
    /**
     * Load storefront details by slug.
     */
    async load(slug) {
      if (!slug) {
        this.reset()
        return null
      }

      // Avoid unnecessary API calls.
      if (this.store?.slug === slug && !this.error) {
        return this.store
      }

      this.loading = true
      this.notFound = false
      this.error = null
      this.store = null

      try {
        const store = await api(
          `/stores/${encodeURIComponent(slug)}`
        )

        this.store = store

        return store
      } catch (error) {
        this.store = null

        const status = error?.status ?? error?.response?.status

        this.notFound = status === 404

        this.error = this.notFound
          ? 'This store could not be found.'
          : 'Unable to load this store. Please try again.'

        return null
      } finally {
        this.loading = false
      }
    },

    /**
     * Clear the current storefront.
     */
    reset() {
      this.store = null
      this.loading = false
      this.notFound = false
      this.error = null
    },

    /**
     * Refresh the current storefront.
     */
    async refresh() {
      const slug = this.store?.slug

      if (!slug) {
        return null
      }

      this.store = null

      return this.load(slug)
    },
  },
})

