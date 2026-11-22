<script setup>
import { ref, watch } from 'vue'
import BottleArt from './BottleArt.vue'

// Shows the uploaded photo when there is one, otherwise the generated bottle illustration.
const props = defineProps({
  src: { type: String, default: null },
  accent: { type: String, default: '#c9a86a' },
  label: { type: String, default: '' },
})

const failed = ref(false)
watch(() => props.src, () => (failed.value = false))
</script>

<template>
  <img v-if="src && !failed" :src="src" :alt="label" class="photo" loading="lazy" @error="failed = true" />
  <div v-else class="bottle-wrap"><BottleArt :accent="accent" :label="label" /></div>
</template>