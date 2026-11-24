
<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'

import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  ArcElement,
  Tooltip,
  Legend
)

const props = defineProps({
  statuses: {
    type: Array,
    default: () => []
  }
})

const colours = {
  pending: '#d6b66c',
  paid: '#64748b',
  processing: '#b89455',
  shipped: '#475569',
  delivered: '#27865a',
  cancelled: '#b45353',
  refunded: '#999999'
}

const chartData = computed(() => ({
  labels: props.statuses.map(
    item => item.status
  ),

  datasets: [
    {
      data: props.statuses.map(
        item => Number(item.total)
      ),

      backgroundColor: props.statuses.map(
        item => colours[item.status] || '#333333'
      ),

      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 6
    }
  ]
}))

const options = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}
</script>

<template>
  <div class="chart-container">
    <Doughnut
      v-if="statuses.some(item => Number(item.total) > 0)"
      :data="chartData"
      :options="options"
      aria-label="Order status distribution"
    />

    <p v-else class="empty">
      No orders for this period.
    </p>
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  width: 100%;
  height: 290px;
}

.empty {
  text-align: center;
  padding-top: 100px;
  color: #777;
}
</style>
