import { defineStore } from 'pinia'
import { api } from '@/api/http'

export const useStorefrontStore = defineStore('storefront', {

  state: () => ({
    store: null,
    loading: false,
    notFound: false,
    error: null,
    requestVersion: 0
  }),

  getters: {
    storeName: state => state.store?.name ?? 'Store',

    storeSlug: state => state.store?.slug ?? null,

    storeLogo: state => state.store?.logo_url ?? null,

    storeAccent: state => state.store?.accent ?? '#222222',

    storeType: state => state.store?.type ?? null,

    isMainStore: state => state.store?.is_main === true,

    isStoreAvailable: state =>
      state.store?.status === 'active'
  },

  actions: {

    async load(slug) {
      if (!slug) {
        this.reset()
        return null
      }

      const version = ++this.requestVersion

      this.loading = true
      this.notFound = false
      this.error = null
      this.store = null

      try {
        const store = await api(
          `/stores/${encodeURIComponent(slug)}`
        )

        if (version !== this.requestVersion) {
          return null
        }

        this.store = store

        return store

      } catch (error) {
        if (version !== this.requestVersion) {
          return null
        }

        this.notFound = error?.status === 404

        this.error = this.notFound
          ? 'This store could not be found.'
          : 'Unable to load this store. Please try again.'

        return null

      } finally {
        if (version === this.requestVersion) {
          this.loading = false
        }
      }
    },

    reset() {
      this.requestVersion++

      this.store = null
      this.loading = false
      this.notFound = false
      this.error = null
    },

    async refresh() {
      const slug = this.store?.slug

      return slug ? this.load(slug) : null
    }
  }
})