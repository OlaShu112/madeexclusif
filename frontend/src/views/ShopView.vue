<script setup>
import {
  ref,
  reactive,
  computed,
  watch
} from 'vue'

import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api/http'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const router = useRouter()

const storeSlug = computed(
  () => String(route.params.store || '')
)

const isIndependentStore = computed(
  () => Boolean(storeSlug.value)
)

const productLabel = computed(
  () => isIndependentStore.value
    ? 'product'
    : 'fragrance'
)

const filtersData = ref({
  categories: [],
  brands: [],
  genders: []
})

const products = ref([])

const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

const loading = ref(true)
const error = ref('')
const filterError = ref('')

let requestVersion = 0
let filterVersion = 0

const f = reactive({
  q: '',
  category: '',
  brand: '',
  gender: '',
  featured: '',
  min_price: '',
  max_price: '',
  sort: 'newest',
  page: 1
})

function readQuery() {
  Object.keys(f).forEach(key => {
    const fallback =
      key === 'sort'
        ? 'newest'
        : key === 'page'
          ? 1
          : ''

    const value = route.query[key]

    f[key] = Array.isArray(value)
      ? value[0] ?? fallback
      : value ?? fallback
  })
}

function shopRoute(query = {}) {
  return isIndependentStore.value
    ? {
        name: 'store-shop',
        params: {
          store: storeSlug.value
        },
        query
      }
    : {
        name: 'shop',
        query
      }
}

async function load() {
  const version = ++requestVersion

  loading.value = true
  error.value = ''
  products.value = []

  try {
    const params = {
      ...f,
      per_page: 12
    }

    if (storeSlug.value) {
      params.store = storeSlug.value
    }

    const response = await api('/products', {
      params
    })

    if (version !== requestVersion) return

    products.value = response.data || []
    meta.value = response

  } catch (err) {
    if (version !== requestVersion) return

    error.value =
      err?.message || 'Unable to load products.'

  } finally {
    if (version === requestVersion) {
      loading.value = false
    }
  }
}

async function loadFilters() {
  const version = ++filterVersion

  filterError.value = ''

  filtersData.value = {
    categories: [],
    brands: [],
    genders: []
  }

  try {
    const response = await api('/filters', {
      params: storeSlug.value
        ? { store: storeSlug.value }
        : {}
    })

    if (version !== filterVersion) return

    filtersData.value = response

  } catch (err) {
    if (version !== filterVersion) return

    filterError.value =
      err?.message || 'Unable to load filters.'
  }
}

function apply(patch = {}) {
  const next = {
    ...f,
    ...patch
  }

  if (!('page' in patch)) {
    next.page = 1
  }

  const query = {}

  Object.entries(next).forEach(([key, value]) => {
    if (
      value !== '' &&
      !(key === 'sort' && value === 'newest') &&
      !(key === 'page' && Number(value) === 1)
    ) {
      query[key] = value
    }
  })

  router.push(shopRoute(query))
}

function clear() {
  router.push(shopRoute())
}

function productLink(product) {
  return {
    name: 'store-product',
    params: {
      store: storeSlug.value,
      slug: product.slug
    }
  }
}

watch(
  () => route.fullPath,
  () => {
    readQuery()
    load()
  },
  { immediate: true }
)

watch(
  storeSlug,
  loadFilters,
  { immediate: true }
)
</script>

