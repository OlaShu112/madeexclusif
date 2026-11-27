<script setup>

import { ref, onMounted } from 'vue'

import {
  Activity,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
} from 'lucide-vue-next'

import systemApi from '@/services/systemApi'

const loading = ref(false)
const error = ref('')
const health = ref(null)

async function loadHealth() {
  if (loading.value) return

  loading.value = true
  error.value = ''
  health.value = null

  try {
    const response = await systemApi.health()

    health.value = response.data
  } catch (exception) {
    error.value = exception?.status === 503
      ? 'One or more system dependencies are unhealthy.'
      : exception?.message || 'Unable to check system health.'
  } finally {
    loading.value = false
  }
}

function formatLabel(value) {
  return value
    .replaceAll('_', ' ')
    .replace(/\b\w/g, letter => letter.toUpperCase())
}

onMounted(loadHealth)

</script>

<template>

  <section class="system-health">

    <header class="health-header">

      <div>
        <small>IVISION PLATFORM ADMINISTRATION</small>

        <h1>System Health</h1>

        <p>
          Monitor essential platform dependencies.
        </p>
      </div>

      <button
        type="button"
        :disabled="loading"
        @click="loadHealth"
      >
        <RefreshCw :size="17" />

        {{ loading ? 'Checking...' : 'Refresh' }}
      </button>

    </header>

    <div v-if="loading" role="status">
      Checking platform services...
    </div>

    <div
      v-else-if="error"
      class="health-error"
      role="alert"
    >
      {{ error }}
    </div>

    <template v-else-if="health">

      <div class="health-overview">

        <Activity :size="28" />

        <div>
          <small>Overall system status</small>

          <h2>{{ formatLabel(health.status) }}</h2>
        </div>

      </div>

      <div class="health-grid">

        <article
          v-for="(check, name) in health.checks"
          :key="name"
          class="health-card"
        >

          <CheckCircle2
            v-if="check.healthy"
            :size="25"
            class="healthy"
          />

          <AlertCircle
            v-else
            :size="25"
            class="unhealthy"
          />

          <h3>{{ formatLabel(name) }}</h3>

          <p>
            {{ check.healthy ? 'Operational' : 'Unavailable' }}
          </p>

          <small>{{ check.message }}</small>

          <p v-if="check.missing_tables?.length">
            Missing tables:
            {{ check.missing_tables.join(', ') }}
          </p>

        </article>

      </div>

      <p class="health-timestamp">
        Last checked:
        {{ new Date(health.checked_at).toLocaleString() }}
      </p>

    </template>

  </section>

</template>

<style scoped>

.system-health {
  color: var(--platform-text, #111827);
}

.health-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 28px;
}

.health-header small {
  font-size: 10px;
  letter-spacing: 1.5px;
  opacity: 0.7;
}

.health-header h1 {
  margin: 8px 0;
  font-size: 28px;
}

.health-header p {
  opacity: 0.7;
}

.health-header button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  border: 1px solid #9995;
  border-radius: 8px;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.health-header button:disabled {
  opacity: 0.5;
  cursor: wait;
}

.health-overview,
.health-card {
  padding: 24px;
  border: 1px solid #9994;
  border-radius: 12px;
  background: var(--platform-card, transparent);
}

.health-overview {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 24px;
}

.health-overview h2 {
  margin: 5px 0;
}

.health-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}

.health-card h3 {
  margin: 15px 0 8px;
}

.healthy {
  color: #16a34a;
}

.unhealthy,
.health-error {
  color: #dc2626;
}

.health-timestamp {
  font-size: 12px;
  opacity: 0.7;
  margin-top: 24px;
}

@media (max-width: 650px) {
  .health-grid {
    grid-template-columns: 1fr;
  }
}

</style>