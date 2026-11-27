<script setup>
import { computed } from 'vue'

import { Bar } from 'vue-chartjs'

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend
} from 'chart.js'

import { formatMoney } from '@/utils/finance'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend
)

const props = defineProps({
  records: {
    type: Array,
    default: () => []
  },

  currency: {
    type: String,
    default: 'GBP'
  }
})

const chartData = computed(() => ({
  labels: props.records.map(
    record => record.label ?? record.month
  ),

  datasets: [
    {
      label: 'Credits',
      data: props.records.map(
        record => Number(record.credits_minor ?? 0) / 100
      ),
      backgroundColor: '#16a34a',
      borderRadius: 5,
      maxBarThickness: 32
    },

    {
      label: 'Debits',
      data: props.records.map(
        record => Number(record.debits_minor ?? 0) / 100
      ),
      backgroundColor: '#94a3b8',
      borderRadius: 5,
      maxBarThickness: 32
    }
  ]
}))

const chartOptions = computed(() => ({
  responsive: true,

  maintainAspectRatio: false,

  interaction: {
    mode: 'index',
    intersect: false
  },

  plugins: {

    legend: {
      position: 'bottom'
    },

    tooltip: {
      callbacks: {
        label(context) {
          return `${context.dataset.label}: ${
            formatMoney(
              Math.round(context.parsed.y * 100),
              props.currency
            )
          }`
        }
      }
    }
  },

  scales: {

    x: {
      grid: {
        display: false
      }
    },

    y: {
      beginAtZero: true,

      ticks: {
        callback(value) {
          return formatMoney(
            Math.round(Number(value) * 100),
            props.currency
          )
        }
      }
    }
  }
}))
</script>

<template>
  <div class="finance-chart-container">

    <Bar
      v-if="records.length"
      :data="chartData"
      :options="chartOptions"
      aria-label="Monthly financial credits and debits"
    />

    <div
      v-else
      class="finance-empty-state"
    >
      No monthly financial information available.
    </div>

  </div>
</template>