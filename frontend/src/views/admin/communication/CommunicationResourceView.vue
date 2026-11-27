<script setup>

import {
  computed,
  ref,
  onMounted,
} from 'vue'

import {
  RefreshCw,
  Inbox,
  ChevronLeft,
  ChevronRight,
  Search,
} from 'lucide-vue-next'

import {
  useCommunicationStore,
} from '@/stores/communication'

const props = defineProps({

  resource: {
    type: String,
    required: true,
  },

})

const communication = useCommunicationStore()

const page = ref(1)

const perPage = ref(20)

const search = ref('')

const definitions = {

  messages: {
    title: 'Messages',
    description: 'Monitor platform communication messages.',
    search: true,
    columns: [
      'reference',
      'name',
      'channel',
      'subject',
      'status',
      'total_recipients',
      'sent_count',
      'created_at',
    ],
  },

  templates: {
    title: 'Email Templates',
    description: 'View available system and custom email templates.',
    search: true,
    columns: [
      'name',
      'category',
      'subject',
      'language',
      'is_active',
      'is_system',
      'updated_at',
    ],
  },

  announcements: {
    title: 'Announcements',
    description: 'Monitor platform announcements.',
    search: true,
    columns: [
      'title',
      'type',
      'audience_type',
      'status',
      'is_pinned',
      'starts_at',
      'ends_at',
    ],
  },

  notifications: {
    title: 'Notifications',
    description: 'Monitor platform notification records.',
    columns: [
      'id',
      'user_id',
      'type',
      'title',
      'read_at',
      'created_at',
    ],
  },

  recipients: {
    title: 'Recipients',
    description: 'Monitor Communication recipients and delivery status.',
    columns: [
      'id',
      'recipient_name',
      'platform_communication_id',
      'status',
      'attempts',
      'sent_at',
      'delivered_at',
    ],
  },

  delivery: {
    title: 'Delivery Logs',
    description: 'Monitor successful and unsuccessful deliveries.',
    columns: [
      'id',
      'platform_communication_id',
      'status',
      'attempts',
      'queued_at',
      'sent_at',
      'delivered_at',
      'failed_at',
    ],
  },

  automation: {
    title: 'Automation',
    description: 'View configured Communication automation rules.',
    columns: [
      'name',
      'event',
      'channel',
      'delay_minutes',
      'is_active',
      'last_triggered_at',
    ],
  },

  preferences: {
    title: 'Communication Preferences',
    description: 'Monitor communication preferences and consent records.',
    columns: [
      'user_id',
      'marketing_email',
      'marketing_sms',
      'product_updates',
      'in_app_notifications',
      'consent_version',
      'updated_at',
    ],
  },

  settings: {
    title: 'Communication Settings',
    description: 'View the current Communication configuration.',
    columns: [],
  },

}

const definition = computed(() =>
  definitions[props.resource]
)

const result = computed(() =>
  communication.resource(props.resource)
)

const rows = computed(() =>
  result.value.data
)

const meta = computed(() =>
  result.value.meta
)

const loading = computed(() =>
  communication.isLoading(props.resource)
)

const error = computed(() =>
  communication.errorFor(props.resource)
)

function label(value) {

  return value
    .replaceAll('_', ' ')
    .replace(/\b\w/g, character =>
      character.toUpperCase()
    )

}

function formatValue(value, key) {

  if (value === null || value === undefined) {
    return '—'
  }

  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }

  if (key.startsWith('is_')) {
    return Number(value) === 1 ? 'Yes' : 'No'
  }

  if (key.endsWith('_at')) {

    const date = new Date(value)

    if (!Number.isNaN(date.getTime())) {

      return date.toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
      })

    }

  }

  if (typeof value === 'object') {
    return JSON.stringify(value)
  }

  return String(value)

}

