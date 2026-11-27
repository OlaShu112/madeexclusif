<script setup>
defineProps({
  title: {
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
  },

  icon: {
    type: [Object, Function],
    default: null
  },

  tone: {
    type: String,
    default: 'neutral'
  }
})
</script>

<template>
  <article
    class="finance-stat-card"
    :class="`finance-tone-${tone}`"
  >
    <div class="finance-stat-heading">

      <span class="finance-stat-label">
        {{ title }}
      </span>

      <div
        v-if="icon"
        class="finance-stat-icon"
      >
        <component
          :is="icon"
          :size="21"
          :stroke-width="1.7"
          aria-hidden="true"
        />
      </div>

    </div>

    <strong class="finance-stat-value">
      {{ value }}
    </strong>

    <span
      v-if="description"
      class="finance-stat-description"
    >
      {{ description }}
    </span>

  </article>
</template>