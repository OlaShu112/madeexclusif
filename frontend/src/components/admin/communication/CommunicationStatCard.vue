<script setup>

defineProps({

  title: {
    type: String,
    required: true,
  },

  value: {
    type: [Number, String],
    default: '—',
  },

  description: {
    type: String,
    default: '',
  },

  icon: {
    type: [Object, Function],
    default: null,
  },

  tone: {
    type: String,
    default: 'primary',
  },

})

</script>

<template>

  <article class="communication-stat-card">

    <div class="communication-stat-header">

      <span class="communication-stat-title">
        {{ title }}
      </span>

      <div
        v-if="icon"
        class="communication-stat-icon"
        :class="`tone-${tone}`"
      >

        <component
          :is="icon"
          :size="21"
        />

      </div>

    </div>

    <div class="communication-stat-value">
      {{ value }}
    </div>

    <div
      v-if="description"
      class="communication-muted"
    >
      {{ description }}
    </div>

  </article>

</template>