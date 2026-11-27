import { defineStore } from 'pinia'

import financeApi from '@/services/financeApi'

import {
  currentMonthStart,
  localDate,
  financeError,
  unpackFinance
} from '@/utils/finance'

export const useFinanceStore = defineStore('finance', {

  state: () => ({
    overview: null,

    trends: [],

    loading: false,

    error: '',

    trendError: '',

    lastUpdated: null,

    filters: {
      currency: 'gbp',
      livemode: false,
      from: currentMonthStart(),
      to: localDate(),
      months: 12
    }
  }),

  getters: {

    currency: state =>
      state.filters.currency.toUpperCase(),

    revenue: state =>
      state.overview?.revenue ?? {},

    expenses: state =>
      state.overview?.expenses ?? {},

    summary: state =>
      state.overview?.summary ?? {},

    invoices: state =>
      state.overview?.invoices ?? {},

    payments: state =>
      state.overview?.payments ?? {},

    recentTransactions: state =>
      state.overview?.recent_transactions ?? [],

    revenueBreakdown: state =>
      state.overview?.revenue_breakdown ?? []
  },

  actions: {

    async loadDashboard() {
      if (this.loading) return

      this.loading = true
      this.error = ''
      this.trendError = ''
      this.overview = null
      this.trends = []
      this.lastUpdated = null

      try {

        const filters = {
          currency: this.filters.currency,
          from: this.filters.from,
          to: this.filters.to
        }

        const response = await financeApi.overview(
          filters
        )

        this.overview = unpackFinance(response)

        this.lastUpdated = new Date().toISOString()

        try {
          const trendResponse = await financeApi.trends({
            currency: this.filters.currency,
            months: this.filters.months
          })

          const data = unpackFinance(trendResponse)

          this.trends = Array.isArray(data)
            ? data
            : []

        } catch (error) {
          this.trendError = financeError(error)
        }

      } catch (error) {

        this.error = financeError(error)

      } finally {

        this.loading = false
      }
    },

    async resetFilters() {
      if (this.loading) return

      this.filters = {
        currency: 'gbp',
        livemode: false,
        from: currentMonthStart(),
        to: localDate(),
        months: 12
      }

      await this.loadDashboard()
    },

    clearFinance() {
      this.overview = null
      this.trends = []
      this.error = ''
      this.trendError = ''
      this.lastUpdated = null
    }
  }
})