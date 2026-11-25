<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import {
  LayoutDashboard,
  ShoppingBag,
  Package,
  Store,
  Users,
  Mail,
  Wallet,
  Receipt,
  Percent,
  Banknote,
  Landmark,
  Settings,
  ShieldCheck,
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  Tags,
  Bell,
  LogOut,
  UserRound,
} from 'lucide-vue-next'

defineProps({
  collapsed: Boolean,
})

const emit = defineEmits(['toggle', 'navigate'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const loggingOut = ref(false)

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
    title: 'COMMERCE',
    items: [
      {
        label: 'Orders',
        icon: ShoppingBag,
        to: '/admin/manage?tab=orders',
      },
      {
        label: 'Products',
        icon: Package,
        to: '/admin/manage?tab=products',
      },
      {
        label: 'Categories',
        icon: Tags,
      },
      {
        label: 'Businesses',
        icon: Store,
        to: '/admin/stores',
      },
      {
        label: 'Customers',
        icon: Users,
      },
      {
        label: 'Subscribers',
        icon: Mail,
      },
    ],
  },
  {
    title: 'FINANCE',
    items: [
      {
        label: 'Financial Overview',
        icon: Wallet,
      },
      {
        label: 'Commissions',
        icon: Percent,
      },
      {
        label: 'Refunds',
        icon: Receipt,
      },
      {
        label: 'Seller Payouts',
        icon: Banknote,
      },
      {
        label: 'Stripe Connect',
        icon: Landmark,
      },
    ],
  },
  {
    title: 'COMMUNICATIONS',
    items: [
      {
        label: 'Email Management',
        icon: Mail,
      },
      {
        label: 'Notifications',
        icon: Bell,
      },
    ],
  },
  {
    title: 'SYSTEM',
    items: [
      {
        label: 'My Profile',
        icon: UserRound,
        to: '/admin/profile',
      },
      {
        label: 'Audit Logs',
        icon: ShieldCheck,
      },
      {
        label: 'Settings',
        icon: Settings,
      },
    ],
  },
]

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

  return route.fullPath === item.to
}
</script>

<template>
  <aside
    class="admin-sidebar"
    :class="{ collapsed }"
  >
    <!-- Administration branding -->

    <div class="sidebar-brand">
      <img
        src="/images/made-exclusif-logo.png"
        alt="Made Exclusif"
      />

      <div v-if="!collapsed">
        <strong>MADE EXCLUSIF</strong>
        <small>ADMINISTRATION</small>
      </div>
    </div>

    <!-- Administration navigation -->

    <nav class="sidebar-navigation">
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
            v-if="item.to"
            :to="item.to"
            class="navigation-link"
            :class="{ active: isActive(item) }"
            :title="item.label"
            :aria-label="item.label"
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

          <!-- Navigation features coming soon -->

          <div
            v-else
            class="navigation-link unavailable"
            :title="`${item.label} — coming soon`"
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

      <!-- View public storefront -->

      <RouterLink
        to="/"
        class="navigation-link"
        title="View storefront"
        aria-label="View storefront"
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
          {{ loggingOut ? 'Logging out...' : 'Log Out' }}
        </span>
      </button>

      <!-- Collapse or expand sidebar -->

      <button
        class="collapse-button"
        type="button"
        aria-label="Toggle sidebar"
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
  background: #151515;
  color: #fff;
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
  border-bottom: 1px solid #303030;
}

.sidebar-brand img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  flex-shrink: 0;
}

.sidebar-brand strong {
  color: #c9a86a;
  font-size: 13px;
  letter-spacing: 1px;
  white-space: nowrap;
}

.sidebar-brand small {
  display: block;
  margin-top: 5px;
  color: #999;
  font-size: 9px;
  letter-spacing: 2px;
}

/* Sidebar navigation */

.sidebar-navigation {
  flex: 1;
  overflow-y: auto;
  padding: 16px 12px;
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
  color: #929292;
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
  color: #d2d2d2;
  font-size: 13px;
  white-space: nowrap;
  transition: background 0.15s;
}

.navigation-link svg {
  flex-shrink: 0;
}

.navigation-link:not(.unavailable):hover {
  background: #292929;
  color: #fff;
}

.navigation-link.active {
  background: #383024;
  color: #d7b575;
  font-weight: 600;
}

.navigation-link.unavailable {
  opacity: 0.42;
  cursor: not-allowed;
}

/* Sidebar footer */

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid #303030;
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
  color: #aaa;
  font-size: 12px;
  cursor: pointer;
  white-space: nowrap;
}

.collapse-button:hover {
  color: #c9a86a;
}
</style>