<template>
  <div class="container section">

    <h1>
      {{
        f.q
          ? `Results for “${f.q}”`
          : isIndependentStore
            ? 'Shop All Products'
            : 'All Fragrances'
      }}
    </h1>

    <div class="shop">

      <aside class="filters">

        <button
          class="linkbtn muted"
          @click="clear"
        >
          Clear all
        </button>

        <p v-if="filterError" role="alert">
          {{ filterError }}
        </p>

        <h4>Category</h4>

        <label
          v-for="category in filtersData.categories"
          :key="category.id"
        >
          <input
            type="radio"
            name="category"
            :checked="f.category === category.slug"
            @change="apply({
              category: category.slug
            })"
          />

          {{ category.name }}
        </label>

        <h4 v-if="filtersData.brands?.length">
          Brand
        </h4>

        <label
          v-for="brand in filtersData.brands"
          :key="brand.id"
        >
          <input
            type="radio"
            name="brand"
            :checked="f.brand === brand.slug"
            @change="apply({
              brand: brand.slug
            })"
          />

          {{ brand.name }}
        </label>

        <template v-if="filtersData.genders?.length">

          <h4>For</h4>

          <label
            v-for="gender in filtersData.genders"
            :key="gender"
          >
            <input
              type="radio"
              name="gender"
              :checked="f.gender === gender"
              @change="apply({ gender })"
            />

            {{ gender }}
          </label>

        </template>

        <h4>Price (£)</h4>

        <div class="row">

          <input
            class="input"
            type="number"
            min="0"
            placeholder="Min"
            aria-label="Minimum price"
            :value="f.min_price"
            @change="apply({
              min_price: $event.target.value
            })"
          />

          <input
            class="input"
            type="number"
            min="0"
            placeholder="Max"
            aria-label="Maximum price"
            :value="f.max_price"
            @change="apply({
              max_price: $event.target.value
            })"
          />

        </div>
      </aside>

      <section>

        <div class="toolbar">

          <span class="muted">
            {{ meta.total }}
            {{ productLabel }}{{ meta.total === 1 ? '' : 's' }}
          </span>

          <select
            class="input"
            style="width: auto"
            :value="f.sort"
            aria-label="Sort products"
            @change="apply({
              sort: $event.target.value
            })"
          >
            <option value="newest">Newest</option>
            <option value="price_asc">
              Price: low to high
            </option>
            <option value="price_desc">
              Price: high to low
            </option>
            <option value="name">Name A–Z</option>
          </select>

        </div>

        <div
          v-if="error"
          class="alert"
          role="alert"
        >
          {{ error }}
        </div>

        <div v-else-if="loading" class="grid">

          <div
            v-for="n in 6"
            :key="n"
            class="skeleton"
          />

        </div>

        <p v-else-if="!products.length" class="muted">
          No {{ productLabel }}s match those filters.

          <button
            class="linkbtn"
            @click="clear"
          >
            Clear filters
          </button>
        </p>

        <div
          v-else-if="isIndependentStore"
          class="tenant-product-grid"
        >

          <RouterLink
            v-for="product in products"
            :key="product.id"
            :to="productLink(product)"
            class="tenant-product-card"
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

        <div v-else class="grid">

          <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
          />

        </div>

        <div
          v-if="!loading && !error && meta.last_page > 1"
          class="pager"
        >
          <button
            :disabled="meta.current_page <= 1"
            @click="apply({
              page: meta.current_page - 1
            })"
          >
            Previous
          </button>

          <span>
            Page {{ meta.current_page }}
            of {{ meta.last_page }}
          </span>

          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="apply({
              page: meta.current_page + 1
            })"
          >
            Next
          </button>
        </div>

      </section>
    </div>
  </div>
</template>

<style scoped>
.tenant-product-grid {
  display: grid;
  grid-template-columns:
    repeat(3, minmax(0, 1fr));
  gap: 22px;
}

.tenant-product-card {
  display: block;
  border: 1px solid #dddddd;
  border-radius: 8px;
  padding: 12px;
  color: var(--store-text, #222222);
  background: var(--store-background, #ffffff);
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

.tenant-product-card h3 {
  margin: 14px 0 8px;
  font-size: 16px;
  color: inherit;
}

.tenant-product-card p {
  color: inherit;
}

@media (max-width: 1000px) {
  .tenant-product-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .tenant-product-grid {
    grid-template-columns: 1fr;
  }
}
</style>