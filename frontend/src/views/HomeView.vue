<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api/http'
import ProductCard from '@/components/ProductCard.vue'

const featured = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await api('/products', { params: { featured: 1, per_page: 3 } })
    featured.value = res.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="hero">
    <div class="container">
      <div class="hero__copy">
        <div class="hero__stars">★★★★★ &nbsp;Loved by thousands of customers</div>
        <h1>Your signature scent, made exclusive</h1>
        <p>Small-batch fragrances composed with rare materials and a high concentration of pure perfume oil. Made to linger.</p>
        <div class="hero__cta">
          <router-link class="btn btn--light" :to="{ name: 'shop' }">Shop Fragrances →</router-link>
          <router-link class="btn btn--light" :to="{ name: 'shop', query: { featured: 1 } }">Bestsellers →</router-link>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container split">
      <div>
        <h2>Experience the next era of fragrance</h2>
        <h3>Composed in small batches</h3>
        <p class="muted">MadeExlusif blends rare florals, resins and woods into perfumes that evolve on the skin from first spray to last trace. Every bottle is numbered and quality-checked before it ships.</p>
        <router-link class="btn btn--ghost" :to="{ name: 'shop' }">Discover the collection</router-link>
      </div>
      <div class="grid grid--3">
        <template v-if="loading"><div v-for="n in 3" :key="n" class="skeleton" /></template>
        <ProductCard v-for="p in featured" v-else :key="p.id" :product="p" />
      </div>
    </div>
  </section>

  <div class="container">
    <div class="values">
      <div><b>Small batch</b><span class="muted">Numbered, hand-checked bottles</span></div>
      <div><b>Parfum strength</b><span class="muted">Up to 25% pure perfume oil</span></div>
      <div><b>Free delivery</b><span class="muted">On UK orders over £100</span></div>
      <div><b>Easy returns</b><span class="muted">Unopened within 30 days</span></div>
    </div>
  </div>
</template>
