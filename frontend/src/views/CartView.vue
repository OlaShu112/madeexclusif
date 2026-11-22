<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { money } from '@/utils/format'
import ProductImage from '@/components/ProductImage.vue'

const cart = useCartStore()
const route = useRoute()
const code = ref('')
const msg = ref('')
const err = ref('')

async function applyCoupon() {
  msg.value = err.value = ''
  try {
    const res = await api('/coupons/check', { method: 'POST', body: { code: code.value, subtotal: cart.subtotal } })
    cart.setCoupon({ code: res.code, discount: res.discount })
    msg.value = `Code ${res.code} applied.`
  } catch (e) {
    err.value = e.message
  }
}
</script>

<template>
  <div class="container section">
    <h1>Your bag</h1>
    <div v-if="route.query.cancelled" class="alert">Payment was cancelled - your bag is still here whenever you're ready.</div>

    <div v-if="!cart.items.length" class="center" style="padding:48px 0">
      <p class="muted">Your bag is empty.</p>
      <router-link class="btn" to="/shop">Browse fragrances</router-link>
    </div>

    <div v-else class="two-col">
      <div>
        <div v-for="i in cart.items" :key="i.variantId" class="line">
          <div class="line__thumb"><ProductImage :src="i.image" :accent="i.accent" :label="i.name" /></div>
          <div>
            <router-link :to="{ name: 'product', params: { slug: i.slug } }"><strong>{{ i.name }}</strong></router-link>
            <div class="muted">{{ i.brand }} · {{ i.sizeMl }}ml</div>
            <div class="qty" style="margin-top:8px">
              <button aria-label="Decrease" @click="cart.setQuantity(i.variantId, i.quantity - 1)">−</button>
              <span>{{ i.quantity }}</span>
              <button aria-label="Increase" @click="cart.setQuantity(i.variantId, i.quantity + 1)">+</button>
            </div>
          </div>
          <div style="text-align:right">
            <div>{{ money(i.price * i.quantity) }}</div>
            <button class="linkbtn muted" @click="cart.remove(i.variantId)">Remove</button>
          </div>
        </div>
      </div>

      <aside class="summary">
        <div v-if="cart.freeShippingRemaining > 0" class="muted">
          Add {{ money(cart.freeShippingRemaining) }} more for free delivery
          <div class="progress"><i :style="{ width: Math.min(100, (1 - cart.freeShippingRemaining / 100) * 100) + '%' }" /></div>
        </div>
        <div v-else class="alert alert--ok">You've unlocked free delivery.</div>

        <div style="display:flex;gap:8px;margin-bottom:8px">
          <input v-model="code" class="input" placeholder="Discount code" aria-label="Discount code" @keyup.enter="applyCoupon" />
          <button class="btn btn--ghost" @click="applyCoupon">Apply</button>
        </div>
        <div v-if="msg" class="alert alert--ok">{{ msg }}</div>
        <div v-if="err" class="alert">{{ err }}</div>

        <dl>
          <dt>Subtotal</dt><dd>{{ money(cart.subtotal) }}</dd>
          <template v-if="cart.discount"><dt>Discount</dt><dd>−{{ money(cart.discount) }}</dd></template>
          <dt>Delivery</dt><dd>{{ cart.shipping ? money(cart.shipping) : 'Free' }}</dd>
        </dl>
        <dl class="grand"><dt>Total</dt><dd><strong>{{ money(cart.total) }}</strong></dd></dl>
        <router-link class="btn btn--block" to="/checkout">Checkout</router-link>
      </aside>
    </div>
  </div>
</template>