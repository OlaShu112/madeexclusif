
<script setup>
import { ref, watch } from 'vue'
import { api } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useBusinessContext } from '@/composables/useBusinessContext'
import { money, dateShort } from '@/utils/format'
import ProductCard from '@/components/ProductCard.vue'

const auth = useAuthStore()

const {
  businessName,
  storeSlug,
  isIndependentStore,
  customerRoute
} = useBusinessContext()

const tab = ref('orders')
const orders = ref([])
const wishlist = ref([])
const loading = ref(true)
const error = ref('')

let requestVersion = 0

async function loadAccount() {

  const version = ++requestVersion

  loading.value = true
  error.value = ''
  orders.value = []
  wishlist.value = []

  const params = isIndependentStore.value
    ? { store: storeSlug.value }
    : {}

  try {

    const [o, w] = await Promise.all([
      api('/orders', { params }),
      api('/wishlist', { params })
    ])

    if (version !== requestVersion) return

    orders.value = o.data || []
    wishlist.value = w || []

  } catch (e) {

    if (version !== requestVersion) return

    error.value =
      e.message || 'Unable to load your account.'

  } finally {

    if (version === requestVersion) {
      loading.value = false
    }
  }
}

watch(
  [storeSlug, () => auth.user?.id],
  loadAccount,
  { immediate: true }
)

function itemDescription(item) {

  const variant =
    item.variant_label ||
    (item.size_ml ? `${item.size_ml}ml` : '')

  return [
    item.name,
    variant,
    `× ${item.quantity}`
  ].filter(Boolean).join(' ')
}
</script>

<template>

  <main
    class="container section account-page"
    :class="{ 'business-account': isIndependentStore }"
  >

    <p class="business-label">
      {{ businessName }}
    </p>

    <h1>Hello, {{ auth.user?.name }}</h1>

    <p class="muted">
      {{ auth.user?.email }}
    </p>

    <p class="account-introduction">
      Manage your orders and saved products
      with {{ businessName }}.
    </p>

    <div class="tabs">

      <button
        :class="{ on: tab === 'orders' }"
        @click="tab = 'orders'"
      >
        Orders
      </button>

      <button
        :class="{ on: tab === 'wishlist' }"
        @click="tab = 'wishlist'"
      >
        Wishlist ({{ wishlist.length }})
      </button>

    </div>

    <div v-if="error" class="alert">

      {{ error }}

      <button
        class="linkbtn"
        @click="loadAccount"
      >
        Try again
      </button>

    </div>

    <p v-else-if="loading" class="muted">
      Loading your account...
    </p>

    <template v-else-if="tab === 'orders'">

      <div
        v-if="!orders.length"
        class="empty-state"
      >

        <h3>No orders yet</h3>

        <p class="muted">
          You haven't placed any orders
          with {{ businessName }}.
        </p>

        <RouterLink
          :to="customerRoute('shop')"
          class="btn"
        >
          Continue Shopping
        </RouterLink>

      </div>

      <div v-else class="table-wrapper">

        <table class="table">

          <thead>
            <tr>
              <th>Order</th>
              <th>Date</th>
              <th>Items</th>
              <th>Status</th>
              <th style="text-align:right">Total</th>
            </tr>
          </thead>

          <tbody>

            <tr v-for="o in orders" :key="o.id">

              <td>{{ o.number }}</td>

              <td>{{ dateShort(o.created_at) }}</td>

              <td>
                {{
                  (o.items || [])
                    .map(itemDescription)
                    .join(', ')
                }}
              </td>

              <td>

                <span
                  class="pill"
                  :class="`pill--${o.status}`"
                >
                  {{ o.status }}
                </span>

                <div
                  v-if="o.tracking_number"
                  class="muted"
                >
                  Tracking: {{ o.tracking_number }}
                </div>

              </td>

              <td style="text-align:right">
                {{ money(o.total) }}
              </td>

            </tr>

          </tbody>

        </table>

      </div>

    </template>

    <template v-else>

      <div
        v-if="!wishlist.length"
        class="empty-state"
      >

        <h3>Your wishlist is empty</h3>

        <p class="muted">
          Save your favourite products
          to find them here later.
        </p>

        <RouterLink
          :to="customerRoute('shop')"
          class="btn"
        >
          Explore Products
        </RouterLink>

      </div>

      <div v-else class="grid">

        <ProductCard
          v-for="p in wishlist"
          :key="p.id"
          :product="p"
        />

      </div>

    </template>

  </main>

</template>

<style scoped>
.business-label {
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 12px;
  margin-bottom: 12px;
}

.account-introduction {
  margin: 15px 0 25px;
  line-height: 1.7;
}

.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-state p {
  margin: 15px 0 25px;
}

.table-wrapper {
  overflow-x: auto;
}

.business-account {
  color: var(--store-text);
}

.business-account .btn {
  background: var(--store-primary);
  color: var(--store-primary-text);
  border-color: var(--store-primary);
}

.business-account .muted {
  color: var(--store-text);
  opacity: 0.75;
}
</style>
