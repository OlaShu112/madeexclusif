<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/http'
import { money } from '@/utils/format'

const router = useRouter()

const loading = ref(true)
const error = ref('')
const store = ref(null)
const products = ref([])
const orders = ref([])
const activeTab = ref('products')
const savingOrderId = ref(null)

const isApproved = computed(() =>
  store.value?.status === 'active'
)

const storeUrl = computed(() =>
  store.value?.slug
    ? `/s/${store.value.slug}`
    : null
)

const totalProducts = computed(() => products.value.length)
const totalOrders = computed(() => orders.value.length)

function unwrapList(response) {
  if (Array.isArray(response)) return response
  if (Array.isArray(response?.data)) return response.data
  return []
}

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const response = await api('/seller/store')

    store.value = response?.store ?? response

    if (store.value?.status === 'active') {
      const [productResponse, orderResponse] = await Promise.all([
        api('/seller/products'),
        api('/seller/orders'),
      ])

      products.value = unwrapList(productResponse)
      orders.value = unwrapList(orderResponse)
    }
  } catch (err) {
    error.value =
      err?.data?.message ||
      err?.message ||
      'Unable to load your seller dashboard.'
  } finally {
    loading.value = false
  }
}

async function updateOrderStatus(order, status) {
  if (savingOrderId.value) return

  savingOrderId.value = order.id
  error.value = ''

  try {
    const updated = await api(`/seller/orders/${order.id}`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    })

    const index = orders.value.findIndex(
      (item) => item.id === order.id
    )

    if (index !== -1) {
      orders.value[index] = {
        ...orders.value[index],
        ...(updated?.order ?? updated),
      }
    }
  } catch (err) {
    error.value =
      err?.data?.message ||
      err?.message ||
      'Unable to update the order.'
  } finally {
    savingOrderId.value = null
  }
}

function nextStatus(status) {
  if (status === 'pending' || status === 'paid') {
    return 'processing'
  }

  if (status === 'processing') {
    return 'shipped'
  }

  if (status === 'shipped') {
    return 'delivered'
  }

  return null
}

onMounted(loadDashboard)
</script>

<template>
  <main class="container seller-dashboard">
    <div class="dashboard-heading">
      <div>
        <p class="eyebrow">Made Exclusif Marketplace</p>
        <h1>Seller Dashboard</h1>
        <p>Manage your storefront, products and customer orders.</p>
      </div>

      <router-link
        v-if="storeUrl"
        :to="storeUrl"
        class="secondary-button"
      >
        View Store
      </router-link>
    </div>

    <p v-if="loading">Loading your dashboard...</p>

    <div v-else-if="error && !store" class="message error" role="alert">
      <p>{{ error }}</p>
      <button type="button" @click="loadDashboard">
        Try Again
      </button>
      <button type="button" @click="router.push('/open-store')">
        Open a Store
      </button>
    </div>

    <template v-else-if="store">
      <section class="store-summary">
        <div>
          <p class="eyebrow">Your Store</p>
          <h2>{{ store.name }}</h2>
          <p>{{ store.tagline || 'Your Made Exclusif storefront' }}</p>
        </div>

        <span
          class="status"
          :class="`status--${store.status}`"
        >
          {{ store.status }}
        </span>
      </section>

      <div v-if="!isApproved" class="message">
        <h2 v-if="store.status === 'pending'">
          Your application is under review
        </h2>

        <h2 v-else-if="store.status === 'suspended'">
          Your store is suspended
        </h2>

        <h2 v-else>
          Your store is not active
        </h2>

        <p>
          Product and order management will become available
          when your store is active.
        </p>
      </div>

      <template v-else>
        <div class="stats">
          <div class="stat-card">
            <span>Products on this page</span>
            <strong>{{ totalProducts }}</strong>
          </div>

          <div class="stat-card">
            <span>Orders on this page</span>
            <strong>{{ totalOrders }}</strong>
          </div>
        </div>

        <div class="tabs">
          <button
            type="button"
            :class="{ selected: activeTab === 'products' }"
            @click="activeTab = 'products'"
          >
            Products
          </button>

          <button
            type="button"
            :class="{ selected: activeTab === 'orders' }"
            @click="activeTab = 'orders'"
          >
            Orders
          </button>
        </div>

        <p v-if="error" class="error" role="alert">
          {{ error }}
        </p>

        <section v-if="activeTab === 'products'">
          <div class="section-heading">
            <h2>My Products</h2>
          </div>

          <div v-if="products.length" class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Price From</th>
                  <th>Featured</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="product in products"
                  :key="product.id"
                >
                  <td>{{ product.name }}</td>
                  <td>{{ product.category?.name || '—' }}</td>
                  <td>
                    {{
                      product.min_price != null
                        ? money(product.min_price)
                        : '—'
                    }}
                  </td>
                  <td>{{ product.is_featured ? 'Yes' : 'No' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-else>No products have been added yet.</p>
        </section>

        <section v-else>
          <h2>Customer Orders</h2>

          <div v-if="orders.length" class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Total</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="order in orders" :key="order.id">
                  <td>#{{ order.id }}</td>
                  <td>{{ order.status }}</td>
                  <td>{{ order.payment_status }}</td>
                  <td>{{ money(order.total) }}</td>
                  <td>
                    <button
                      v-if="
                        nextStatus(order.status) &&
                        order.payment_status === 'paid'
                      "
                      type="button"
                      :disabled="savingOrderId === order.id"
                      @click="
                        updateOrderStatus(
                          order,
                          nextStatus(order.status)
                        )
                      "
                    >
                      Mark {{ nextStatus(order.status) }}
                    </button>

                    <span v-else>—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-else>No customer orders yet.</p>
        </section>
      </template>
    </template>
  </main>
</template>

<style scoped>
.seller-dashboard {
  padding-top: 40px;
  padding-bottom: 80px;
}

.dashboard-heading,
.store-summary,
.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.dashboard-heading {
  margin-bottom: 35px;
}

.dashboard-heading h1 {
  margin: 8px 0;
  font-size: clamp(28px, 4vw, 40px);
}

.eyebrow {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: #777;
}

.secondary-button {
  padding: 12px 18px;
  border: 1px solid #222;
  color: #222;
  text-decoration: none;
  border-radius: 5px;
}

.store-summary {
  padding: 25px;
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  margin-bottom: 25px;
}

.store-summary h2 {
  margin: 8px 0;
}

.status {
  padding: 7px 12px;
  border-radius: 20px;
  text-transform: capitalize;
  background: #eee;
}

.status--active {
  background: #e5f6ea;
  color: #176c36;
}

.status--pending {
  background: #fff2d8;
  color: #8b5900;
}

.status--suspended {
  background: #ffe7e7;
  color: #a22;
}

.message {
  padding: 25px;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
}

.error {
  color: #b42318;
}

.stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  padding: 25px;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
}

.stat-card span {
  display: block;
  color: #777;
}

.stat-card strong {
  display: block;
  font-size: 30px;
  margin-top: 10px;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 25px;
}

.tabs button {
  padding: 10px 20px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
}

.tabs button.selected {
  background: #222;
  color: white;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 14px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

th {
  font-size: 12px;
  text-transform: uppercase;
  color: #666;
}

td button {
  padding: 8px 12px;
  border: 1px solid #222;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

td button:disabled {
  opacity: 0.5;
  cursor: wait;
}

@media (max-width: 640px) {
  .dashboard-heading,
  .store-summary {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats {
    grid-template-columns: 1fr;
  }
}
</style>

