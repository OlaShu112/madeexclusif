<script setup>
import { ref, reactive } from 'vue'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { money } from '@/utils/format'

const cart = useCartStore()
const auth = useAuthStore()

const form = reactive({
  email: auth.user?.email || '',
  shipping_address: { full_name: auth.user?.name || '', line1: '', line2: '', city: '', postcode: '', country: 'GB' },
})
const busy = ref(false)
const error = ref('')

async function pay() {
  busy.value = true
  error.value = ''
  try {
    const res = await api('/orders', {
      method: 'POST',
      body: {
        email: form.email,
        shipping_address: form.shipping_address,
        coupon_code: cart.coupon?.code || null,
        items: cart.items.map((i) => ({ variant_id: i.variantId, quantity: i.quantity })),
      },
    })
    // Hand over to Stripe's secure payment page. The bag is only emptied after payment succeeds.
    window.location.href = res.checkout_url
  } catch (e) {
    error.value = e.message
    busy.value = false
  }
}
</script>

<template>
  <div class="container section">
    <h1>Checkout</h1>

    <div v-if="!cart.items.length" class="center"><p class="muted">Your bag is empty.</p><router-link class="btn" to="/shop">Browse fragrances</router-link></div>

    <form v-else class="two-col" @submit.prevent="pay">
      <div>
        <div v-if="error" class="alert">{{ error }}</div>

        <h3>Contact</h3>
        <div class="field"><label for="email">Email</label><input id="email" v-model="form.email" type="email" class="input" required autocomplete="email" /></div>
        <p v-if="!auth.user" class="muted"><router-link :to="{ name: 'login', query: { redirect: '/checkout' } }" class="linkbtn">Sign in</router-link> to save your order history, or continue as a guest.</p>

        <h3 style="margin-top:28px">Delivery address</h3>
        <div class="field"><label>Full name</label><input v-model="form.shipping_address.full_name" class="input" required autocomplete="name" /></div>
        <div class="field"><label>Address</label><input v-model="form.shipping_address.line1" class="input" required autocomplete="address-line1" /></div>
        <div class="field"><label>Apartment, suite (optional)</label><input v-model="form.shipping_address.line2" class="input" autocomplete="address-line2" /></div>
        <div class="row">
          <div class="field"><label>City</label><input v-model="form.shipping_address.city" class="input" required autocomplete="address-level2" /></div>
          <div class="field"><label>Postcode</label><input v-model="form.shipping_address.postcode" class="input" required autocomplete="postal-code" /></div>
        </div>
        <div class="field"><label>Country</label>
          <select v-model="form.shipping_address.country" class="input">
            <option value="GB">United Kingdom</option><option value="IE">Ireland</option><option value="FR">France</option><option value="DE">Germany</option><option value="US">United States</option>
          </select>
        </div>

        <h3 style="margin-top:28px">Payment</h3>
        <p class="muted">You'll enter your card details on Stripe's secure payment page in the next step. We never see or store your card number.</p>
      </div>

      <aside class="summary">
        <h3>Order summary</h3>
        <div v-for="i in cart.items" :key="i.variantId" style="display:flex;justify-content:space-between;padding:6px 0">
          <span>{{ i.name }} {{ i.sizeMl }}ml × {{ i.quantity }}</span><span>{{ money(i.price * i.quantity) }}</span>
        </div>
        <hr style="border:0;border-top:1px solid #cfcac2;margin:14px 0" />
        <dl>
          <dt>Subtotal</dt><dd>{{ money(cart.subtotal) }}</dd>
          <template v-if="cart.discount"><dt>Discount ({{ cart.coupon.code }})</dt><dd>−{{ money(cart.discount) }}</dd></template>
          <dt>Delivery</dt><dd>{{ cart.shipping ? money(cart.shipping) : 'Free' }}</dd>
        </dl>
        <dl class="grand"><dt>Total</dt><dd><strong>{{ money(cart.total) }}</strong></dd></dl>
        <button class="btn btn--block" :disabled="busy">{{ busy ? 'Redirecting to Stripe…' : 'Pay securely with Stripe' }}</button>
        <p class="muted" style="font-size:12px;margin-top:10px">Final prices and stock are confirmed by our server before payment.</p>
      </aside>
    </form>
  </div>
</template>