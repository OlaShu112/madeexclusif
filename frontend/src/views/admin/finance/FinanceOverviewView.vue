<script setup>
import {
  computed,
  onMounted,
  onBeforeUnmount
} from 'vue'

import {
  Wallet,
  TrendingUp,
  TrendingDown,
  Receipt,
  CreditCard,
  FileText,
  RefreshCw,
  ArrowLeftRight,
  AlertCircle,
  Landmark
} from 'lucide-vue-next'

import { useFinanceStore } from '@/stores/finance'

import FinanceStatCard from
  '@/components/admin/finance/FinanceStatCard.vue'

import FinanceRevenueChart from
  '@/components/admin/finance/FinanceRevenueChart.vue'

import {
  formatMoney,
  formatNumber,
  formatDate,
  formatDateTime,
  humanise
} from '@/utils/finance'

const finance = useFinanceStore()

const money = value =>
  formatMoney(value, finance.currency)

const statistics = computed(() => [
  {
    title: 'Gross revenue',
    value: money(
      finance.revenue.gross_revenue_minor
    ),
    description: 'Total platform revenue',
    icon: TrendingUp,
    tone: 'success'
  },

  {
    title: 'Net revenue',
    value: money(
      finance.revenue.net_revenue_minor
    ),
    description: 'Revenue after refunds',
    icon: Wallet,
    tone: 'success'
  },

  {
    title: 'Operating expenses',
    value: money(
      finance.expenses.operating_expenses_minor
    ),
    description: 'Recorded operating costs',
    icon: TrendingDown,
    tone: 'neutral'
  },

  {
    title: 'Net cash movement',
    value: money(
      finance.summary.net_cash_movement_minor
    ),
    description: 'Credits less debits',
    icon: Landmark,
    tone: 'neutral'
  },

  {
    title: 'Subscription revenue',
    value: money(
      finance.revenue.subscription_revenue_minor
    ),
    description: 'Platform subscription income',
    icon: CreditCard,
    tone: 'neutral'
  },

  {
    title: 'Commission revenue',
    value: money(
      finance.revenue.commission_revenue_minor
    ),
    description: 'Recorded platform commissions',
    icon: Receipt,
    tone: 'neutral'
  },

  {
    title: 'Outstanding invoices',
    value: money(
      finance.invoices.outstanding_amount_minor
    ),
    description: 'Current outstanding balance',
    icon: FileText,
    tone: 'warning'
  },

  {
    title: 'Transactions',
    value: formatNumber(
      finance.summary.transaction_count
    ),
    description: 'Completed financial transactions',
    icon: ArrowLeftRight,
    tone: 'neutral'
  }
])

onMounted(() => {
  finance.loadDashboard()
})

onBeforeUnmount(() => {
  finance.clearFinance()
})
</script>

