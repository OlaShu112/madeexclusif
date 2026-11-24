<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { useCartStore } from '@/stores/cart'
import { money } from '@/utils/format'

const route = useRoute()
const cart = useCartStore()

const order = ref(null)
const loading = ref(true)
const error = ref('')
const checking = ref(false)

let attempts = 0
let timer = null
let stopped = false

const maxAttempts = 12

async function confirmPayment() {
  if (stopped || checking.value) return

  const sessionId = route.query.session_id

  if (typeof sessionId !== 'string' || !sessionId) {
    loading.value = false
    return
  }

  checking.value = true
  error.value = ''

  try {
    const response = await api('/checkout/confirm', {
      params: {
        session_id: sessionId,
      },
    })

    if (stopped) return

    order.value = {
      number:
        response.order_number ??
        response.order?.number,

      status:
        response.status ??
        response.order?.status,

      payment_status:
        response.payment_status ??
        response.order?.payment_status,

      total:
        response.order?.total,
    }

    if (order.value.payment_status === 'paid') {
      return
    }

    if (
      ['cancelled', 'refunded'].includes(
        order.value.status
      )
    ) {
      return
    }

    attempts++

    if (attempts < maxAttempts) {
      timer = setTimeout(confirmPayment, 5000)
    }
  } catch (e) {
    if (!stopped) {
      error.value =
        e.message ||
        'Unable to confirm payment at this time.'
    }
  } finally {
    if (!stopped) {
      loading.value = false
      checking.value = false
    }
  }
}

function retryConfirmation() {
  clearTimeout(timer)
  attempts = 0
  confirmPayment()
}

function continueShopping() {
  cart.clear()
}

onMounted(confirmPayment)

onBeforeUnmount(() => {
  stopped = true
  clearTimeout(timer)
})
</script>

<template>
  <div class="container section success-page">

    <div v-if="loading" class="confirmation-panel">

      <div class="spinner"></div>

      <h2>Confirming Your Payment</h2>

      <p class="muted">
        Please wait while we verify your order.
      </p>

    </div>

    <template
      v-else-if="order?.payment_status === 'paid'"
    >

      <div class="success-symbol">✓</div>

      <div class="eyebrow">
        Thank you for shopping with us
      </div>

      <h1>Payment Received</h1>

      <p>
        Your payment has been successfully
        confirmed.
      </p>

      <div class="order-information">

        <div class="information-row">
          <span>Order Number</span>
          <strong>{{ order.number }}</strong>
        </div>

        <div class="information-row">
          <span>Payment Status</span>
          <strong class="paid">Confirmed</strong>
        </div>

        <div class="information-row">
          <span>Total Paid</span>
          <strong>{{ money(order.total) }}</strong>
        </div>

      </div>

      <p>
        Your order will now be prepared
        by the relevant store.
      </p>

      <p class="muted">
        Order and payment notifications will
        be sent to the email address supplied
        during checkout.
      </p>

      <div class="actions">

        <router-link
          class="btn"
          to="/shop"
          @click="continueShopping"
        >
          Clear Bag &amp; Continue Shopping
        </router-link>

        <router-link
          class="btn btn--ghost"
          to="/account"
        >
          My Account
        </router-link>

      </div>

    </template>

    <template
      v-else-if="order?.status === 'refunded'"
    >

      <h1>Order Refunded</h1>

      <p>
        This order has been marked as refunded.
      </p>

      <router-link class="btn" to="/shop">
        Continue Shopping
      </router-link>

    </template>

    <template
      v-else-if="order?.status === 'cancelled'"
    >

      <h1>Order Cancelled</h1>

      <p>
        This order is no longer active.
        If you believe you have been charged,
        please contact customer support.
      </p>

      <router-link class="btn" to="/cart">
        Return to Shopping Bag
      </router-link>

    </template>

    <template v-else-if="order">

      <div class="eyebrow">
        Payment verification
      </div>

      <h1>Payment Confirmation Pending</h1>

      <p>
        We are awaiting confirmation for order
        <strong>{{ order.number }}</strong>.
      </p>

      <p>
        If you have already completed payment,
        please do not attempt another payment
        until the status has been confirmed.
      </p>

      <p v-if="error" class="alert">
        {{ error }}
      </p>

      <button
        class="btn"
        :disabled="checking"
        @click="retryConfirmation"
      >
        {{ checking ? 'Checking…' : 'Check Payment Status' }}
      </button>

    </template>

    <template v-else>

      <h1>
        {{ error
          ? 'Unable to Confirm Payment'
          : 'No Recent Order'
        }}
      </h1>

      <p v-if="error" class="alert">
        {{ error }}
      </p>

      <div class="actions">

        <button
          v-if="route.query.session_id"
          class="btn"
          :disabled="checking"
          @click="retryConfirmation"
        >
          Check Again
        </button>

        <router-link
          class="btn btn--ghost"
          to="/shop"
        >
          Continue Shopping
        </router-link>

      </div>

    </template>

  </div>
</template>

<style scoped>
.success-page {
  max-width: 720px;
  margin: 0 auto;
  padding-top: 60px;
  padding-bottom: 60px;
}

.success-symbol {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  border: 2px solid #c9a86a;
  color: #c9a86a;
  font-size: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 25px;
}

.order-information {
  border: 1px solid #ddd;
  padding: 24px;
  margin: 28px 0;
  border-radius: 6px;
}

.information-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid #eee;
}

.information-row:last-child {
  border-bottom: none;
}

.paid {
  color: #16803c;
}

.actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 28px;
}

.confirmation-panel {
  text-align: center;
  padding: 40px 0;
}

.spinner {
  width: 42px;
  height: 42px;
  border: 3px solid #eee;
  border-top-color: #c9a86a;
  border-radius: 50%;
  margin: 0 auto 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .spinner {
    animation: none;
  }
}
</style>