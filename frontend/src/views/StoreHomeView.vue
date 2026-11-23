<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStorefrontStore } from '@/stores/storefront'

const route = useRoute()
const router = useRouter()
const storefront = useStorefrontStore()

const products = ref([])
const productsLoading = ref(false)
const productsError = ref('')

const slug = computed(() => String(route.params.store || ''))

const store = computed(() =>
  storefront.store?.slug === slug.value
    ? storefront.store
    : null
)

const storeName = computed(() => store.value?.name || 'Our Store')

const storeDescription = computed(() =>
  store.value?.tagline || 'Discover our latest collection.'
)

const shopLink = computed(() => ({
  name: 'store-shop',
  params: { store: slug.value },
}))

watch(
  slug,
  async (value, _, onCleanup) => {
    let cancelled = false
    onCleanup(() => { cancelled = true })

    products.value = []
    productsError.value = ''
    productsLoading.value = true

    if (!value) {
      productsLoading.value = false
      return
    }

    try {
      const { api } = await import('@/api/http')
      const result = await api(
        `/products?store=${encodeURIComponent(value)}&featured=1`
      )

      if (cancelled) return

      products.value = Array.isArray(result)
        ? result
        : Array.isArray(result?.data)
          ? result.data
          : []
    } catch (error) {
      if (!cancelled) {
        productsError.value = 'Unable to load featured products.'
      }
    } finally {
      if (!cancelled) productsLoading.value = false
    }
  },
  { immediate: true }
)

function viewProduct(product) {
  router.push({
    name: 'store-product',
    params: {
      store: slug.value,
      slug: product.slug,
    },
  })
}
</script>

<template>
  <main class="store-home container">
    <section v-if="storefront.notFound" class="store-message">
      <h1>Store not found</h1>
      <p>This store may no longer be available.</p>
      <router-link to="/">Return to Made Exclusif</router-link>
    </section>

    <section v-else>
      <div class="store-hero">
        <img
          v-if="store?.logo_url"
          :src="store.logo_url"
          :alt="storeName"
          class="store-logo"
        />

        <p class="store-eyebrow">Welcome to</p>
        <h1>{{ storeName }}</h1>
        <p>{{ storeDescription }}</p>

        <router-link :to="shopLink" class="shop-button">
          Shop Collection
        </router-link>
      </div>

      <section class="featured-section">
        <div class="section-heading">
          <h2>Featured Products</h2>
          <router-link :to="shopLink">View All</router-link>
        </div>

        <p v-if="productsLoading">Loading products...</p>

        <p v-else-if="productsError">{{ productsError }}</p>

        <div v-else-if="products.length" class="product-grid">
          <button
            v-for="product in products"
            :key="product.id"
            type="button"
            class="product-tile"
            @click="viewProduct(product)"
          >
            <div class="product-image">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                loading="lazy"
              />
              <span v-else>View Product</span>
            </div>
            <h3>{{ product.name }}</h3>
            <p v-if="product.min_price != null">
              From £{{ Number(product.min_price).toFixed(2) }}
            </p>
          </button>
        </div>

        <p v-else>No featured products are available yet.</p>
      </section>
    </section>
  </main>
</template>

<style scoped>
.store-home {
  padding-top: 32px;
  padding-bottom: 64px;
}

.store-hero {
  text-align: center;
  padding: 70px 20px;
  background: #f8f6f2;
  border-radius: 12px;
}

.store-logo {
  max-width: 130px;
  max-height: 90px;
  object-fit: contain;
  margin-bottom: 20px;
}

.store-eyebrow {
  text-transform: uppercase;
  letter-spacing: 3px;
  font-size: 12px;
  color: #777;
}

.store-hero h1 {
  font-size: clamp(30px, 5vw, 56px);
  margin: 12px 0;
}

.store-hero p {
  color: #666;
}

.shop-button {
  display: inline-block;
  margin-top: 24px;
  padding: 13px 30px;
  background: #222;
  color: white;
  text-decoration: none;
  border-radius: 5px;
}

.featured-section {
  margin-top: 55px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 25px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 22px;
}

.product-tile {
  text-align: left;
  border: 1px solid #eee;
  background: white;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
}

.product-image {
  aspect-ratio: 1;
  background: #f6f6f6;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.product-tile h3 {
  margin: 14px 0 6px;
  font-size: 15px;
}

.store-message {
  text-align: center;
  padding: 90px 20px;
}

@media (max-width: 900px) {
  .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .product-grid {
    grid-template-columns: 1fr;
  }
}
</style>

