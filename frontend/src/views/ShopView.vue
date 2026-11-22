<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api/http'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const router = useRouter()

const filtersData = ref({ categories: [], brands: [], genders: [] })
const products = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')

// Filters live in the URL so results are shareable and the back button works.
const f = reactive({ q: '', category: '', brand: '', gender: '', featured: '', min_price: '', max_price: '', sort: 'newest', page: 1 })

function readQuery() {
  Object.keys(f).forEach((k) => { f[k] = route.query[k] ?? (k === 'sort' ? 'newest' : k === 'page' ? 1 : '') })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await api('/products', { params: { ...f, per_page: 12 } })
    products.value = res.data
    meta.value = res
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function apply(patch = {}) {
  const next = { ...f, ...patch }
  if (!('page' in patch)) next.page = 1
  const query = {}
  Object.entries(next).forEach(([k, v]) => { if (v !== '' && !(k === 'sort' && v === 'newest') && !(k === 'page' && Number(v) === 1)) query[k] = v })
  router.push({ name: 'shop', query })
}

function clear() { router.push({ name: 'shop' }) }

watch(() => route.query, () => { readQuery(); load() })
onMounted(async () => {
  readQuery()
  load()
  filtersData.value = await api('/filters')
})
</script>

<template>
  <div class="container section">
    <h1>{{ f.q ? `Results for “${f.q}”` : 'All Fragrances' }}</h1>

    <div class="shop">
      <aside class="filters">
        <button class="linkbtn muted" @click="clear">Clear all</button>

        <h4>Category</h4>
        <label v-for="c in filtersData.categories" :key="c.id">
          <input type="radio" name="cat" :checked="f.category === c.slug" @change="apply({ category: c.slug })" /> {{ c.name }}
        </label>

        <h4>Brand</h4>
        <label v-for="b in filtersData.brands" :key="b.id">
          <input type="radio" name="brand" :checked="f.brand === b.slug" @change="apply({ brand: b.slug })" /> {{ b.name }}
        </label>

        <h4>For</h4>
        <label v-for="g in filtersData.genders" :key="g" style="text-transform:capitalize">
          <input type="radio" name="gender" :checked="f.gender === g" @change="apply({ gender: g })" /> {{ g }}
        </label>

        <h4>Price (£)</h4>
        <div class="row">
          <input class="input" type="number" min="0" placeholder="Min" :value="f.min_price" @change="apply({ min_price: $event.target.value })" />
          <input class="input" type="number" min="0" placeholder="Max" :value="f.max_price" @change="apply({ max_price: $event.target.value })" />
        </div>
      </aside>

      <section>
        <div class="toolbar">
          <span class="muted">{{ meta.total }} fragrance{{ meta.total === 1 ? '' : 's' }}</span>
          <select class="input" style="width:auto" :value="f.sort" @change="apply({ sort: $event.target.value })" aria-label="Sort">
            <option value="newest">Newest</option>
            <option value="price_asc">Price: low to high</option>
            <option value="price_desc">Price: high to low</option>
            <option value="name">Name A–Z</option>
          </select>
        </div>

        <div v-if="error" class="alert">{{ error }}</div>
        <div v-else-if="loading" class="grid"><div v-for="n in 6" :key="n" class="skeleton" /></div>
        <p v-else-if="!products.length" class="muted">No fragrances match those filters. <button class="linkbtn" @click="clear">Clear filters</button></p>
        <div v-else class="grid">
          <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>

        <div v-if="meta.last_page > 1" class="pager">
          <button :disabled="meta.current_page <= 1" @click="apply({ page: meta.current_page - 1 })">‹</button>
          <button v-for="n in meta.last_page" :key="n" :class="{ active: n === meta.current_page }" @click="apply({ page: n })">{{ n }}</button>
          <button :disabled="meta.current_page >= meta.last_page" @click="apply({ page: meta.current_page + 1 })">›</button>
        </div>
      </section>
    </div>
  </div>
</template>
