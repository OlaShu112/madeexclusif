<script setup>
import { ref, reactive, onMounted } from 'vue'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'

import {
  Search,
  RefreshCw,
  Eye,
  X
} from 'lucide-vue-next'

import '@/assets/platform-admin.css'

const platform = usePlatformStore()

const loading = ref(false)
const detailsLoading = ref(false)

const error = ref('')
const detailsError = ref('')

const statistics = ref({})
const actions = ref([])
const stores = ref([])

const logs = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0
})

const selectedLog = ref(null)
const detailsOpen = ref(false)

const filters = reactive({
  q: '',
  action: '',
  store_id: '',
  from: '',
  to: '',
  per_page: 20
})

function formatDate(value) {
  if (!value) return '—'

  return new Date(value).toLocaleString('en-GB')
}

function formatAction(action) {
  return (action || '')
    .replace(/[._]/g, ' ')
    .replace(/\b\w/g, letter => letter.toUpperCase())
}

async function loadLogs(page = 1) {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    const response = await api(
      '/admin/platform/audit-logs',
      {
        params: {
          ...filters,
          page
        }
      }
    )

    statistics.value = response.statistics
    actions.value = response.actions
    stores.value = response.stores
    logs.value = response.logs

  } catch (exception) {
    error.value =
      exception.message ||
      'Unable to load audit logs.'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.q = ''
  filters.action = ''
  filters.store_id = ''
  filters.from = ''
  filters.to = ''

  loadLogs()
}

async function viewLog(id) {
  if (detailsLoading.value) return

  detailsOpen.value = true
  detailsLoading.value = true
  selectedLog.value = null
  detailsError.value = ''

  try {
    const response = await api(
      `/admin/platform/audit-logs/${id}`
    )

    selectedLog.value = response.log

  } catch (exception) {
    detailsError.value =
      exception.message ||
      'Unable to retrieve audit event.'
  } finally {
    detailsLoading.value = false
  }
}

function closeDetails() {
  if (detailsLoading.value) return

  detailsOpen.value = false
  selectedLog.value = null
  detailsError.value = ''
}

onMounted(() => loadLogs())
</script>

