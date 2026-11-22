import { defineStore } from 'pinia'

const KEY = 'mx_cart'
const FREE_SHIPPING_OVER = 100
const SHIPPING_FLAT = 5.95

function load() {
  try { return JSON.parse(localStorage.getItem(KEY)) || { items: [], coupon: null } } catch { return { items: [], coupon: null } }
}

export const useCartStore = defineStore('cart', {
  state: () => load(),
  getters: {
    count: (s) => s.items.reduce((n, i) => n + i.quantity, 0),
    subtotal: (s) => s.items.reduce((n, i) => n + i.price * i.quantity, 0),
    discount: (s) => s.coupon?.discount || 0,
    shipping() {
      if (!this.items.length) return 0
      return this.subtotal - this.discount >= FREE_SHIPPING_OVER ? 0 : SHIPPING_FLAT
    },
    total() { return Math.max(this.subtotal - this.discount, 0) + this.shipping },
    freeShippingRemaining() { return Math.max(FREE_SHIPPING_OVER - (this.subtotal - this.discount), 0) },
  },
  actions: {
    persist() { localStorage.setItem(KEY, JSON.stringify({ items: this.items, coupon: this.coupon })) },
    add(product, variant, quantity = 1) {
      const existing = this.items.find((i) => i.variantId === variant.id)
      const max = Math.min(variant.stock, 10)
      if (existing) existing.quantity = Math.min(existing.quantity + quantity, max)
      else this.items.push({
        variantId: variant.id, slug: product.slug, name: product.name, brand: product.brand?.name,
        accent: product.accent, image: product.image_url || null, sizeMl: variant.size_ml, price: Number(variant.price), stock: variant.stock,
        quantity: Math.min(quantity, max),
      })
      this.coupon = null // totals changed; the coupon must be re-validated
      this.persist()
    },
    setQuantity(variantId, q) {
      const item = this.items.find((i) => i.variantId === variantId)
      if (!item) return
      item.quantity = Math.max(1, Math.min(q, item.stock, 10))
      this.coupon = null
      this.persist()
    },
    remove(variantId) {
      this.items = this.items.filter((i) => i.variantId !== variantId)
      this.coupon = null
      this.persist()
    },
    setCoupon(c) { this.coupon = c; this.persist() },
    clear() { this.items = []; this.coupon = null; this.persist() },
  },
})
