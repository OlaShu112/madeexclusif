<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { money } from '@/utils/format'

const route = useRoute()
const cart = useCartStore()

const order = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  const sessionId = route.query.session_id
  if (!sessionId) {
    loading.value = false
    return
  }
  try {
    order.value = await api('/checkout/confirm', { params: { session_id: sessionId } })
    // Only empty the bag once Stripe confirms the money was taken.
    if (order.value.payment_status === 'paid') cart.clear()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="container section" style="max-width:720px">
    <p v-if="loading" class="muted">Confirming your payment…</p>

    <template v-else-if="order && order.payment_status === 'paid'">
      <div class="eyebrow">Thank you</div>
      <h1>Payment received</h1>
      <p>Order <strong>{{ order.number }}</strong> is confirmed. A receipt has been sent to {{ order.email }} by Stripe.</p>

      <table class="table">
        <tbody>
          <tr v-for="i in order.items" :key="i.id"><td>{{ i.name }} {{ i.size_ml }}ml × {{ i.quantity }}</td><td style="text-align:right">{{ money(i.line_total) }}</td></tr>
          <tr><td class="muted">Delivery</td><td style="text-align:right">{{ Number(order.shipping) ? money(order.shipping) : 'Free' }}</td></tr>
          <tr v-if="Number(order.discount)"><td class="muted">Discount</td><td style="text-align:right">−{{ money(order.discount) }}</td></tr>
          <tr><td><strong>Total paid</strong></td><td style="text-align:right"><strong>{{ money(order.total) }}</strong></td></tr>
        </tbody>
      </table>
      <p style="margin-top:24px"><router-link class="btn" to="/shop">Continue shopping</router-link></p>
    </template>

    <template v-else-if="order">
      <h1>Payment pending</h1>
      <p>We haven't received confirmation of payment for order <strong>{{ order.number }}</strong> yet. If you were charged, it will update shortly - check your account's order list in a few minutes.</p>
      <router-link class="btn btn--ghost" to="/account">View my orders</router-link>
    </template>

    <template v-else>
      <h1>{{ error ? 'We couldn\'t confirm that order' : 'No recent order' }}</h1>
      <p v-if="error" class="alert">{{ error }}</p>
      <router-link class="btn" to="/shop">Browse fragrances</router-link>
    </template>
  </div>
</template>