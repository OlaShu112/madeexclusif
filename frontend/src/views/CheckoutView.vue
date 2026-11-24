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

  shipping_address: {
    name: auth.user?.name || '',
    phone_code: '+44',
    phone: '',
    address: '',
    apartment: '',
    city: '',
    postcode: '',
    country: ''
  }
})

const busy = ref(false)
const error = ref('')

async function pay() {
  if (busy.value) return

  busy.value = true
  error.value = ''

  try {
    if (!cart.items.length) {
      throw new Error('Your shopping bag is empty.')
    }

    const address = form.shipping_address

    const payload = {
      email: form.email.trim(),

      shipping_address: {
        name: address.name.trim(),
        phone_code: address.phone_code.trim(),
        phone: address.phone.trim(),
        address: address.address.trim(),
        apartment: address.apartment.trim(),
        city: address.city.trim(),
        postcode: address.postcode.trim(),
        country: address.country.trim()
      },

      coupon_code: cart.coupon?.code || null,

      items: cart.items.map(item => ({
        product_variant_id: Number(item.variantId),
        quantity: Number(item.quantity)
      }))
    }

    const res = await api('/orders', {
      method: 'POST',
      body: payload
    })

    if (!res?.checkout_url) {
      throw new Error(
        'Payment could not be started. Please try again.'
      )
    }

    // Stripe handles card payment securely.
    // Do not clear the cart before payment succeeds.
    window.location.assign(res.checkout_url)

  } catch (e) {
    error.value =
      e?.message ||
      'Unable to start payment. Please try again later.'

    busy.value = false
  }
}
</script>

<template>
  <div class="container section">
    <h1>Checkout</h1>

    <div v-if="!cart.items.length" class="center">
      <p class="muted">Your bag is empty.</p>

      <router-link class="btn" to="/shop">
        Browse fragrances
      </router-link>
    </div>

    <form
      v-else
      class="two-col"
      @submit.prevent="pay"
    >
      <div>
        <div
          v-if="error"
          class="alert"
          role="alert"
        >
          {{ error }}
        </div>

        <!-- CONTACT -->
        <h3>Contact</h3>

        <div class="field">
          <label for="email">Email address</label>

          <input
            id="email"
            v-model.trim="form.email"
            type="email"
            class="input"
            autocomplete="email"
            required
          />
        </div>

        <p v-if="!auth.user" class="muted">
          <router-link
            :to="{
              name: 'login',
              query: { redirect: '/checkout' }
            }"
            class="linkbtn"
          >
            Sign in
          </router-link>

          to save your order history, or continue as a guest.
        </p>

        <!-- DELIVERY ADDRESS -->
        <h3 style="margin-top:28px">
          Delivery address
        </h3>

        <div class="field">
          <label for="full-name">Full name</label>

          <input
            id="full-name"
            v-model.trim="form.shipping_address.name"
            type="text"
            class="input"
            autocomplete="name"
            placeholder="Enter your full name"
            required
          />
        </div>

        <!-- INTERNATIONAL PHONE -->
        <div class="row">
          <div class="field">
            <label for="phone-code">
              Dialling code
            </label>

            <input
              id="phone-code"
              v-model.trim="form.shipping_address.phone_code"
              type="tel"
              class="input"
              inputmode="tel"
              placeholder="+44"
              pattern="\+[1-9][0-9]{0,3}"
              title="Enter a dialling code such as +44 or +234"
              required
            />
          </div>

          <div class="field">
            <label for="phone">
              Phone number
            </label>

            <input
              id="phone"
              v-model.trim="form.shipping_address.phone"
              type="tel"
              class="input"
              autocomplete="tel-national"
              placeholder="7700 900123"
              required
            />
          </div>
        </div>

        <!-- STREET ADDRESS -->
        <div class="field">
          <label for="address">
            Street address
          </label>

          <input
            id="address"
            v-model.trim="form.shipping_address.address"
            type="text"
            class="input"
            autocomplete="address-line1"
            placeholder="House number and street name"
            required
          />
        </div>

        <!-- APARTMENT -->
        <div class="field">
          <label for="apartment">
            Apartment, suite (optional)
          </label>

          <input
            id="apartment"
            v-model.trim="form.shipping_address.apartment"
            type="text"
            class="input"
            autocomplete="address-line2"
            placeholder="Apartment, suite or building"
          />
        </div>

        <!-- CITY AND POSTCODE -->
        <div class="row">
          <div class="field">
            <label for="city">City</label>

            <input
              id="city"
              v-model.trim="form.shipping_address.city"
              type="text"
              class="input"
              autocomplete="address-level2"
              required
            />
          </div>

          <div class="field">
            <label for="postcode">
              Postcode / ZIP code
            </label>

            <input
              id="postcode"
              v-model.trim="form.shipping_address.postcode"
              type="text"
              class="input"
              autocomplete="postal-code"
            />
          </div>
        </div>

        <!-- COUNTRY -->
        <div class="field">
          <label for="country">
            Country
          </label>

          <input
            id="country"
            v-model.trim="form.shipping_address.country"
            type="text"
            class="input"
            autocomplete="country-name"
            placeholder="Enter your country"
            required
          />

          <p class="muted" style="font-size:12px">
            Please enter your delivery country.
            International delivery charges may vary.
          </p>
        </div>

        <!-- PAYMENT -->
        <h3 style="margin-top:28px">
          Payment
        </h3>

        <p class="muted">
          You'll enter your card details on Stripe's
          secure payment page in the next step.
          We never see or store your card number.
        </p>
      </div>

      <!-- ORDER SUMMARY -->
      <aside class="summary">
        <h3>Order summary</h3>

        <div
          v-for="item in cart.items"
          :key="item.variantId"
          style="
            display:flex;
            justify-content:space-between;
            gap:12px;
            padding:6px 0;
          "
        >
          <span>
            {{ item.name }}
            {{ item.sizeMl }}ml
            × {{ item.quantity }}
          </span>

          <span>
            {{ money(item.price * item.quantity) }}
          </span>
        </div>

        <hr
          style="
            border:0;
            border-top:1px solid #cfcac2;
            margin:14px 0;
          "
        />

        <dl>
          <dt>Subtotal</dt>
          <dd>{{ money(cart.subtotal) }}</dd>

          <template v-if="cart.discount">
            <dt>
              Discount ({{ cart.coupon?.code }})
            </dt>

            <dd>
              −{{ money(cart.discount) }}
            </dd>
          </template>

          <dt>Delivery</dt>

          <dd>
            {{ cart.shipping ? money(cart.shipping) : 'Free' }}
          </dd>
        </dl>

        <dl class="grand">
          <dt>Total</dt>

          <dd>
            <strong>
              {{ money(cart.total) }}
            </strong>
          </dd>
        </dl>

        <button
          type="submit"
          class="btn btn--block"
          :disabled="busy || !cart.items.length"
        >
          {{
            busy
              ? 'Redirecting to Stripe…'
              : 'Pay securely with Stripe'
          }}
        </button>

        <p
          class="muted"
          style="font-size:12px;margin-top:10px"
        >
          Final prices, delivery charges and stock
          are confirmed by our server before payment.
        </p>
      </aside>
    </form>
  </div>
</template>
