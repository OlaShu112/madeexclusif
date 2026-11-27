
<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'

import {
  ShoppingBag,
  Wallet,
  Users,
  Store,
  Package,
  Receipt,
  RefreshCw,
  TrendingUp,
  Mail,
  Banknote
} from 'lucide-vue-next'

import MetricCard from '@/components/dashboard/MetricCard.vue'
import SalesChart from '@/components/dashboard/SalesChart.vue'
import GrowthChart from '@/components/dashboard/GrowthChart.vue'
import OrderStatusChart from '@/components/dashboard/OrderStatusChart.vue'

const days = ref(30)
const analytics = ref(null)
const loading = ref(false)
const error = ref('')
const platform = usePlatformStore()

const money = value =>
  new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP'
  }).format(Number(value || 0) / 100)

const number = value =>
  new Intl.NumberFormat('en-GB')
    .format(Number(value || 0))

const summary = computed(() =>
  analytics.value?.summary || {}
)

const metrics = computed(() => [
  {
    label: 'Gross sales',
    value: money(summary.value.gross_sales_minor),
    description: 'Confirmed customer payments',
    icon: ShoppingBag
  },
  {
    label: 'Net sales',
    value: money(summary.value.net_sales_minor),
    description: 'Gross sales less recorded refunds',
    icon: TrendingUp
  },
  {
    label: 'Platform commission',
    value: money(summary.value.commission_minor),
    description: 'Recorded commission less reversals',
    icon: Wallet
  },
  {
    label: 'Paid orders',
    value: number(summary.value.paid_orders),
    description: 'During the selected period',
    icon: Package
  },
  {
    label: 'Registered customers',
    value: number(summary.value.customers),
    description:
      `${number(summary.value.new_customers)} new in this period`,
    icon: Users
  },
  {
    label: 'Active businesses',
    value: number(summary.value.active_businesses),
    description:
      `${number(summary.value.pending_businesses)} awaiting approval`,
    icon: Store
  },
  {
    label: 'Refunds',
    value: money(summary.value.refunds_minor),
    description: 'Successfully recorded refunds',
    icon: Receipt
  },
  {
    label: 'Seller payouts',
    value: money(summary.value.paid_payouts_minor),
    description: 'Recorded completed payouts',
    icon: Banknote
  },
  {
    label: 'Products',
    value: number(summary.value.products),
    description: 'Total marketplace products',
    icon: Package
  },
  {
    label: 'Failed emails',
    value: number(summary.value.failed_emails),
    description: 'Notifications requiring attention',
    icon: Mail
  }
])

async function loadAnalytics() {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    analytics.value = await api('/admin/analytics', {
      params: {
        days: days.value
      }
    })
  } catch (e) {
    error.value =
      e.message || 'Unable to load analytics.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAnalytics)
</script>

<template>
  <main class="analytics-page">

    <header class="page-header">
      <div>
        <div class="eyebrow">
          {{ platform.branding.platform_name || 'IVISION' }} Administration
        </div>

        <h1>Business Overview</h1>

        <p>
          Monitor marketplace performance,
          customer growth and financial activity.
        </p>
      </div>

      <div class="header-actions">
        <select
          v-model.number="days"
          :disabled="loading"
          aria-label="Reporting period"
          @change="loadAnalytics"
        >
          <option :value="7">Last 7 days</option>
          <option :value="30">Last 30 days</option>
          <option :value="90">Last 90 days</option>
          <option :value="365">Last 365 days</option>
        </select>

        <button
          :disabled="loading"
          @click="loadAnalytics"
        >
          <RefreshCw :size="16" />
          Refresh
        </button>
      </div>
    </header>

    <div v-if="error" class="error" role="alert">
      {{ error }}
    </div>

    <div
      v-if="loading"
      class="loading"
      role="status"
    >
      Loading marketplace analytics...
    </div>

    <template v-if="analytics">

      <div class="reporting-period">
        Reporting period:
        {{ analytics.period.from }}
        —
        {{ analytics.period.to }}
      </div>

      <section class="metrics-grid">
        <MetricCard
          v-for="metric in metrics"
          :key="metric.label"
          :label="metric.label"
          :value="metric.value"
          :description="metric.description"
        >
          <template #icon>
            <component
              :is="metric.icon"
              :size="21"
            />
          </template>
        </MetricCard>
      </section>

      <section class="charts-grid">

        <article class="panel sales-panel">
          <h2>Sales Performance</h2>
          <p>Gross sales over the reporting period</p>

          <SalesChart
            :series="analytics.series"
          />
        </article>

        <article class="panel">
          <h2>Order Distribution</h2>
          <p>Current status of orders placed in this period</p>

          <OrderStatusChart
            :statuses="analytics.order_statuses"
          />
        </article>

        <article class="panel full-width">
          <h2>Customer & Business Growth</h2>
          <p>New marketplace registrations</p>

          <GrowthChart
            :series="analytics.series"
          />
        </article>

      </section>

      <section class="tables-grid">

        <article class="panel">
          <h2>Recent Orders</h2>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Business</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="order in analytics.recent_orders"
                  :key="order.id"
                >
                  <td>{{ order.number }}</td>

                  <td>
                    {{ order.store?.name || 'Unassigned' }}
                  </td>

                  <td>
                    {{ money(Number(order.total) * 100) }}
                  </td>

                  <td>
                    <span
                      class="status"
                      :class="order.status"
                    >
                      {{ order.status }}
                    </span>
                  </td>
                </tr>

                <tr v-if="!analytics.recent_orders.length">
                  <td colspan="4">No orders available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="panel">
          <h2>Top Selling Products</h2>
          <p>Units sold during the selected period</p>

          <div
            v-for="(product, index) in analytics.top_products"
            :key="product.name"
            class="product-row"
          >
            <span class="rank">
              {{ index + 1 }}
            </span>

            <span class="product-name">
              {{ product.name }}
            </span>

            <strong>
              {{ number(product.units) }}
            </strong>
          </div>

          <p v-if="!analytics.top_products.length">
            No sales recorded for this period.
          </p>
        </article>

      </section>

      <p class="financial-note">
        Financial figures are internal marketplace records
        and may differ from Stripe settlement balances.
        Customer and business totals are all-time counts.
      </p>

    </template>

  </main>
</template>

<style scoped>
.analytics-page {
  min-height: 100vh;
  padding: 32px;
  background: #f6f6f4;
  color: #171717;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 24px;
  margin-bottom: 28px;
}

.eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  color: #98763c;
}

