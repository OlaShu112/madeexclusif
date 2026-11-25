<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

import {
  Search,
  ShoppingBag,
  UserRound,
  Menu,
  X
} from 'lucide-vue-next'

import {
  navigationItems,
  navigationDestination
} from '@/utils/websiteTheme'

const props = defineProps({
  store: {
    type: Object,
    required: true
  }
})

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()

const searchQuery = ref('')
const mobileMenuOpen = ref(false)

const settings = computed(() => ({
  show_logo: true,
  show_business_name: true,
  show_search: true,
  show_account: true,
  show_cart: true,
  sticky_header: false,
  ...(props.store.website_theme?.header_settings || {})
}))

const links = computed(
  () => navigationItems(props.store)
)

const homeLink = computed(() => ({
  name: 'store-home',
  params: {
    store: props.store.slug
  }
}))

const accountLink = computed(() =>
  auth.user
    ? { name: 'account' }
    : {
        name: 'login',
        query: {
          redirect: route.fullPath
        }
      }
)

const cartCount = computed(() =>
  String(cart.storeId) === String(props.store.id)
    ? cart.count
    : 0
)

function search() {
  const q = searchQuery.value.trim()

  if (!q) return

  router.push({
    name: 'store-shop',
    params: {
      store: props.store.slug
    },
    query: { q }
  })

  mobileMenuOpen.value = false
}

watch(
  () => route.fullPath,
  () => {
    mobileMenuOpen.value = false
    searchQuery.value = String(route.query.q || '')
  },
  { immediate: true }
)
</script>

<template>
  <header
    class="tenant-header"
    :class="{ sticky: settings.sticky_header }"
  >
    <div class="header-main">

      <button
        class="mobile-toggle"
        type="button"
        aria-label="Toggle navigation"
        aria-controls="store-navigation"
        :aria-expanded="mobileMenuOpen"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        <X v-if="mobileMenuOpen" :size="25" />
        <Menu v-else :size="25" />
      </button>

      <RouterLink
        :to="homeLink"
        class="business-brand"
        :aria-label="`${store.name} home`"
      >
        <img
          v-if="settings.show_logo && store.logo_url"
          :src="store.logo_url"
          :alt="store.name"
        />

        <span
          v-if="
            settings.show_business_name ||
            !settings.show_logo ||
            !store.logo_url
          "
        >
          {{ store.name }}
        </span>
      </RouterLink>

      <form
        v-if="settings.show_search"
        class="search-form"
        role="search"
        @submit.prevent="search"
      >
        <input
          v-model="searchQuery"
          type="search"
          maxlength="255"
          placeholder="Search products..."
          aria-label="Search products"
        />

        <button
          type="submit"
          aria-label="Submit search"
        >
          <Search :size="21" />
        </button>
      </form>

      <div class="header-actions">

        <RouterLink
          v-if="settings.show_account"
          :to="accountLink"
          class="header-action"
          aria-label="Customer account"
        >
          <UserRound :size="23" />
          <span>Account</span>
        </RouterLink>

        <RouterLink
          v-if="settings.show_cart"
          :to="{ name: 'cart' }"
          class="header-action"
          :aria-label="`Shopping bag, ${cartCount} items from this store`"
        >
          <div class="bag-icon">
            <ShoppingBag :size="23" />

            <span
              v-if="cartCount"
              class="bag-count"
            >
              {{ cartCount }}
            </span>
          </div>

          <span>Bag</span>
        </RouterLink>

      </div>
    </div>

    <nav
      id="store-navigation"
      class="store-navigation"
      :class="{ 'mobile-open': mobileMenuOpen }"
      aria-label="Business website navigation"
    >
      <RouterLink
        v-for="(item, index) in links"
        :key="index"
        :to="navigationDestination(store, item)"
      >
        {{ item.label }}
      </RouterLink>
    </nav>
  </header>
</template>

<style scoped>
.tenant-header {
  background: var(--store-header);
  color: var(--store-header-text);
  z-index: 30;
}

.tenant-header.sticky {
  position: sticky;
  top: 0;
}

.header-main {
  max-width: 1440px;
  margin: auto;
  padding: 22px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
}

.business-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 24px;
  font-weight: 700;
  min-width: 0;
  overflow-wrap: anywhere;
}

.business-brand img {
  width: 65px;
  height: 65px;
  object-fit: contain;
}

.tenant-header a {
  color: inherit;
  text-decoration: none;
}

.search-form {
  display: flex;
  flex: 1;
  max-width: 520px;
  background: #ffffff;
  color: #222222;
  border-radius: 5px;
  overflow: hidden;
}

.search-form input {
  flex: 1;
  min-width: 0;
  padding: 13px 16px;
  border: 0;
  background: transparent;
  color: #222222;
  font: inherit;
}

.search-form button {
  border: 0;
  padding: 10px 16px;
  background: transparent;
  color: #222222;
  cursor: pointer;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 24px;
}

.header-action {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 5px;
  font-size: 12px;
}

.bag-icon {
  position: relative;
}

.bag-count {
  position: absolute;
  top: -9px;
  right: -11px;
  background: var(--store-primary);
  color: var(--store-primary-text);
  border: 1px solid currentColor;
  border-radius: 50%;
  min-width: 19px;
  height: 19px;
  display: grid;
  place-items: center;
  font-size: 10px;
}

.store-navigation {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 28px;
  padding: 17px 24px;
  border-top: 1px solid currentColor;
  font-size: 14px;
}

.store-navigation a:hover {
  text-decoration: underline;
}

.mobile-toggle {
  display: none;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

@media (max-width: 800px) {
  .header-main {
    flex-wrap: wrap;
    gap: 15px;
    padding: 15px;
  }

  .mobile-toggle {
    display: block;
  }

  .business-brand {
    flex: 1;
    font-size: 18px;
  }

  .business-brand img {
    width: 45px;
    height: 45px;
  }

  .header-actions {
    gap: 15px;
  }

  .search-form {
    order: 4;
    flex-basis: 100%;
    max-width: none;
  }

  .store-navigation {
    display: none;
  }

  .store-navigation.mobile-open {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
}
</style>