<template>
  <div class="finance-overview">

    <!-- Page heading -->

    <div class="finance-page-heading">

      <div>
        <h2>Financial overview</h2>

        <p>
          Monitor platform revenue, expenditure
          and financial performance.
        </p>
      </div>

      <button
        type="button"
        class="finance-button"
        :disabled="finance.loading"
        @click="finance.loadDashboard()"
      >
        <RefreshCw
          :size="17"
          :class="{ 'finance-spin': finance.loading }"
        />

        Refresh
      </button>

    </div>

    <!-- Financial filters -->

    <form
      class="finance-filter-panel"
      @submit.prevent="finance.loadDashboard()"
    >

      <label>
        From date

        <input
          v-model="finance.filters.from"
          type="date"
          :disabled="finance.loading"
          required
        />
      </label>

      <label>
        To date

        <input
          v-model="finance.filters.to"
          type="date"
          :min="finance.filters.from"
          :disabled="finance.loading"
          required
        />
      </label>

      <label>
        Currency

        <select
          v-model="finance.filters.currency"
          :disabled="finance.loading"
        >
          <option value="gbp">GBP — British Pound</option>
          <option value="usd">USD — US Dollar</option>
          <option value="eur">EUR — Euro</option>
          <option value="ngn">NGN — Nigerian Naira</option>
        </select>
      </label>

      <button
        type="submit"
        class="finance-button finance-button-primary"
        :disabled="finance.loading"
      >
        Apply filters
      </button>

      <button
        type="button"
        class="finance-button"
        :disabled="finance.loading"
        @click="finance.resetFilters()"
      >
        Reset
      </button>

    </form>

    <!-- Error state -->

    <div
      v-if="finance.error"
      class="finance-error"
      role="alert"
    >
      <AlertCircle :size="20" />

      <div>
        <strong>
          Unable to load Finance dashboard
        </strong>

        <p>{{ finance.error }}</p>
      </div>
    </div>

    <!-- Loading state -->

    <div
      v-else-if="finance.loading && !finance.overview"
      class="finance-loading"
      role="status"
    >
      <RefreshCw
        :size="24"
        class="finance-spin"
      />

      Loading financial information...
    </div>

    <!-- Financial information -->

    <template v-else-if="finance.overview">

      <div class="finance-statistics-grid">

        <FinanceStatCard
          v-for="item in statistics"
          :key="item.title"
          v-bind="item"
        />

      </div>

      <!-- Monthly financial chart -->

      <section class="finance-panel">

        <div class="finance-panel-heading">
          <div>
            <h3>Monthly cash movement</h3>

            <p>
              Credits and debits over the last
              {{ finance.filters.months }} months.
            </p>
          </div>

          <select
            v-model.number="finance.filters.months"
            :disabled="finance.loading"
            aria-label="Chart reporting period"
            @change="finance.loadDashboard()"
          >
            <option :value="3">3 months</option>
            <option :value="6">6 months</option>
            <option :value="12">12 months</option>
            <option :value="24">24 months</option>
          </select>
        </div>

        <div
          v-if="finance.trendError"
          class="finance-error"
          role="alert"
        >
          {{ finance.trendError }}
        </div>

        <FinanceRevenueChart
          v-else
          :records="finance.trends"
          :currency="finance.currency"
        />

      </section>

      <!-- Revenue and expenditure breakdown -->

      <div class="finance-two-columns">

        <section class="finance-panel">

          <div class="finance-panel-heading">
            <h3>Revenue breakdown</h3>
          </div>

          <div class="finance-summary-list">

            <div>
              <span>Subscription revenue</span>

              <strong>
                {{ money(
                  finance.revenue.subscription_revenue_minor
                ) }}
              </strong>
            </div>

            <div>
              <span>Commission revenue</span>

              <strong>
                {{ money(
                  finance.revenue.commission_revenue_minor
                ) }}
              </strong>
            </div>

            <div>
              <span>Other revenue</span>

              <strong>
                {{ money(
                  finance.revenue.other_revenue_minor
                ) }}
              </strong>
            </div>

            <div>
              <span>Refunds and reversals</span>

              <strong>
                {{ money(
                  finance.revenue.refunds_minor
                ) }}
              </strong>
            </div>

            <div class="finance-summary-total">
              <span>Net revenue</span>

              <strong>
                {{ money(
                  finance.revenue.net_revenue_minor
                ) }}
              </strong>
            </div>

          </div>

        </section>

        <section class="finance-panel">

          <div class="finance-panel-heading">
            <h3>Expenditure breakdown</h3>
          </div>

          <div class="finance-summary-list">

            <div>
              <span>Operating expenses</span>

              <strong>
                {{ money(
                  finance.expenses.operating_expenses_minor
                ) }}
              </strong>
            </div>

            <div>
              <span>Payment processing fees</span>

              <strong>
                {{ money(
                  finance.expenses.processing_fees_minor
                ) }}
              </strong>
            </div>

            <div>
              <span>Total expenses</span>

              <strong>
                {{ money(
                  finance.expenses.total_expenses_minor
                ) }}
              </strong>
            </div>

            <div class="finance-summary-total">
              <span>Operating cash surplus</span>

              <strong>
                {{ money(
                  finance.summary.operating_cash_surplus_minor
                ) }}
              </strong>
            </div>

          </div>

        </section>

      </div>

      <!-- Invoice and payment summaries -->

      <div class="finance-two-columns">

        <section class="finance-panel">

          <div class="finance-panel-heading">
            <h3>Invoice summary</h3>
          </div>

          <div class="finance-summary-list">

            <div>
              <span>Total invoices</span>
              <strong>
                {{ formatNumber(finance.invoices.total) }}
              </strong>
            </div>

            <div>
              <span>Paid invoices</span>
              <strong>
                {{ formatNumber(finance.invoices.paid) }}
              </strong>
            </div>

            <div>
              <span>Outstanding invoices</span>
              <strong>
                {{ formatNumber(finance.invoices.outstanding) }}
              </strong>
            </div>

            <div>
              <span>Overdue invoices</span>
              <strong>
                {{ formatNumber(finance.invoices.overdue) }}
              </strong>
            </div>

          </div>

        </section>

        <section class="finance-panel">

          <div class="finance-panel-heading">
            <h3>Payment summary</h3>
          </div>

          <div class="finance-summary-list">

            <div>
              <span>Successful payments</span>
              <strong>
                {{ formatNumber(finance.payments.successful) }}
              </strong>
            </div>

            <div>
              <span>Pending payments</span>
              <strong>
                {{ formatNumber(finance.payments.pending) }}
              </strong>
            </div>

            <div>
              <span>Failed payments</span>
              <strong>
                {{ formatNumber(finance.payments.failed) }}
              </strong>
            </div>

          </div>

        </section>

      </div>

      <!-- Recent transactions -->

      <section class="finance-panel">

        <div class="finance-panel-heading">
          <div>
            <h3>Recent transactions</h3>
            <p>Latest transactions in the reporting period.</p>
          </div>
        </div>

        <div
          v-if="!finance.recentTransactions.length"
          class="finance-empty-state"
        >
          <ArrowLeftRight :size="36" />

          <strong>No transactions recorded</strong>

          <p>
            Financial transactions will appear here
            when recorded by the platform.
          </p>
        </div>

        <div
          v-else
          class="finance-table-wrapper"
        >

          <table class="finance-table">

            <thead>
              <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Direction</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="transaction in finance.recentTransactions"
                :key="transaction.id"
              >

                <td>{{ transaction.reference }}</td>

                <td>{{ humanise(transaction.type) }}</td>

                <td>
                  {{ humanise(transaction.direction) }}
                </td>

                <td
                  :class="transaction.direction === 'credit'
                    ? 'finance-credit'
                    : 'finance-debit'"
                >
                  {{ transaction.direction === 'credit'
                    ? '+'
                    : '−' }}

                  {{ formatMoney(
                    transaction.amount_minor,
                    transaction.currency
                  ) }}
                </td>

                <td>
                  <span class="finance-status-badge">
                    {{ humanise(transaction.status) }}
                  </span>
                </td>

                <td>
                  {{ formatDate(transaction.occurred_at) }}
                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </section>

      <p class="finance-last-updated">
        Last updated:
        {{ formatDateTime(finance.lastUpdated) }}
      </p>

    </template>

  </div>
</template>