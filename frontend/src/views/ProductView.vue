<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { money } from '@/utils/format'
import ProductImage from '@/components/ProductImage.vue'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()

const product = ref(null)
const related = ref([])
const loading = ref(true)
const notFound = ref(false)
const variantId = ref(null)
const qty = ref(1)
const added = ref(false)

const review = ref({ rating: 5, title: '', body: '' })
const reviewMsg = ref('')
const reviewErr = ref('')

const variant = computed(() => product.value?.variants.find((v) => v.id === variantId.value))

async function load() {
  try {
    const res = await api(`/products/${route.params.slug}`)
    product.value = res.product
    related.value = res.related
    const firstInStock = res.product.variants.find((v) => v.stock > 0) || res.product.variants[0]
    variantId.value = firstInStock?.id
    document.title = `${res.product.name} · MadeExlusif`
  } catch (e) {
    notFound.value = e.status === 404
  } finally {
    loading.value = false
  }
}

function addToBag() {
  if (!variant.value || variant.value.stock < 1) return
  cart.add(product.value, variant.value, qty.value)
  added.value = true
  setTimeout(() => (added.value = false), 2500)
}

async function submitReview() {
  reviewMsg.value = reviewErr.value = ''
  try {
    await api(`/products/${product.value.slug}/reviews`, { method: 'POST', body: review.value })
    reviewMsg.value = 'Thank you - your review has been saved.'
    await load()
  } catch (e) {
    reviewErr.value = e.message
  }
}

onMounted(load)
</script>

<template>
  <div class="container section">
    <div v-if="loading" class="skeleton" style="max-width:480px" />
    <div v-else-if="notFound" class="center">
      <h1>Fragrance not found</h1>
      <router-link class="btn btn--ghost" to="/shop">Back to shop</router-link>
    </div>

    <template v-else-if="product">
      <div class="pdp">
        <div class="pdp__media"><ProductImage :src="product.image_url" :accent="product.accent" :label="product.name" /></div>

        <div>
          <div class="eyebrow">{{ product.brand.name }} · {{ product.category.name }}</div>
          <h1>{{ product.name }}</h1>
          <div v-if="product.reviews_count" style="margin-bottom:8px">
            <span class="stars">{{ '★'.repeat(Math.round(product.rating_avg)) }}{{ '☆'.repeat(5 - Math.round(product.rating_avg)) }}</span>
            <span class="muted"> {{ Number(product.rating_avg).toFixed(1) }} ({{ product.reviews_count }})</span>
          </div>
          <p class="muted">{{ product.concentration }} · {{ product.gender }}</p>
          <p>{{ product.description }}</p>

          <div class="price">{{ variant ? money(variant.price) : '' }}</div>

          <div class="eyebrow">Size</div>
          <div class="sizes">
            <button v-for="v in product.variants" :key="v.id" class="size" :class="{ on: v.id === variantId }"
              :disabled="v.stock < 1" @click="variantId = v.id; qty = 1">{{ v.size_ml }}ml</button>
          </div>

          <p v-if="variant && variant.stock > 0 && variant.stock <= 5" class="muted">Only {{ variant.stock }} left</p>

          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <div class="qty">
              <button aria-label="Decrease" @click="qty = Math.max(1, qty - 1)">−</button>
              <span>{{ qty }}</span>
              <button aria-label="Increase" @click="qty = Math.min(qty + 1, variant?.stock || 1, 10)">+</button>
            </div>
            <button class="btn" :disabled="!variant || variant.stock < 1" @click="addToBag">
              {{ variant && variant.stock < 1 ? 'Sold out' : 'Add to bag' }}
            </button>
            <router-link v-if="added" to="/cart" class="linkbtn">Added - view bag →</router-link>
          </div>

          <div class="notes">
            <div class="note"><b>Top</b>{{ product.notes_top }}</div>
            <div class="note"><b>Heart</b>{{ product.notes_heart }}</div>
            <div class="note"><b>Base</b>{{ product.notes_base }}</div>
          </div>
        </div>
      </div>

      <section style="margin-top:72px;max-width:760px">
        <h2>Reviews</h2>
        <p v-if="!product.reviews.length" class="muted">No reviews yet - be the first.</p>
        <div v-for="r in product.reviews" :key="r.id" class="review">
          <span class="stars">{{ '★'.repeat(r.rating) }}</span> <strong>{{ r.title }}</strong>
          <p class="muted" style="margin:4px 0">{{ r.user?.name }}</p>
          <p>{{ r.body }}</p>
        </div>

        <form v-if="auth.user" style="margin-top:24px" @submit.prevent="submitReview">
          <h3>Write a review</h3>
          <div v-if="reviewMsg" class="alert alert--ok">{{ reviewMsg }}</div>
          <div v-if="reviewErr" class="alert">{{ reviewErr }}</div>
          <div class="field"><label>Rating</label>
            <select v-model.number="review.rating" class="input"><option v-for="n in [5,4,3,2,1]" :key="n" :value="n">{{ n }} star{{ n > 1 ? 's' : '' }}</option></select>
          </div>
          <div class="field"><label>Title</label><input v-model="review.title" class="input" maxlength="120" /></div>
          <div class="field"><label>Your review</label><textarea v-model="review.body" class="input" rows="4" maxlength="2000" /></div>
          <button class="btn">Submit review</button>
        </form>
        <p v-else><router-link :to="{ name: 'login', query: { redirect: route.fullPath } }" class="linkbtn">Sign in</router-link> to write a review.</p>
      </section>

      <section v-if="related.length" style="margin-top:72px">
        <h2>You may also like</h2>
        <div class="grid"><ProductCard v-for="p in related" :key="p.id" :product="p" /></div>
      </section>
    </template>
  </div>
</template>
