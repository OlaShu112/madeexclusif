
<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
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
      label: 'Gross sales',
      data: props.series.map(
        item => item.sales_minor / 100
      ),
      borderColor: '#b89455',
      backgroundColor: 'rgba(201,168,106,0.15)',
      borderWidth: 2.5,
      pointRadius: 0,
      pointHoverRadius: 5,
      tension: 0.35,
      fill: true
    }
  ]
}))

const money = new Intl.NumberFormat('en-GB', {
  style: 'currency',
  currency: 'GBP'
})

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: context =>
          money.format(context.parsed.y)
      }
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
        callback: value =>
          money.format(value)
      },
      grid: {
        color: 'rgba(128,128,128,0.12)'
      }
    }
  }
}
</script>

<template>
  <div class="chart-container">
    <Line
      :data="chartData"
      :options="options"
      aria-label="Gross sales over the selected period"
    />
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  height: 320px;
  width: 100%;
}
</style>
