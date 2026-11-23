
<script setup>
import { computed, ref, watch } from 'vue'
import BottleArt from './BottleArt.vue'

/**
 * Made Exclusif - Product Image Component
 *
 * Displays:
 * - Uploaded product photographs
 * - Perfume bottle placeholder
 * - Clothing placeholder
 *
 * Automatically falls back when an image fails to load.
 */

const props = defineProps({
  src: {
    type: String,
    default: null,
  },

  accent: {
    type: String,
    default: '#c9a86a',
  },

  label: {
    type: String,
    default: '',
  },

  kind: {
    type: String,
    default: 'perfume',
  },

  lazy: {
    type: Boolean,
    default: true,
  },
})

const failed = ref(false)

const imageSource = computed(() => {
  return typeof props.src === 'string'
    ? props.src.trim()
    : ''
})

const isClothing = computed(() => {
  return props.kind === 'clothing'
})

const showImage = computed(() => {
  return Boolean(imageSource.value) && !failed.value
})

const altText = computed(() => {
  return props.label || 'Product image'
})

watch(
  () => props.src,
  () => {
    failed.value = false
  }
)

function handleImageError() {
  failed.value = true
}
</script>

<template>
  <img
    v-if="showImage"
    :src="imageSource"
    :alt="altText"
    class="product-photo"
    :loading="lazy ? 'lazy' : 'eager'"
    decoding="async"
    @error="handleImageError"
  />

  <div
    v-else-if="isClothing"
    class="product-placeholder clothing-placeholder"
    role="img"
    :aria-label="`${altText} placeholder`"
  >
    <svg
      viewBox="0 0 100 100"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
      class="clothing-art"
    >
      <path
        d="M30 12 L8 30 L22 46 L30 40 L30 90 L70 90 L70 40 L78 46 L92 30 L70 12 Q50 26 30 12 Z"
        :fill="accent"
        stroke="#00000022"
        stroke-width="1.5"
        stroke-linejoin="round"
      />
    </svg>
  </div>

  <div
    v-else
    class="product-placeholder perfume-placeholder"
    role="img"
    :aria-label="`${altText} placeholder`"
  >
    <BottleArt
      :accent="accent"
      :label="label"
    />
  </div>
</template>

<style scoped>
.product-photo {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
}

.product-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-height: 120px;
  overflow: hidden;
}

.clothing-art {
  display: block;
  width: 65%;
  max-width: 170px;
  height: auto;
}

.perfume-placeholder {
  padding: 8px;
}

@media (max-width: 640px) {
  .clothing-art {
    width: 75%;
    max-width: 140px;
  }
}
</style>

