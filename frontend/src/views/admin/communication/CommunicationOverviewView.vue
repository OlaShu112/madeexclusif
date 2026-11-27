<script setup>

import {
  computed,
  onMounted,
} from 'vue'

import {
  Mail,
  FileText,
  Megaphone,
  Bell,
  Workflow,
  AlertCircle,
  RefreshCw,
} from 'lucide-vue-next'

import {
  useCommunicationStore,
} from '@/stores/communication'

import CommunicationStatCard
  from '@/components/admin/communication/CommunicationStatCard.vue'

const communication = useCommunicationStore()

const overview = computed(() =>
  communication.overview ?? {}
)

/*
|--------------------------------------------------------------------------
| Resolve Overview Statistics
|--------------------------------------------------------------------------
|
| Missing fields are displayed as a dash rather than
| incorrectly reporting zero.
|
*/

function metric(...paths) {

  for (const path of paths) {

    const value = path
      .split('.')
      .reduce(
        (object, key) => object?.[key],
        overview.value
      )

    if (
      typeof value === 'number' ||
      typeof value === 'string'
    ) {
      return value
    }

  }

  return '—'
}

const cards = computed(() => [

  {
    title: 'Total Communications',
    value: metric(
      'communications.total',
      'summary.total_communications',
      'total_communications'
    ),
    description: 'Platform communication records',
    icon: Mail,
    tone: 'primary',
  },

  {
    title: 'Email Templates',
    value: metric(
      'templates.total',
      'email_templates.total',
      'summary.total_templates',
      'total_templates'
    ),
    description: 'Available email templates',
    icon: FileText,
    tone: 'success',
  },

  {
    title: 'Announcements',
    value: metric(
      'announcements.total',
      'summary.total_announcements',
      'total_announcements'
    ),
    description: 'Platform announcements',
    icon: Megaphone,
    tone: 'primary',
  },

  {
    title: 'Notifications',
    value: metric(
      'notifications.total',
      'summary.total_notifications',
      'total_notifications'
    ),
    description: 'In-app notification records',
    icon: Bell,
    tone: 'warning',
  },

  {
    title: 'Automation Rules',
    value: metric(
      'automation.total',
      'automation_rules.total',
      'summary.total_automation_rules',
      'total_automation_rules'
    ),
    description: 'Configured automation rules',
    icon: Workflow,
    tone: 'primary',
  },

  {
    title: 'Failed Deliveries',
    value: metric(
      'delivery.failed',
      'recipients.failed',
      'summary.failed_deliveries',
      'failed_deliveries'
    ),
    description: 'Unsuccessful delivery attempts',
    icon: AlertCircle,
    tone: 'danger',
  },

])

async function load() {

  try {
    await communication.loadOverview()
  } catch {
    // Display the store error.
  }

}

onMounted(load)

</script>

<template>

  <div class="communication-page">

    <div class="communication-section-header">

      <div>

        <h2>Overview</h2>

        <p class="communication-muted">
          Monitor your platform communication activity.
        </p>

      </div>

      <button
        class="communication-button"
        :disabled="communication.isLoading('overview')"
        @click="load"
      >

        <RefreshCw :size="16" />

        Refresh

      </button>

    </div>

    <div
      v-if="communication.isLoading('overview')"
      class="communication-panel"
      role="status"
    >
      Loading Communication overview...
    </div>

    <div
      v-else-if="communication.errorFor('overview')"
      class="communication-error"
      role="alert"
    >
      {{ communication.errorFor('overview') }}
    </div>

    <template v-else>

      <div class="communication-stat-grid">

        <CommunicationStatCard
          v-for="card in cards"
          :key="card.title"
          v-bind="card"
        />

      </div>

      <section class="communication-panel">

        <h3>Communication summary</h3>

        <p class="communication-muted">
          Live statistics retrieved from the IVISION API.
        </p>

        <div
          v-for="(group, name) in overview"
          :key="name"
          class="communication-summary-group"
        >

          <h4>
            {{ name.replaceAll('_', ' ') }}
          </h4>

          <template
            v-if="group && typeof group === 'object' && !Array.isArray(group)"
          >

            <div
              v-for="(value, key) in group"
              :key="key"
              class="communication-summary-row"
            >

              <span>
                {{ key.replaceAll('_', ' ') }}
              </span>

              <strong>
                {{ typeof value === 'object' ? '—' : value }}
              </strong>

            </div>

          </template>

          <strong v-else-if="typeof group !== 'object'">
            {{ group }}
          </strong>

        </div>

      </section>

    </template>

  </div>

</template>