<template>
  <section class="platform-admin-page">

    <header class="page-heading">

      <p class="eyebrow">
        {{ platform.branding.platform_name }}
        PLATFORM ADMINISTRATION
      </p>

      <h1>Audit Logs</h1>

      <p class="page-description">
        Monitor recorded administrative actions,
        business activities and platform events.
      </p>

    </header>

    <div class="statistics-grid">

      <div class="statistic-card">
        <span>Total Recorded Events</span>
        <strong>
          {{ statistics.total_events ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Events Today</span>
        <strong>
          {{ statistics.events_today ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Administrator Events</span>
        <strong>
          {{ statistics.administrator_events ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Business Events</span>
        <strong>
          {{ statistics.business_events ?? '—' }}
        </strong>
      </div>

    </div>

    <div
      v-if="error"
      class="message error"
      role="alert"
    >
      {{ error }}
    </div>

    <div class="admin-panel">

      <div class="panel-heading">

        <h2>Platform Activity History</h2>

        <button
          class="secondary-button"
          :disabled="loading"
          @click="loadLogs(logs.current_page)"
        >
          <RefreshCw :size="16" />
          Refresh
        </button>

      </div>

      <form
        class="filter-bar"
        @submit.prevent="loadLogs()"
      >

        <input
          v-model="filters.q"
          class="search-input"
          placeholder="Search activity, user or business..."
          aria-label="Search audit logs"
        />

        <select
          v-model="filters.action"
          aria-label="Filter by action"
        >
          <option value="">
            All Actions
          </option>

          <option
            v-for="action in actions"
            :key="action"
            :value="action"
          >
            {{ formatAction(action) }}
          </option>

        </select>

        <select
          v-model="filters.store_id"
          aria-label="Filter by business"
        >
          <option value="">
            All Businesses
          </option>

          <option
            v-for="store in stores"
            :key="store.id"
            :value="store.id"
          >
            {{ store.name }}
          </option>

        </select>

        <label>
          From
          <input
            v-model="filters.from"
            type="date"
            aria-label="Start date"
          />
        </label>

        <label>
          To
          <input
            v-model="filters.to"
            type="date"
            :min="filters.from || undefined"
            aria-label="End date"
          />
        </label>

        <button
          type="submit"
          class="primary-button"
          :disabled="loading"
        >
          <Search :size="16" />
          Search
        </button>

        <button
          type="button"
          class="secondary-button"
          :disabled="loading"
          @click="resetFilters"
        >
          Reset
        </button>

      </form>

      <p
        v-if="loading"
        class="empty-state"
      >
        Loading audit records...
      </p>

      <div
        v-else-if="!error"
        class="table-wrapper"
      >

        <table>

          <thead>
            <tr>
              <th>Event ID</th>
              <th>Action</th>
              <th>Performed By</th>
              <th>Business</th>
              <th>Entity</th>
              <th>Date and Time</th>
              <th>Details</th>
            </tr>
          </thead>

          <tbody>

            <tr
              v-for="log in logs.data"
              :key="log.id"
            >

              <td>#{{ log.id }}</td>

              <td>
                <strong>
                  {{ formatAction(log.action) }}
                </strong>

                <small>{{ log.action }}</small>
              </td>

              <td>
                {{ log.actor_name || 'Unknown actor' }}

                <small>
                  {{ log.actor_role || 'Not recorded' }}
                </small>
              </td>

              <td>
                {{ log.business_name || 'Not assigned' }}
              </td>

              <td>
                {{ log.entity_type || '—' }}

                <small v-if="log.entity_id">
                  #{{ log.entity_id }}
                </small>
              </td>

              <td>
                {{ formatDate(log.created_at) }}
              </td>

              <td>
                <button
                  class="secondary-button"
                  @click="viewLog(log.id)"
                >
                  <Eye :size="15" />
                  View
                </button>
              </td>

            </tr>

            <tr v-if="!logs.data.length">
              <td colspan="7" class="empty-state">
                No recorded audit events found.
              </td>
            </tr>

          </tbody>

        </table>

      </div>

      <div class="pagination">

        <span>
          {{ logs.total }} events —
          Page {{ logs.current_page }}
          of {{ logs.last_page }}
        </span>

        <div class="pagination-buttons">

          <button
            class="secondary-button"
            :disabled="
              loading ||
              logs.current_page <= 1
            "
            @click="loadLogs(logs.current_page - 1)"
          >
            Previous
          </button>

          <button
            class="secondary-button"
            :disabled="
              loading ||
              logs.current_page >= logs.last_page
            "
            @click="loadLogs(logs.current_page + 1)"
          >
            Next
          </button>

        </div>

      </div>

    </div>

    <!-- Audit event details -->

    <div
      v-if="detailsOpen"
      class="modal-overlay"
      @click.self="closeDetails"
      @keydown.esc="closeDetails"
    >

      <section
        class="modal-panel"
        role="dialog"
        aria-modal="true"
        aria-label="Audit event details"
      >

        <div class="panel-heading">

          <h2>Audit Event Details</h2>

          <button
            class="secondary-button"
            aria-label="Close audit event"
            :disabled="detailsLoading"
            @click="closeDetails"
          >
            <X :size="18" />
          </button>

        </div>

        <div
          v-if="detailsError"
          class="message error"
          role="alert"
        >
          {{ detailsError }}
        </div>

        <p v-if="detailsLoading">
          Loading audit event...
        </p>

        <template v-else-if="selectedLog">

          <div class="details-grid">

            <div>
              <span>Event ID</span>
              <strong>#{{ selectedLog.id }}</strong>
            </div>

            <div>
              <span>Action</span>
              <strong>
                {{ formatAction(selectedLog.action) }}
              </strong>
            </div>

            <div>
              <span>Performed By</span>
              <strong>
                {{ selectedLog.actor_name || 'Unknown actor' }}
              </strong>
            </div>

            <div>
              <span>Role</span>
              <strong>
                {{ selectedLog.actor_role || 'Not recorded' }}
              </strong>
            </div>

            <div>
              <span>Business</span>
              <strong>
                {{ selectedLog.business_name || 'Not assigned' }}
              </strong>
            </div>

            <div>
              <span>Date and Time</span>
              <strong>
                {{ formatDate(selectedLog.created_at) }}
              </strong>
            </div>

            <div>
              <span>Entity Type</span>
              <strong>
                {{ selectedLog.entity_type || '—' }}
              </strong>
            </div>

            <div>
              <span>Entity ID</span>
              <strong>
                {{ selectedLog.entity_id || '—' }}
              </strong>
            </div>

          </div>

          <h3>Recorded Event Information</h3>

          <pre>{{
            JSON.stringify(
              selectedLog.metadata,
              null,
              2
            )
          }}</pre>

          <p class="page-description">
            Audit records are read-only.
            Only approved event metadata is displayed.
          </p>

        </template>

      </section>

    </div>

  </section>
</template>