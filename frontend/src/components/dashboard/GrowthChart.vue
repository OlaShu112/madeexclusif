
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

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend
)

const props = defineProps({
  series: {
    type: Array,
    default: () => []
  }
})

const chartData = computed(() => ({
  labels: props.series.map(item => item.date),

  datasets: [
    {
      label: 'New customers',
      data: props.series.map(item => item.customers),
      backgroundColor: '#c9a86a',
      borderRadius: 5
    },
    {
      label: 'New businesses',
      data: props.series.map(item => item.businesses),
      backgroundColor: '#262626',
      borderRadius: 5
    }
  ]
}))

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      },
      ticks: {
        maxTicksLimit: 8
      }
    },
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0
      }
    }
  }
}
</script>

<template>
  <div class="chart-container">
    <Bar
      :data="chartData"
      :options="options"
      aria-label="New customer and business registrations"
    />
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  width: 100%;
  height: 320px;
}
</style>