async function load() {

  try {

    if (props.resource === 'settings') {

      await communication.loadSettings()

      return

    }

    const params = {
      page: page.value,
      per_page: perPage.value,
    }

    if (definition.value.search && search.value.trim()) {
      params.search = search.value.trim()
    }

    await communication.loadResource(
      props.resource,
      params
    )

  } catch {
    // Display the store error.
  }

}

function applySearch() {

  page.value = 1

  load()

}

function changePage(value) {

  if (
    value < 1 ||
    value > (meta.value.last_page ?? 1)
  ) {
    return
  }

  page.value = value

  load()

}

onMounted(load)

</script>

<template>

  <div class="communication-page">

    <div class="communication-section-header">

      <div>

        <h2>{{ definition.title }}</h2>

        <p class="communication-muted">
          {{ definition.description }}
        </p>

      </div>

      <button
        class="communication-button"
        :disabled="loading"
        @click="load"
      >

        <RefreshCw :size="16" />

        Refresh

      </button>

    </div>

    <section class="communication-panel">

      <div
        v-if="resource !== 'settings'"
        class="communication-toolbar"
      >

        <form
          v-if="definition.search"
          class="communication-search"
          @submit.prevent="applySearch"
        >

          <input
            v-model="search"
            type="search"
            aria-label="Search records"
            placeholder="Search records..."
            maxlength="150"
          />

          <button
            class="communication-button"
            type="submit"
            :disabled="loading"
          >

            <Search :size="16" />

            Search

          </button>

        </form>

        <label class="communication-page-size">

          Records per page

          <select
            v-model.number="perPage"
            @change="applySearch"
          >

            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>

          </select>

        </label>

      </div>

      <div
        v-if="loading"
        class="communication-empty"
        role="status"
      >
        Loading records...
      </div>

      <div
        v-else-if="error"
        class="communication-error"
        role="alert"
      >
        {{ error }}
      </div>

      <template v-else-if="resource === 'settings'">

        <div
          v-for="(value, key) in communication.settings"
          :key="key"
          class="communication-summary-group"
        >

          <h4>{{ label(key) }}</h4>

          <pre v-if="typeof value === 'object'">{{ JSON.stringify(value, null, 2) }}</pre>

          <span v-else>{{ formatValue(value, key) }}</span>

        </div>

      </template>

      <div
        v-else-if="rows.length === 0"
        class="communication-empty"
      >

        <Inbox :size="40" />

        <h3>No records found</h3>

        <p class="communication-muted">
          There are no matching records to display.
        </p>

      </div>

      <template v-else>

        <div class="communication-table-wrapper">

          <table class="communication-table">

            <thead>

              <tr>

                <th
                  v-for="column in definition.columns"
                  :key="column"
                  scope="col"
                >
                  {{ label(column) }}
                </th>

              </tr>

            </thead>

            <tbody>

              <tr
                v-for="record in rows"
                :key="record.id"
              >

                <td
                  v-for="column in definition.columns"
                  :key="column"
                >

                  <span
                    v-if="column === 'status'"
                    class="communication-status"
                    :class="`status-${record[column]}`"
                  >
                    {{ formatValue(record[column], column) }}
                  </span>

                  <span v-else>
                    {{ formatValue(record[column], column) }}
                  </span>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

        <div class="communication-pagination">

          <span class="communication-muted">

            Showing {{ meta.from ?? 0 }}
            to {{ meta.to ?? 0 }}
            of {{ meta.total ?? 0 }} records

          </span>

          <div class="communication-pagination-controls">

            <button
              class="communication-button"
              :disabled="page <= 1 || loading"
              @click="changePage(page - 1)"
            >

              <ChevronLeft :size="16" />

              Previous

            </button>

            <span>
              {{ page }} / {{ meta.last_page ?? 1 }}
            </span>

            <button
              class="communication-button"
              :disabled="page >= (meta.last_page ?? 1) || loading"
              @click="changePage(page + 1)"
            >

              Next

              <ChevronRight :size="16" />

            </button>

          </div>

        </div>

      </template>

    </section>

  </div>

</template>