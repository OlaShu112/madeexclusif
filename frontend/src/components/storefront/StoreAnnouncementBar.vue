<script setup>
import { computed } from 'vue'

const props = defineProps({
  store: {
    type: Object,
    required: true
  }
})

const theme = computed(
  () => props.store.website_theme || {}
)
</script>

<template>
  <div
    v-if="
      theme.announcement_enabled &&
      theme.announcement_text
    "
    class="store-announcement"
  >
    {{ theme.announcement_text }}
  </div>
</template>

<style scoped>
.store-announcement {
  background: var(--store-announcement, #222222);
  color: var(--store-announcement-text, #ffffff);
  text-align: center;
  padding: 11px 20px;
  font-size: 13px;
  line-height: 1.6;
  overflow-wrap: anywhere;
}
</style>