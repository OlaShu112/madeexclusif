<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePlatformStore } from '@/stores/platform'

import {
  LayoutDashboard,
  ShoppingBag,
  Package,
  Store,
  Users,
  Mail,
  Wallet,
  CreditCard,
  Palette,
  Settings,
  ShieldCheck,
  ClipboardList,
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  LogOut,
  UserRound,
} from 'lucide-vue-next'

const props = defineProps({
  collapsed: Boolean,
})

const emit = defineEmits(['toggle', 'navigate'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const platform = usePlatformStore()

const loggingOut = ref(false)

/**
 * Dynamic platform branding.
 */

const platformName = computed(() =>
  platform.branding.platform_name || 'IVISION'
)

const administrationTitle = computed(() =>
  platform.branding.administration_title ||
  'Platform Administration'
)

const brandLogo = computed(() => {
  const branding = platform.branding

  if (props.collapsed) {
    return (
      branding.sidebarLogo ||
      branding.darkLogo ||
      branding.primaryLogo ||
      null
    )
  }

  return (
    branding.darkLogo ||
    branding.primaryLogo ||
    branding.lightLogo ||
    null
  )
})

const brandInitial = computed(() =>
  platformName.value.charAt(0).toUpperCase()
)

/**
 * Secure administrator logout.
 */

async function handleLogout() {
  if (loggingOut.value) return

  loggingOut.value = true

  try {
    await auth.logout()
    await router.replace('/admin/login')
  } finally {
    loggingOut.value = false
  }
}

/**
 * Administrator navigation sections.
 *
 * Existing working routes are preserved.
 * New modules become available when their
 * dedicated routes are registered.
 */

const sections = [
  {
    title: 'OVERVIEW',
    items: [
      {
        label: 'Dashboard',
        icon: LayoutDashboard,
        to: '/admin',
      },
    ],
  },
  {
    title: 'MANAGEMENT',
    items: [
      {
        label: 'Businesses',
        icon: Store,
        to: '/admin/stores',
      },
      {
        label: 'Customers',
        icon: Users,
        to: '/admin/customers',
      },
      {
        label: 'Orders',
        icon: ShoppingBag,
        to: '/admin/manage?tab=orders',
        paths: ['/admin/orders'],
      },
      {
        label: 'Products',
        icon: Package,
        to: '/admin/manage?tab=products',
        paths: ['/admin/products'],
      },
      {
        label: 'Subscriptions',
        icon: CreditCard,
        to: '/admin/subscriptions',
      },
      {
        label: 'Finance',
        icon: Wallet,
        to: '/admin/finance',
        paths: [
          '/admin/refunds',
          '/admin/payouts',
        ],
      },
      {
        label: 'Communication',
        icon: Mail,
        to: '/admin/communication',
      },
    ],
  },
  {
    title: 'SYSTEM',
    items: [
      {
        label: 'Platform Branding',
        icon: Palette,
        to: '/admin/branding',
      },
      {
        label: 'Platform Settings',
        icon: Settings,
        to: '/admin/settings',
      },
      {
        label: 'Security',
        icon: ShieldCheck,
        to: '/admin/security',
      },
      {
        label: 'Audit Logs',
        icon: ClipboardList,
        to: '/admin/audit-logs',
      },
    ],
  },
]

/**
 * Check whether a dedicated navigation route
 * has been registered.
 */

function isAvailable(item) {
  if (!item.to) return false

  const resolved = router.resolve(item.to)

  return resolved.matched.some(record =>
    record.path === resolved.path
  )
}

/**
 * Determine the active navigation item.
 */

function isActive(item) {
  if (!item.to) return false

  if (item.to === '/admin') {
    return [
      '/admin',
      '/admin/analytics',
    ].includes(route.path)
  }

  if (
    item.paths?.some(path =>
      route.path === path ||
      route.path.startsWith(`${path}/`)
    )
  ) {
    return true
  }

  const destination = router.resolve(item.to)

  if (Object.keys(destination.query).length) {
    return (
      route.path === destination.path &&
      Object.entries(destination.query).every(
        ([key, value]) => route.query[key] === value
      )
    )
  }

  return (
    route.path === destination.path ||
    route.path.startsWith(`${destination.path}/`)
  )
}
</script>

<template>
  <aside
    class="admin-sidebar"
    :class="{ collapsed }"
    :style="platform.themeVariables"
  >
    <!-- Administration branding -->

    <div class="sidebar-brand">
      <img
        v-if="brandLogo"
        :src="brandLogo"
        :alt="platformName"
        class="platform-logo"
      />

      <div
        v-else
        class="brand-symbol"
        aria-hidden="true"
      >
        {{ brandInitial }}
      </div>

      <div
        v-if="!collapsed"
        class="brand-information"
      >
        <strong>
          {{ platformName }}
        </strong>

        <small>
          {{ administrationTitle }}
        </small>
      </div>
    </div>

    <!-- Administration navigation -->

    <nav
      class="sidebar-navigation"
      aria-label="Platform administration navigation"
    >
      <section
        v-for="section in sections"
        :key="section.title"
        class="navigation-section"
      >
        <h4 v-if="!collapsed">
          {{ section.title }}
        </h4>

        <template
          v-for="item in section.items"
          :key="item.label"
        >
          <!-- Available navigation links -->

          <RouterLink
            v-if="isAvailable(item)"
            :to="item.to"
            class="navigation-link"
            :class="{ active: isActive(item) }"
            :title="item.label"
            :aria-label="item.label"
            :aria-current="
              isActive(item) ? 'page' : undefined
            "
            @click="emit('navigate')"
          >
            <component
              :is="item.icon"
              :size="19"
            />

            <span v-if="!collapsed">
              {{ item.label }}
            </span>
          </RouterLink>

          <!-- Modules awaiting route integration -->

          <div
            v-else
            class="navigation-link unavailable"
            :title="`${item.label} — awaiting integration`"
            :aria-label="`${item.label} — unavailable`"
            aria-disabled="true"
          >
            <component
              :is="item.icon"
              :size="19"
            />

            <span v-if="!collapsed">
              {{ item.label }}
            </span>
          </div>
        </template>
      </section>
    </nav>

    <!-- Sidebar footer -->

    <div class="sidebar-footer">

      <!-- Administrator profile -->

      <RouterLink
        to="/admin/profile"
        class="navigation-link"
        :class="{
          active: route.path === '/admin/profile'
        }"
        title="My Profile"
        aria-label="My Profile"
        @click="emit('navigate')"
      >
        <UserRound :size="19" />

        <span v-if="!collapsed">
          My Profile
        </span>
      </RouterLink>

      <!-- View public IVISION website -->

      <RouterLink
        to="/"
        class="navigation-link"
        title="View IVISION website"
        aria-label="View Storefront"
        @click="emit('navigate')"
      >
        <ExternalLink :size="19" />

        <span v-if="!collapsed">
          View Storefront
        </span>
      </RouterLink>

      <!-- Administrator logout -->

      <button
        type="button"
        class="logout-button"
        :disabled="loggingOut"
        title="Log Out"
        aria-label="Log Out"
        @click="handleLogout"
      >
        <LogOut :size="19" />

        <span v-if="!collapsed">
          {{
            loggingOut
              ? 'Logging out...'
              : 'Log Out'
          }}
        </span>
      </button>

      <!-- Collapse or expand sidebar -->

      <button
        class="collapse-button"
        type="button"
        :title="
          collapsed
            ? 'Expand Sidebar'
            : 'Collapse Sidebar'
        "
        :aria-label="
          collapsed
            ? 'Expand Sidebar'
            : 'Collapse Sidebar'
        "
        :aria-expanded="!collapsed"
        @click="emit('toggle')"
      >
        <ChevronRight
          v-if="collapsed"
          :size="20"
        />

        <ChevronLeft
          v-else
          :size="20"
        />

        <span v-if="!collapsed">
          Collapse Sidebar
        </span>
      </button>
    </div>
  </aside>
</template>

<style scoped>

/* Administration sidebar */

.admin-sidebar {
  width: 260px;
  height: 100dvh;
  display: flex;
  flex-direction: column;

  background:
    var(--platform-sidebar, #111827);

  color:
    var(--platform-sidebar-text, #E5E7EB);

  font-family:
    var(--platform-font, system-ui, sans-serif);

  transition: width 0.2s ease;
  flex-shrink: 0;
  overflow: hidden;
}

.admin-sidebar.collapsed {
  width: 76px;
}

/* Administration branding */

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 90px;
  padding: 18px;

  border-bottom:
    1px solid rgba(255, 255, 255, 0.10);
}

.sidebar-brand img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  flex-shrink: 0;
}

.brand-symbol {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;

  background:
    var(--platform-accent, #C9A86A);

  color: #111827;
  font-size: 24px;
  font-weight: 800;
  border-radius: 9px;
  flex-shrink: 0;
}

.brand-information {
  min-width: 0;
}

.sidebar-brand strong {
  display: block;

  color:
    var(--platform-sidebar-text, #E5E7EB);

  font-size: 19px;
  font-weight: 800;
  letter-spacing: 1.5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-brand small {
  display: block;
  margin-top: 5px;
  color: inherit;
  opacity: 0.65;
  font-size: 9px;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  line-height: 1.5;
}

/* Sidebar navigation */

.sidebar-navigation {
  flex: 1;
  overflow-y: auto;
  padding: 16px 12px;
  scrollbar-width: thin;
}

.navigation-section {
  margin-bottom: 22px;
}

.navigation-section h4 {
  margin: 0 0 10px;
  padding-left: 12px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 1.3px;
  color: inherit;
  opacity: 0.55;
}

/* Navigation links */

.navigation-link {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 43px;
  padding: 10px 12px;
  margin-bottom: 3px;
  border-radius: 7px;
  text-decoration: none;

  color:
    var(--platform-sidebar-text, #E5E7EB);

  font-size: 13px;
  white-space: nowrap;
  transition:
    background 0.15s,
    color 0.15s;

  box-sizing: border-box;
}

.navigation-link svg {
  flex-shrink: 0;
}

.navigation-link:not(.unavailable):hover {
  background: rgba(255, 255, 255, 0.07);
}

.navigation-link.active {
  background:
    var(--platform-active-menu, #374151);

  color:
    var(--platform-sidebar-text, #E5E7EB);

  font-weight: 600;

  box-shadow:
    inset 3px 0 0
    var(--platform-accent, #C9A86A);
}

.navigation-link.unavailable {
  opacity: 0.42;
  cursor: not-allowed;
}

/* Sidebar footer */

.sidebar-footer {
  padding: 12px;

  border-top:
    1px solid rgba(255, 255, 255, 0.10);
}

/* Administrator logout button */

.logout-button {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 43px;
  padding: 10px 12px;
  margin-bottom: 5px;
  border: none;
  border-radius: 7px;
  background: transparent;
  color: #f87171;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s ease;
}

.logout-button svg {
  flex-shrink: 0;
}

.logout-button:hover {
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
}

.logout-button:disabled {
  opacity: 0.5;
  cursor: wait;
}

/* Sidebar collapse button */

.collapse-button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: transparent;
  border: none;

  color:
    var(--platform-sidebar-text, #E5E7EB);

  opacity: 0.7;
  font-size: 12px;
  cursor: pointer;
  white-space: nowrap;
}

.collapse-button:hover {
  color:
    var(--platform-accent, #C9A86A);

  opacity: 1;
}

/* Collapsed sidebar */

.collapsed .sidebar-brand {
  justify-content: center;
  padding: 18px 10px;
}

.collapsed .navigation-link,
.collapsed .logout-button,
.collapsed .collapse-button {
  justify-content: center;
  padding-left: 0;
  padding-right: 0;
}

.collapsed .navigation-section {
  margin-bottom: 15px;
}

/* Keyboard accessibility */

.navigation-link:focus-visible,
.logout-button:focus-visible,
.collapse-button:focus-visible {
  outline: 2px solid
    var(--platform-accent, #C9A86A);

  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .admin-sidebar,
  .navigation-link,
  .logout-button {
    transition: none;
  }
}

</style>