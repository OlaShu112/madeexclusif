import { defineStore } from 'pinia'
import { api, tokenStore } from '@/api/http'

export const useAuthStore = defineStore('auth', {

  state: () => ({
    user: null,
    wishlistIds: [],
    initialized: false,
    loading: false,
    initializationError: null,
  }),

  getters: {

    isAdmin: (state) =>
      state.user?.role === 'admin',

    isAuthenticated: (state) =>
      Boolean(state.user),

    isInitialized: (state) =>
      state.initialized,

  },

  actions: {

    /*
    |--------------------------------------------------------------------------
    | Restore Authentication
    |--------------------------------------------------------------------------
    */

    async init() {

      if (this.initialized) {
        return
      }

      this.loading = true
      this.initializationError = null

      try {

        const token = tokenStore.get()

        if (!token) {

          this.user = null
          this.wishlistIds = []
          this.initialized = true

          return
        }

        const user = await api('/me')

        if (tokenStore.get() !== token) {
          return
        }

        if (!user?.id) {
          throw new Error(
            'Invalid authentication response.'
          )
        }

        this.user = user
        this.initialized = true

        void this.loadWishlist()

      } catch (error) {

        this.user = null
        this.wishlistIds = []

        // Only discard a token when authentication
        // has explicitly been rejected.

        if (error?.status === 401) {

          tokenStore.clear()
          this.initialized = true

        } else {

          // Preserve the token during temporary
          // network or server failures.

          this.initializationError =
            'Unable to verify your session. Please try again.'

          this.initialized = false

          console.error(
            'Authentication restoration failed:',
            error?.status || error?.message
          )

        }

      } finally {

        this.loading = false

      }

    },

    /*
    |--------------------------------------------------------------------------
    | User Login
    |--------------------------------------------------------------------------
    */

    async login(payload) {

      this.loading = true
      this.initializationError = null

      try {

        const { user, token } = await api('/login', {
          method: 'POST',
          body: payload,
        })

        if (!user?.id || !token) {
          throw new Error(
            'Invalid login response.'
          )
        }

        tokenStore.set(token)

        this.user = user
        this.wishlistIds = []
        this.initialized = true

        void this.loadWishlist()

      } finally {

        this.loading = false

      }

    },

    /*
    |--------------------------------------------------------------------------
    | User Registration
    |--------------------------------------------------------------------------
    */

    async register(payload) {

      this.loading = true
      this.initializationError = null

      try {

        const { user, token } = await api('/register', {
          method: 'POST',
          body: payload,
        })

        if (!user?.id || !token) {
          throw new Error(
            'Invalid registration response.'
          )
        }

        tokenStore.set(token)

        this.user = user
        this.wishlistIds = []
        this.initialized = true

      } finally {

        this.loading = false

      }

    },

    /*
    |--------------------------------------------------------------------------
    | User Logout
    |--------------------------------------------------------------------------
    */

    async logout() {

      this.loading = true

      try {

        if (tokenStore.get()) {

          await api('/logout', {
            method: 'POST',
          })

        }

      } catch (error) {

        if (error?.status !== 401) {

          console.warn(
            'Server-side logout could not be confirmed.'
          )

        }

      } finally {

        tokenStore.clear()

        this.user = null
        this.wishlistIds = []
        this.initialized = true
        this.initializationError = null
        this.loading = false

      }

    },

    /*
    |--------------------------------------------------------------------------
    | Load Customer Wishlist
    |--------------------------------------------------------------------------
    */

    async loadWishlist() {

      if (!this.user || this.isAdmin) {

        this.wishlistIds = []

        return
      }

      const token = tokenStore.get()

      try {

        const items = await api('/wishlist')

        if (tokenStore.get() !== token) {
          return
        }

        this.wishlistIds = Array.isArray(items)
          ? items.map((product) => product.id)
          : []

      } catch {

        // Wishlist failures must not invalidate
        // the user's authentication session.

      }

    },

    /*
    |--------------------------------------------------------------------------
    | Toggle Wishlist Product
    |--------------------------------------------------------------------------
    */

    async toggleWishlist(productId) {

      const { wishlisted } = await api(
        `/wishlist/${productId}`,
        {
          method: 'POST',
        }
      )

      this.wishlistIds = wishlisted

        ? [...new Set([
            ...this.wishlistIds,
            productId,
          ])]

        : this.wishlistIds.filter(
            (id) => id !== productId
          )

    },

  },

})