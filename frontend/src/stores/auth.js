import { defineStore } from 'pinia'
import { api, tokenStore } from '@/api/http'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, wishlistIds: [] }),
  getters: {
    isAdmin: (s) => s.user?.role === 'admin',
  },
  actions: {
    async init() {
      if (!tokenStore.get()) return
      try {
        this.user = await api('/me')
        this.loadWishlist()
      } catch {
        tokenStore.clear()
      }
    },
    async login(payload) {
      const { user, token } = await api('/login', { method: 'POST', body: payload })
      tokenStore.set(token)
      this.user = user
      this.loadWishlist()
    },
    async register(payload) {
      const { user, token } = await api('/register', { method: 'POST', body: payload })
      tokenStore.set(token)
      this.user = user
    },
    async logout() {
      try { await api('/logout', { method: 'POST' }) } catch { /* token may already be invalid */ }
      tokenStore.clear()
      this.user = null
      this.wishlistIds = []
    },
    async loadWishlist() {
      try {
        const items = await api('/wishlist')
        this.wishlistIds = items.map((p) => p.id)
      } catch { /* ignore */ }
    },
    async toggleWishlist(productId) {
      const { wishlisted } = await api(`/wishlist/${productId}`, { method: 'POST' })
      this.wishlistIds = wishlisted
        ? [...this.wishlistIds, productId]
        : this.wishlistIds.filter((id) => id !== productId)
    },
  },
})
