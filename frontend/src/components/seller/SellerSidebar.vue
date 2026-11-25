<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import {
  LayoutDashboard,
  Package,
  ShoppingBag,
  Users,
  Globe,
  Palette,
  Images,
  Wallet,
  CreditCard,
  Banknote,
  ChartNoAxesCombined,
  Settings,
  UserRound,
  ExternalLink,
  LogOut,
  Building2,
  X,
} from 'lucide-vue-next'

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['navigate'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loggingOut = ref(false)
const logoutError = ref('')

const isApproved = computed(() =>
  props.store?.status === 'active'
)

const storeUrl = computed(() =>
  isApproved.value && props.store?.slug
    ? `/s/${props.store.slug}`
    : null
)

const sections = [
  {
    title: 'OVERVIEW',
    items: [
      {
        label: 'Dashboard',
        icon: LayoutDashboard,
        to: '/seller',
      },
      {
        label: 'Analytics',
        icon: ChartNoAxesCombined,
      },
    ],
  },
  {
    title: 'COMMERCE',
    items: [
      {
        label: 'Products',
        icon: Package,
        to: '/seller?tab=products',
        requiresActive: true,
      },
      {
        label: 'Orders',
        icon: ShoppingBag,
        to: '/seller?tab=orders',
        requiresActive: true,
      },
      {
        label: 'Customers',
        icon: Users,
      },
    ],
  },
  {
    title: 'WEBSITE MANAGEMENT',
    items: [
      {
        label: 'My Website',
        icon: Globe,
        website: true,
      },
      {
        label: 'Customise Website',
        icon: Palette,
        to: '/seller/website/theme',
        requiresActive: true,
      },
      {
        label: 'Media Library',
        icon: Images,
        to: '/seller/website/media',
        requiresActive: true,
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
        label: 'Payment Settings',
        icon: CreditCard,
      },
      {
        label: 'Payouts',
        icon: Banknote,
      },
    ],
  },
  {
    title: 'SYSTEM',
    items: [
      {
        label: 'My Profile',
        icon: UserRound,
      },
      {
        label: 'Settings',
        icon: Settings,
      },
    ],
  },
]

function itemDestination(item) {
  if (item.requiresActive && !isApproved.value) {
    return null
  }

  if (item.website) {
    return storeUrl.value
  }

  return item.to || null
}

function isActive(item) {
  if (!item.to) return false

  if (item.to === '/seller') {
    return route.path === '/seller' && !route.query.tab
  }

  if (item.to === '/seller?tab=products') {
    return (
      route.path === '/seller' &&
      route.query.tab === 'products'
    )
  }

  if (item.to === '/seller?tab=orders') {
    return (
      route.path === '/seller' &&
      route.query.tab === 'orders'
    )
  }

  return route.path === item.to
}

async function handleLogout() {
  if (loggingOut.value) return

  loggingOut.value = true
  logoutError.value = ''

  try {
    await auth.logout()

    await router.replace({
      name: 'seller-login',
    })
  } catch {
    logoutError.value =
      'Unable to log out. Please try again.'
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <aside class="seller-sidebar">

    <!-- IVISION branding -->

    <div class="sidebar-brand">

      <div>
        <strong>IVISION</strong>
        <small>SELLER PORTAL</small>
      </div>

      <button
        type="button"
        class="close-button"
        aria-label="Close sidebar"
        @click="emit('navigate')"
      >
        <X :size="20" />
      </button>

    </div>

    <!-- Business identity -->

    <div class="store-identity">

      <img
        v-if="store?.logo_url"
        :src="store.logo_url"
        :alt="store.name"
        class="store-logo"
      />

      <div v-else class="store-logo-placeholder">
        <Building2 :size="24" />
      </div>

      <div class="store-details">
        <strong>
          {{ store?.name || 'Your Business' }}
        </strong>

        <small v-if="store?.status">
          {{ store.status }}
        </small>

        <small v-else>
          Business account
        </small>
      </div>

    </div>

    <!-- Seller navigation -->

    <nav
      class="sidebar-navigation"
      aria-label="Seller navigation"
    >

      <section
        v-for="section in sections"
        :key="section.title"
        class="navigation-section"
      >

        <h4>{{ section.title }}</h4>

        <template
          v-for="item in section.items"
          :key="item.label"
        >

          <RouterLink
            v-if="itemDestination(item)"
            :to="itemDestination(item)"
            class="navigation-link"
            :class="{ active: isActive(item) }"
            @click="emit('navigate')"
          >
            <component
              :is="item.icon"
              :size="19"
            />

            <span>{{ item.label }}</span>

          </RouterLink>

          <div
            v-else
            class="navigation-link unavailable"
            aria-disabled="true"
            :title="`${item.label} — unavailable`"
          >
            <component
              :is="item.icon"
              :size="19"
            />

            <span>{{ item.label }}</span>

          </div>

        </template>

      </section>

    </nav>

    <!-- Sidebar footer -->

    <div class="sidebar-footer">

      <RouterLink
        v-if="storeUrl"
        :to="storeUrl"
        class="navigation-link"
        @click="emit('navigate')"
      >
        <ExternalLink :size="19" />

        <span>View Store</span>
      </RouterLink>

      <button
        type="button"
        class="logout-button"
        :disabled="loggingOut"
        @click="handleLogout"
      >
        <LogOut :size="19" />

        <span>
          {{ loggingOut ? 'Logging out...' : 'Log Out' }}
        </span>

      </button>

      <p
        v-if="logoutError"
        class="logout-error"
        role="alert"
      >
        {{ logoutError }}
      </p>

    </div>

  </aside>
</template>

<style scoped>
.seller-sidebar {
  width: 260px;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #151515;
  color: #fff;
  overflow: hidden;
  flex-shrink: 0;
}

.sidebar-brand {
  min-height: 76px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #303030;
}

.sidebar-brand strong {
  display: block;
  font-size: 23px;
  letter-spacing: 3px;
  font-weight: 700;
}

.sidebar-brand small {
  display: block;
  margin-top: 5px;
  font-size: 10px;
  letter-spacing: 2px;
  color: #aaa;
}

.close-button {
  display: none;
  border: none;
  background: transparent;
  color: #fff;
  cursor: pointer;
}

.store-identity {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 16px;
  border-bottom: 1px solid #303030;
}

.store-logo,
.store-logo-placeholder {
  width: 46px;
  height: 46px;
  border-radius: 8px;
  flex-shrink: 0;
}

.store-logo {
  object-fit: contain;
  background: #fff;
}

.store-logo-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #303030;
  color: #ddd;
}

.store-details {
  min-width: 0;
}

.store-details strong {
  display: block;
  font-size: 14px;
  overflow-wrap: anywhere;
}

.store-details small {
  display: block;
  margin-top: 5px;
  color: #aaa;
  font-size: 11px;
  text-transform: capitalize;
}

.sidebar-navigation {
  flex: 1;
  overflow-y: auto;
  padding: 18px 12px;
}

.navigation-section {
  margin-bottom: 24px;
}

.navigation-section h4 {
  margin: 0 0 10px;
  padding-left: 12px;
  color: #999;
  font-size: 10px;
  letter-spacing: 1.2px;
  font-weight: 600;
}

.navigation-link,
.logout-button {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 43px;
  padding: 10px 12px;
  margin-bottom: 4px;
  border-radius: 7px;
  text-decoration: none;
  font-size: 13px;
  color: #d5d5d5;
}

.navigation-link svg,
.logout-button svg {
  flex-shrink: 0;
}

.navigation-link:not(.unavailable):hover {
  background: #292929;
  color: #fff;
}

.navigation-link.active {
  background: #333;
  color: #fff;
  font-weight: 600;
}

.navigation-link.unavailable {
  opacity: 0.4;
  cursor: not-allowed;
}

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid #303030;
}

.logout-button {
  width: 100%;
  border: none;
  background: transparent;
  color: #f87171;
  cursor: pointer;
  text-align: left;
}

.logout-button:hover {
  background: rgba(239, 68, 68, 0.12);
}

.logout-button:disabled {
  opacity: 0.5;
  cursor: wait;
}

.logout-error {
  color: #fca5a5;
  font-size: 12px;
  padding: 0 12px;
}

@media (max-width: 900px) {
  .close-button {
    display: block;
  }
}
</style>