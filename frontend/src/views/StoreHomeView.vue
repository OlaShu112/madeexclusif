<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useStorefrontStore } from '@/stores/storefront'
import { api } from '@/api/http'

const route = useRoute()
const storefront = useStorefrontStore()

const products = ref([])
const categories = ref([])

const loading = ref(false)
const productError = ref('')
const categoryError = ref('')

const slug = computed(
  () => String(route.params.store || '')
)

const store = computed(() =>
  storefront.store?.slug === slug.value
    ? storefront.store
    : null
)

const theme = computed(
  () => store.value?.website_theme || {}
)

const sections = computed(() => {
  const defaults = [
    {
      type: 'hero',
      enabled: true,
      position: 1
    },
    {
      type: 'categories',
      title: 'Shop by Category',
      enabled: true,
      position: 2
    },
    {
      type: 'featured_products',
      title: 'Featured Products',
      enabled: true,
      position: 3
    },
    {
      type: 'about_business',
      title: 'About Us',
      enabled: true,
      position: 4
    }
  ]

  const configured = theme.value.homepage_sections

  return (
    Array.isArray(configured)
      ? configured
      : defaults
  )
    .filter(section => section.enabled !== false)
    .sort((a, b) => a.position - b.position)
})

const shopLink = computed(() => ({
  name: 'store-shop',
  params: {
    store: slug.value
  }
}))

function categoryLink(category) {
  return {
    ...shopLink.value,
    query: {
      category: category.slug
    }
  }
}

function productLink(product) {
  return {
    name: 'store-product',
    params: {
      store: slug.value,
      slug: product.slug
    }
  }
}

watch(
  slug,
  async (value, _, onCleanup) => {
    let cancelled = false

    onCleanup(() => {
      cancelled = true
    })

    products.value = []
    categories.value = []
    productError.value = ''
    categoryError.value = ''

    if (!value) return

    loading.value = true

    const results = await Promise.allSettled([
      api('/products', {
        params: {
          store: value,
          featured: 1,
          per_page: 8
        }
      }),

      api('/filters', {
        params: {
          store: value
        }
      })
    ])

    if (cancelled) return

    if (results[0].status === 'fulfilled') {
      products.value =
        results[0].value.data || []
    } else {
      productError.value =
        'Unable to load featured products.'
    }

    if (results[1].status === 'fulfilled') {
      categories.value =
        results[1].value.categories || []
    } else {
      categoryError.value =
        'Unable to load product categories.'
    }

    loading.value = false
  },
  { immediate: true }
)
</script>

<template>
  <main v-if="store" class="store-home">

    <template
      v-for="section in sections"
      :key="section.type"
    >

      <section
        v-if="section.type === 'hero'"
        class="store-hero"
      >

        <img
          v-if="store.logo_url"
          :src="store.logo_url"
          :alt="store.name"
          class="hero-logo"
        />

        <p class="eyebrow">
          Welcome to {{ store.name }}
        </p>

        <h1>
          {{ theme.hero_title || store.name }}
        </h1>

        <p class="hero-description">
          {{
            theme.hero_subtitle ||
            store.tagline ||
            'Discover our latest collection.'
          }}
        </p>

        <RouterLink
          :to="shopLink"
          class="primary-button"
        >
          {{
            theme.hero_button_text ||
            'Shop Collection'
          }}
        </RouterLink>

      </section>

      <section
        v-else-if="section.type === 'categories'"
        class="homepage-section"
      >

        <div class="section-heading">
          <h2>
            {{ section.title || 'Shop by Category' }}
          </h2>

          <RouterLink :to="shopLink">
            View All
          </RouterLink>
        </div>

        <p v-if="loading">Loading categories...</p>

        <p v-else-if="categoryError">
          {{ categoryError }}
        </p>

        <div
          v-else-if="categories.length"
          class="category-grid"
        >

          <RouterLink
            v-for="category in categories"
            :key="category.id"
            :to="categoryLink(category)"
            class="category-card"
          >
            <h3>{{ category.name }}</h3>
            <span>Explore Collection</span>
          </RouterLink>

        </div>

        <p v-else>
          Product categories will appear here.
        </p>

      </section>

      <section
        v-else-if="section.type === 'featured_products'"
        class="homepage-section"
      >

        <div class="section-heading">
          <h2>
            {{ section.title || 'Featured Products' }}
          </h2>

          <RouterLink :to="shopLink">
            View All
          </RouterLink>
        </div>

        <p v-if="loading">Loading products...</p>

        <p v-else-if="productError">
          {{ productError }}
        </p>

        <div
          v-else-if="products.length"
          class="product-grid"
        >

          <RouterLink
            v-for="product in products"
            :key="product.id"
            :to="productLink(product)"
            class="product-card"
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

          </RouterLink>

        </div>

        <p v-else>
          No featured products are available yet.
        </p>

      </section>

      <section
        v-else-if="section.type === 'about_business'"
        class="about-section"
      >
        <h2>{{ section.title || 'About Us' }}</h2>

        <p>
          {{
            theme.footer_description ||
            store.tagline ||
            `Welcome to ${store.name}.`
          }}
        </p>
      </section>

    </template>
  </main>
</template>

<style scoped>
.store-home {
  max-width: 1440px;
  margin: auto;
  padding: 30px 24px 65px;
}

.store-hero {
  padding: 90px 25px;
  text-align: center;
  background: var(--store-secondary);
  color: var(--store-secondary-text);
  border-radius: 10px;
}

.hero-logo {
  max-width: 140px;
  max-height: 100px;
  object-fit: contain;
  margin-bottom: 20px;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 3px;
  font-size: 12px;
}

.store-hero h1 {
  font-size: clamp(32px, 5vw, 65px);
  margin: 20px 0;
  color: inherit;
}

.hero-description {
  max-width: 700px;
  margin: auto;
  line-height: 1.8;
  white-space: pre-wrap;
}

.primary-button {
  display: inline-block;
  padding: 15px 32px;
  margin-top: 28px;
  background: var(--store-primary);
  color: var(--store-primary-text);
  border-radius: 5px;
  text-decoration: none;
}

.homepage-section {
  margin: 60px 0;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 25px;
}

.section-heading a {
  color: inherit;
}

.category-grid,
.product-grid {
  display: grid;
  grid-template-columns:
    repeat(4, minmax(0, 1fr));
  gap: 22px;
}

.category-card {
  padding: 40px 20px;
  background: var(--store-secondary);
  color: var(--store-secondary-text);
  border-radius: 8px;
  text-decoration: none;
  text-align: center;
}

.category-card h3 {
  color: inherit;
}

.category-card span {
  display: block;
  margin-top: 12px;
  font-size: 13px;
}

.product-card {
  padding: 12px;
  border: 1px solid #dddddd;
  border-radius: 8px;
  background: var(--store-background);
  color: var(--store-text);
  text-decoration: none;
}

.product-image {
  aspect-ratio: 1;
  background: #f5f5f5;
  color: #222222;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.product-card h3 {
  margin: 15px 0 8px;
  color: inherit;
}

.about-section {
  margin-top: 60px;
  padding: 50px 25px;
  text-align: center;
  background: var(--store-secondary);
  color: var(--store-secondary-text);
  border-radius: 8px;
}

.about-section p {
  max-width: 800px;
  margin: 20px auto 0;
  line-height: 1.8;
  white-space: pre-wrap;
}

@media (max-width: 900px) {
  .category-grid,
  .product-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .category-grid,
  .product-grid {
    grid-template-columns: 1fr;
  }

  .store-hero {
    padding: 55px 20px;
  }
}
</style>