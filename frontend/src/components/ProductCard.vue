<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import ProductImage from './ProductImage.vue'
import { useAuthStore } from '@/stores/auth'
import { money } from '@/utils/format'

/**
 * Made Exclusif - Product Card
 *
 * Supports:
 * - Main perfume store
 * - Independent seller storefronts
 * - Product images and placeholders
 * - Wishlist management
 * - Product pricing
 * - Featured product badges
 */

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const auth = useAuthStore()
const router = useRouter()

const wishlistLoading = ref(false)

const isSellerProduct = computed(() => {
  return Boolean(
    props.product.store &&
    !props.product.store.is_main &&
    props.product.store.slug
  )
})

const isClothing = computed(() => {
  return props.product.store?.type === 'clothing'
})

const to = computed(() => {
  if (isSellerProduct.value) {
    return {
      name: 'store-product',
      params: {
        store: props.product.store.slug,
        slug: props.product.slug,
      },
    }
  }

  return {
    name: 'product',
    params: {
      slug: props.product.slug,
    },
  }
})

const isWishlisted = computed(() => {
  return auth.wishlistIds?.includes(props.product.id) ?? false
})

const productBrand = computed(() => {
  return props.product.brand?.name ||
    props.product.store?.name ||
    ''
})

const productDetail = computed(() => {
  if (isClothing.value) {
    return props.product.category?.name || ''
  }

  return props.product.concentration || ''
})

const productPrice = computed(() => {
  const price = Number(props.product.min_price)

  return Number.isFinite(price) && price >= 0
    ? money(price)
    : 'Price unavailable'
})

const isFeatured = computed(() => {
  return Boolean(props.product.is_featured)
})

async function wish() {
  if (wishlistLoading.value) return

  if (!auth.user) {
    return router.push({
      name: 'login',
      query: {
        redirect: router.currentRoute.value.fullPath,
      },
    })
  }

  wishlistLoading.value = true

  try {
    await auth.toggleWishlist(props.product.id)
  } catch (error) {
    console.error('Unable to update wishlist:', error)
  } finally {
    wishlistLoading.value = false
  }
}
</script>

<template>
  <article class="product-card">
    <div class="product-card__media-wrap">
      <router-link
        :to="to"
        class="product-card__media"
        :aria-label="`View ${product.name}`"
      >
        <ProductImage
          :src="product.image_url"
          :accent="product.accent"
          :label="product.name"
          :kind="product.store?.type || 'perfume'"
        />
      </router-link>

      <span
        v-if="isFeatured"
        class="product-card__badge"
      >
        Featured
      </span>

      <button
        type="button"
        class="product-card__wish"
        :class="{ 'product-card__wish--active': isWishlisted }"
        :disabled="wishlistLoading"
        :aria-label="
          isWishlisted
            ? `Remove ${product.name} from wishlist`
            : `Add ${product.name} to wishlist`
        "
        :aria-pressed="isWishlisted"
        @click="wish"
      >
        <span aria-hidden="true">
          {{ isWishlisted ? '♥' : '♡' }}
        </span>
      </button>
    </div>

    <div class="product-card__body">
      <div
        v-if="productBrand"
        class="product-card__brand"
      >
        {{ productBrand }}
      </div>

      <router-link
        :to="to"
        class="product-card__name"
      >
        {{ product.name }}
      </router-link>

      <div class="product-card__meta">
        <span
          v-if="productDetail"
          class="product-card__detail"
        >
          {{ productDetail }}
        </span>

        <span class="product-card__price">
          <template v-if="product.min_price != null">
            From {{ productPrice }}
          </template>

          <template v-else>
            Price unavailable
          </template>
        </span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.product-card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #eeeeee;
  border-radius: 10px;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.product-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.product-card__media-wrap {
  position: relative;
  width: 100%;
  background: #f7f6f3;
}

.product-card__media {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  aspect-ratio: 1 / 1;
  padding: 14px;
  overflow: hidden;
  text-decoration: none;
}

.product-card__media :deep(img) {
  transition: transform 0.3s ease;
}

.product-card:hover .product-card__media :deep(img) {
  transform: scale(1.04);
}

.product-card__badge {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 5px 10px;
  background: #222222;
  color: #ffffff;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border-radius: 3px;
}

.product-card__wish {
  position: absolute;
  top: 10px;
  right: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid #e7e7e7;
  border-radius: 50%;
  background: #ffffff;
  color: #333333;
  font-size: 23px;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease;
}

.product-card__wish:hover {
  background: #f5f5f5;
}

.product-card__wish--active {
  color: #c0392b;
}

.product-card__wish:disabled {
  opacity: 0.5;
  cursor: wait;
}

.product-card__wish:focus-visible {
  outline: 2px solid #333333;
  outline-offset: 3px;
}

.product-card__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 16px;
}

.product-card__brand {
  margin-bottom: 6px;
  color: #777777;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.product-card__name {
  display: block;
  margin-bottom: 12px;
  color: #222222;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.5;
  text-decoration: none;
}

.product-card__name:hover {
  text-decoration: underline;
}

.product-card__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: auto;
  font-size: 13px;
}

.product-card__detail {
  color: #777777;
}

.product-card__price {
  color: #222222;
  font-weight: 700;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .product-card__body {
    padding: 12px;
  }

  .product-card__name {
    font-size: 14px;
  }

  .product-card__meta {
    flex-wrap: wrap;
  }

  .product-card__wish {
    width: 32px;
    height: 32px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .product-card,
  .product-card__media :deep(img) {
    transition: none;
  }

  .product-card:hover {
    transform: none;
  }
}
</style>

