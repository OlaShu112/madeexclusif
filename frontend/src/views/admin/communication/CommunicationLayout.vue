<script setup>

import {
  computed,
  onMounted,
  onBeforeUnmount,
  ref,
} from 'vue'

import {
  RouterLink,
  RouterView,
  useRoute,
} from 'vue-router'

import {
  LayoutDashboard,
  Mail,
  FileText,
  Megaphone,
  Bell,
  Users,
  Send,
  Workflow,
  SlidersHorizontal,
  Settings,
  RefreshCw,
} from 'lucide-vue-next'

import {
  useCommunicationStore,
} from '@/stores/communication'

import '@/assets/platform-communication.css'

const communication = useCommunicationStore()

const route = useRoute()

const ready = ref(false)

const navigation = [

  {
    label: 'Overview',
    name: 'admin-communication-overview',
    permission: 'communication.view',
    icon: LayoutDashboard,
  },

  {
    label: 'Messages',
    name: 'admin-communication-messages',
    permission: 'communication.messages.view',
    icon: Mail,
  },

  {
    label: 'Email Templates',
    name: 'admin-communication-templates',
    permission: 'communication.templates.view',
    icon: FileText,
  },

  {
    label: 'Announcements',
    name: 'admin-communication-announcements',
    permission: 'communication.announcements.view',
    icon: Megaphone,
  },

  {
    label: 'Notifications',
    name: 'admin-communication-notifications',
    permission: 'communication.notifications.view',
    icon: Bell,
  },

  {
    label: 'Recipients',
    name: 'admin-communication-recipients',
    permission: 'communication.recipients.view',
    icon: Users,
  },

  {
    label: 'Delivery Logs',
    name: 'admin-communication-delivery',
    permission: 'communication.delivery.view',
    icon: Send,
  },

  {
    label: 'Automation',
    name: 'admin-communication-automation',
    permission: 'communication.automation.view',
    icon: Workflow,
  },

  {
    label: 'Preferences',
    name: 'admin-communication-preferences',
    permission: 'communication.preferences.view',
    icon: SlidersHorizontal,
  },

  {
    label: 'Settings',
    name: 'admin-communication-settings',
    permission: 'communication.settings.view',
    icon: Settings,
  },

]

const visibleNavigation = computed(() =>
  navigation.filter(item =>
    communication.hasPermission(item.permission)
  )
)

const currentPageAllowed = computed(() => {

  const permission = route.meta.communicationPermission

  return Boolean(
    permission &&
    communication.hasPermission(permission)
  )
})

async function initialise() {

  ready.value = false

  try {

    await communication.loadPermissions()

    ready.value = true

  } catch {
    // Error is handled by the Communication store.
  }

}

onMounted(initialise)

onBeforeUnmount(() => {
  communication.clear()
})

</script>

<template>

  <section class="communication-centre">

    <header class="communication-page-header">

      <div>

        <div class="communication-eyebrow">
          IVISION PLATFORM ADMINISTRATION
        </div>

        <h1>Communication Centre</h1>

        <p class="communication-muted">
          Manage platform communications, email templates,
          announcements, notifications and automation.
        </p>

      </div>

      <button
        class="communication-button"
        :disabled="communication.isLoading('permissions')"
        @click="initialise"
      >

        <RefreshCw :size="16" />

        Refresh access

      </button>

    </header>

    <div
      v-if="communication.isLoading('permissions')"
      class="communication-panel"
      role="status"
    >
      Loading Communication permissions...
    </div>

    <div
      v-else-if="communication.errorFor('permissions')"
      class="communication-error"
      role="alert"
    >

      {{ communication.errorFor('permissions') }}

      <button
        class="communication-button"
        @click="initialise"
      >
        Retry
      </button>

    </div>

    <template v-else-if="ready">

      <nav
        class="communication-navigation"
        aria-label="Communication navigation"
      >

        <RouterLink
          v-for="item in visibleNavigation"
          :key="item.name"
          :to="{ name: item.name }"
          class="communication-nav-link"
          exact-active-class="communication-nav-active"
        >

          <component
            :is="item.icon"
            :size="17"
          />

          <span>{{ item.label }}</span>

        </RouterLink>

      </nav>

      <RouterView
        v-if="currentPageAllowed"
        :key="route.name"
      />

      <div
        v-else
        class="communication-error"
        role="alert"
      >
        You do not have permission to access this page.
      </div>

    </template>

  </section>

</template>