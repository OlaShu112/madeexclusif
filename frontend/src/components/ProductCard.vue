<script setup>
import { useRouter } from 'vue-router'
import ProductImage from './ProductImage.vue'
import { useAuthStore } from '@/stores/auth'
import { money } from '@/utils/format'

const props = defineProps({ product: { type: Object, required: true } })
const auth = useAuthStore()
const router = useRouter()

async function wish() {
  if (!auth.user) return router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
  await auth.toggleWishlist(props.product.id)
}
</script>

<template>
  <article class="card">
    <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="card__media">
      <ProductImage :src="product.image_url" :accent="product.accent" :label="product.name" />
    </router-link>
    <span v-if="product.is_featured" class="tag">Bestseller</span>
    <button class="wish" :class="{ on: auth.wishlistIds.includes(product.id) }" :aria-label="`Save ${product.name}`" @click="wish">
      {{ auth.wishlistIds.includes(product.id) ? '♥' : '♡' }}
    </button>
    <div class="card__body">
      <div class="card__brand">{{ product.brand?.name }}</div>
      <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="card__name">{{ product.name }}</router-link>
      <div class="card__meta">
        <span>{{ product.concentration }}</span>
        <span>from {{ money(product.min_price) }}</span>
      </div>
    </div>
  </article>
</template>