h1 {
  font-size: 32px;
  margin: 8px 0;
  font-weight: 700;
}

.page-header p,
.panel p {
  font-size: 13px;
  color: #777;
  line-height: 1.6;
}

.header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

select,
button {
  border-radius: 8px;
  padding: 11px 15px;
  font-size: 13px;
  font-weight: 500;
}

select {
  background: #fff;
  border: 1px solid #ddd;
  color: #222;
}

button {
  background: #171717;
  color: #d4b477;
  border: 1px solid #171717;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

button:disabled {
  opacity: 0.5;
  cursor: wait;
}

.reporting-period {
  color: #777;
  font-size: 12px;
  margin-bottom: 18px;
}

.metrics-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(210px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.charts-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 2fr) minmax(0, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.panel {
  background: #fff;
  border: 1px solid #e8e5df;
  border-radius: 14px;
  padding: 24px;
  min-width: 0;
}

.panel h2 {
  font-size: 17px;
  font-weight: 650;
  margin: 0 0 8px;
}

.panel p {
  margin: 0 0 24px;
}

.full-width {
  grid-column: 1 / -1;
}

.tables-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 2fr) minmax(0, 1fr);
  gap: 20px;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  text-align: left;
}

th {
  color: #777;
  font-weight: 600;
  background: #faf9f6;
}

th,
td {
  padding: 14px 10px;
  border-bottom: 1px solid #eee;
  white-space: nowrap;
}

.status {
  display: inline-block;
  border-radius: 5px;
  background: #f0f0f0;
  color: #555;
  padding: 5px 9px;
  text-transform: capitalize;
  font-size: 11px;
}

.status.delivered,
.status.paid {
  background: #e7f3eb;
  color: #23683e;
}

.status.pending,
.status.processing {
  background: #faf0d9;
  color: #8c6829;
}

.status.cancelled,
.status.refunded {
  background: #f8e9e9;
  color: #a43c3c;
}

.product-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid #eee;
  font-size: 13px;
}

.rank {
  color: #a48346;
  background: #f7f2e8;
  width: 30px;
  height: 30px;
  border-radius: 7px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.product-name {
  flex: 1;
  overflow-wrap: anywhere;
}

.error {
  background: #fceaea;
  color: #a33333;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.loading {
  color: #777;
  padding: 20px;
}

.financial-note {
  margin-top: 24px;
  color: #777;
  font-size: 12px;
  line-height: 1.6;
}

@media (max-width: 1000px) {
  .charts-grid,
  .tables-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 600px) {
  .analytics-page {
    padding: 16px;
  }

  h1 {
    font-size: 25px;
  }

  .metrics-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .panel {
    padding: 16px;
  }
}
</style>
