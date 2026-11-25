<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'

const route = useRoute()
const page = ref(null)
const loading = ref(true)
const error = ref('')
let requestVersion = 0

async function load() {
  const version = ++requestVersion

  loading.value = true
  error.value = ''
  page.value = null

  try {
    const result = await api(
      `/stores/${encodeURIComponent(route.params.store)}` +
      `/pages/${encodeURIComponent(route.params.page)}`
    )

    if (version === requestVersion) {
      page.value = result
    }
  } catch (e) {
    if (version === requestVersion) {
      error.value = 'This page is currently unavailable.'
    }
  } finally {
    if (version === requestVersion) {
      loading.value = false
    }
  }
}

watch(
  () => [route.params.store, route.params.page],
  load,
  { immediate: true }
)
</script>

<template>
  <main class="website-page">
    <p v-if="loading">Loading page...</p>
    <p v-else-if="error">{{ error }}</p>

    <article v-else-if="page">
      <h1>{{ page.title }}</h1>
      <div class="page-content">{{ page.content }}</div>
    </article>
  </main>
</template>

<style scoped>
.website-page {
  max-width: 1000px;
  width: 100%;
  margin: 0 auto;
  padding: 64px 24px;
  box-sizing: border-box;
}

h1 {
  font-size: 36px;
  margin-bottom: 28px;
}

.page-content {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  line-height: 1.8;
}
</style>
