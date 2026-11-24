
<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  description: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <article class="metric-card">
    <div class="metric-header">
      <span class="metric-label">{{ label }}</span>

      <div class="metric-icon">
        <slot name="icon" />
      </div>
    </div>

    <div class="metric-value">
      {{ value }}
    </div>

    <div v-if="description" class="metric-description">
      {{ description }}
    </div>
  </article>
</template>

<style scoped>
.metric-card {
  background: #fff;
  border: 1px solid #e8e5df;
  border-radius: 14px;
  padding: 22px;
  min-width: 0;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.025);
  transition: box-shadow 0.2s, transform 0.2s;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.metric-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.metric-label {
  font-size: 13px;
  font-weight: 600;
  color: #666;
}

.metric-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  color: #a48346;
  background: #f7f2e8;
}

.metric-value {
  margin-top: 14px;
  font-size: clamp(22px, 2.5vw, 30px);
  font-weight: 700;
  color: #171717;
  letter-spacing: -0.8px;
  overflow-wrap: anywhere;
}

.metric-description {
  margin-top: 8px;
  color: #777;
  font-size: 12px;
  line-height: 1.5;
}
</style>
