<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { money, dateShort } from '@/utils/format'
import ProductCard from '@/components/ProductCard.vue'

const auth = useAuthStore()
const tab = ref('orders')
const orders = ref([])
const wishlist = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const [o, w] = await Promise.all([api('/orders'), api('/wishlist')])
    orders.value = o.data
    wishlist.value = w
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="container section">
    <h1>Hello, {{ auth.user.name }}</h1>
    <p class="muted">{{ auth.user.email }}</p>

    <div class="tabs">
      <button :class="{ on: tab === 'orders' }" @click="tab = 'orders'">Orders</button>
      <button :class="{ on: tab === 'wishlist' }" @click="tab = 'wishlist'">Wishlist ({{ wishlist.length }})</button>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>
    <p v-else-if="loading" class="muted">Loading…</p>

    <template v-else-if="tab === 'orders'">
      <p v-if="!orders.length" class="muted">You haven't placed any orders yet.</p>
      <table v-else class="table">
        <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>{{ o.number }}</td>
            <td>{{ dateShort(o.created_at) }}</td>
            <td>{{ o.items.map((i) => `${i.name} ${i.size_ml}ml × ${i.quantity}`).join(', ') }}</td>
            <td><span class="pill" :class="`pill--${o.status}`">{{ o.status }}</span><div v-if="o.tracking_number" class="muted">Tracking: {{ o.tracking_number }}</div></td>
            <td style="text-align:right">{{ money(o.total) }}</td>
          </tr>
        </tbody>
      </table>
    </template>

    <template v-else>
      <p v-if="!wishlist.length" class="muted">Tap the heart on any fragrance to save it here.</p>
      <div v-else class="grid"><ProductCard v-for="p in wishlist" :key="p.id" :product="p" /></div>
    </template>
  </div>
</template>
