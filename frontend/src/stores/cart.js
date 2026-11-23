import { defineStore } from 'pinia'

const KEY = 'mx_cart'
const FREE_SHIPPING_OVER = 100
const SHIPPING_FLAT = 5.95
const MAX_QUANTITY = 10

const emptyCart = () => ({
  items: [],
  coupon: null,
  storeId: null,
  storeName: '',
})

function money(value) {
  return Math.round((Number(value) + Number.EPSILON) * 100) / 100
}

function load() {
  const empty = emptyCart()

  try {
    const saved = localStorage.getItem(KEY)

    if (!saved) return empty

    const parsed = JSON.parse(saved)

    if (
      !parsed ||
      typeof parsed !== 'object' ||
      !Array.isArray(parsed.items)
    ) {
      return empty
    }

    const items = parsed.items
      .filter((item) =>
        item &&
        Number.isInteger(Number(item.variantId)) &&
        Number.isFinite(Number(item.price)) &&
        Number(item.price) >= 0 &&
        Number.isFinite(Number(item.quantity)) &&
        Number.isFinite(Number(item.stock))
      )
      .map((item) => ({
        ...item,
        variantId: Number(item.variantId),
        price: Number(item.price),
        stock: Math.max(0, Math.floor(Number(item.stock))),
        quantity: Math.max(
          0,
          Math.min(
            Math.floor(Number(item.quantity)),
            Math.floor(Number(item.stock)),
            MAX_QUANTITY
          )
        ),
      }))
      .filter((item) => item.quantity > 0)

    if (!items.length) return empty

    return {
      items,
      coupon: parsed.coupon ?? null,
      storeId: parsed.storeId ?? null,
      storeName: parsed.storeName ?? '',
    }
  } catch {
    return empty
  }
}

/**
 * Made Exclusif shopping cart.
 *
 * Each checkout contains products from one store only.
 * Product prices and stock are verified again by Laravel.
 */
export const useCartStore = defineStore('cart', {
  state: () => load(),

  getters: {
    count: (state) =>
      state.items.reduce(
        (total, item) => total + item.quantity,
        0
      ),

    subtotal: (state) =>
      money(
        state.items.reduce(
          (total, item) =>
            total + item.price * item.quantity,
          0
        )
      ),

    discount() {
      return money(
        Math.min(
          this.subtotal,
          Math.max(0, Number(this.coupon?.discount) || 0)
        )
      )
    },

    shipping() {
      if (!this.items.length) return 0

      const amount = money(this.subtotal - this.discount)

      return amount >= FREE_SHIPPING_OVER
        ? 0
        : SHIPPING_FLAT
    },

    total() {
      return money(
        Math.max(this.subtotal - this.discount, 0) +
          this.shipping
      )
    },

    freeShippingRemaining() {
      return money(
        Math.max(
          FREE_SHIPPING_OVER -
            (this.subtotal - this.discount),
          0
        )
      )
    },

    isEmpty: (state) => state.items.length === 0,

    hasCoupon: (state) => state.coupon !== null,
  },

  actions: {
    persist() {
      try {
        localStorage.setItem(
          KEY,
          JSON.stringify({
            items: this.items,
            coupon: this.coupon,
            storeId: this.storeId,
            storeName: this.storeName,
          })
        )
      } catch (error) {
        console.warn('Unable to save shopping cart:', error)
      }
    },

    /**
     * Return false when products belong to different stores.
     */
    canAdd(product) {
      if (!product?.store_id) return false

      return (
        this.items.length === 0 ||
        String(this.storeId) === String(product.store_id)
      )
    },

    /**
     * Add a product variant to the cart.
     */
    add(product, variant, quantity = 1) {
      if (!product || !variant) return false

      if (!this.canAdd(product)) {
        return false
      }

      const stock = Math.max(
        0,
        Math.floor(Number(variant.stock) || 0)
      )

      const max = Math.min(stock, MAX_QUANTITY)

      if (max < 1) return false

      const requested = Math.floor(Number(quantity))

      if (!Number.isFinite(requested) || requested < 1) {
        return false
      }

      const variantId = Number(variant.id)

      if (!Number.isInteger(variantId)) return false

      const price = Number(variant.price)

      if (!Number.isFinite(price) || price < 0) {
        return false
      }

      const existing = this.items.find(
        (item) => item.variantId === variantId
      )

      if (existing) {
        if (existing.quantity >= max) return false

        existing.quantity = Math.min(
          existing.quantity + requested,
          max
        )

        existing.stock = stock
        existing.price = price
      } else {
        this.items.push({
          variantId,
          slug: product.slug,
          name: product.name,
          brand: product.brand?.name ?? '',
          accent: product.accent ?? null,
          image: product.image_url ?? null,
          kind: product.store?.type ?? 'perfume',
          href:
            product.store && !product.store.is_main
              ? `/s/${product.store.slug}/product/${product.slug}`
              : `/product/${product.slug}`,
          label: variant.label ?? variant.size_label ?? '',
          price,
          stock,
          quantity: Math.min(requested, max),
        })
      }

      if (this.items.length === 1) {
        this.storeId = product.store_id
        this.storeName = product.store?.name ?? ''
      }

      this.coupon = null
      this.persist()

      return true
    },

    /**
     * Update an item's quantity.
     */
    setQuantity(variantId, quantity) {
      const item = this.items.find(
        (entry) =>
          entry.variantId === Number(variantId)
      )

      if (!item) return false

      const requested = Math.floor(Number(quantity))

      if (!Number.isFinite(requested)) return false

      if (requested <= 0) {
        this.remove(variantId)
        return true
      }

      const max = Math.min(item.stock, MAX_QUANTITY)

      if (max < 1) {
        this.remove(variantId)
        return true
      }

      item.quantity = Math.max(
        1,
        Math.min(requested, max)
      )

      this.coupon = null
      this.persist()

      return true
    },

    /**
     * Remove one variant from the cart.
     */
    remove(variantId) {
      const previousCount = this.items.length

      this.items = this.items.filter(
        (item) =>
          item.variantId !== Number(variantId)
      )

      if (previousCount === this.items.length) {
        return false
      }

      this.coupon = null

      if (!this.items.length) {
        this.storeId = null
        this.storeName = ''
      }

      this.persist()

      return true
    },

    /**
     * Save a coupon validated by the backend.
     */
    setCoupon(coupon) {
      this.coupon = coupon ?? null
      this.persist()
    },

    /**
     * Remove the applied coupon.
     */
    removeCoupon() {
      this.coupon = null
      this.persist()
    },

    /**
     * Empty the shopping cart.
     */
    clear() {
      this.items = []
      this.coupon = null
      this.storeId = null
      this.storeName = ''

      this.persist()
    },
  },
})

