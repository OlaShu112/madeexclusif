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
  layout: 'double_row',
  show_logo: true,
  show_business_name: true,
  show_search: true,
  show_account: true,
  show_cart: true,
  sticky_header: false,
  ...(props.store.website_theme?.header_settings || {})
}))

const isSingleRow = computed(
  () => settings.value.layout === 'single_row'
)

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
    ? {
        name: 'store-account',
        params: {
          store: props.store.slug
        }
      }
    : {
        name: 'store-login',
        params: {
          store: props.store.slug
        },
        query: {
          redirect: router.resolve({
            name: 'store-account',
            params: {
              store: props.store.slug
            }
          }).fullPath
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
    :class="{
      sticky: settings.sticky_header,
      'single-row': isSingleRow,
      'double-row': !isSingleRow
    }"
  >
    <div class="header-main">

      <!-- Mobile navigation -->

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

      <!-- Business branding -->

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

      <!-- Single-row navigation -->

      <nav
        v-if="isSingleRow"
        id="store-navigation"
        class="store-navigation inline-navigation"
        :class="{ 'mobile-open': mobileMenuOpen }"
        aria-label="Business website navigation"
      >
        <RouterLink
          v-for="(item, index) in links"
          :key="index"
          :to="navigationDestination(store, item)"
          @click="mobileMenuOpen = false"
        >
          {{ item.label }}
        </RouterLink>
      </nav>

      <!-- Product search -->

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
          <Search :size="isSingleRow ? 17 : 21" />
        </button>
      </form>

      <!-- Customer controls -->

      <div class="header-actions">

        <RouterLink
          v-if="settings.show_account"
          :to="accountLink"
          class="header-action"
          aria-label="Customer account"
        >
          <UserRound
            v-if="!isSingleRow"
            :size="23"
          />

          <span>Account</span>
        </RouterLink>

        <RouterLink
          v-if="settings.show_cart"
          :to="{ name: 'cart' }"
          class="header-action"
          :aria-label="`Shopping bag, ${cartCount} items from this store`"
        >
          <!-- Existing spacious bag design -->

          <div
            v-if="!isSingleRow"
            class="bag-icon"
          >
            <ShoppingBag :size="23" />

            <span
              v-if="cartCount"
              class="bag-count"
            >
              {{ cartCount }}
            </span>
          </div>

          <span>Bag</span>

          <!-- Additional compact bag design -->

          <span
            v-if="isSingleRow && cartCount"
            class="inline-bag-count"
          >
            {{ cartCount }}
          </span>

        </RouterLink>

      </div>
    </div>

    <!-- Existing double-row navigation -->

    <nav
      v-if="!isSingleRow"
      id="store-navigation"
      class="store-navigation"
      :class="{ 'mobile-open': mobileMenuOpen }"
      aria-label="Business website navigation"
    >
      <RouterLink
        v-for="(item, index) in links"
        :key="index"
        :to="navigationDestination(store, item)"
        @click="mobileMenuOpen = false"
      >
        {{ item.label }}
      </RouterLink>
    </nav>

  </header>
</template>

<style scoped>
/* =====================================
   SHARED HEADER
===================================== */

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

/* =====================================
   EXISTING SPACIOUS SEARCH
===================================== */

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

.search-form:focus-within {
  outline: 2px solid currentColor;
  outline-offset: 2px;
}

/* =====================================
   EXISTING CUSTOMER CONTROLS
===================================== */

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

/* =====================================
   EXISTING DOUBLE-ROW NAVIGATION
===================================== */

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

/* =====================================
   ADDITIONAL CLASSIC SINGLE-ROW HEADER
===================================== */

.single-row {
  border-bottom: 1px solid rgba(128, 128, 128, 0.18);
}

.single-row .header-main {
  max-width: 1600px;
  padding: 12px 5%;
  min-height: 76px;
  gap: 24px;
  box-sizing: border-box;
}

.single-row .business-brand {
  flex-shrink: 0;
  max-width: 30%;
  gap: 16px;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 28px;
  font-weight: 600;
  letter-spacing: 1.2px;
}

.single-row .business-brand img {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
}

.single-row .inline-navigation {
  flex: 1;
  min-width: 0;
  align-items: center;
  border: 0;
  padding: 0;
  gap: 12px 24px;
  font-size: 14px;
  font-weight: 500;
}

.single-row .inline-navigation a {
  padding: 8px 0;
}

.single-row .search-form {
  flex: 0 1 175px;
  width: 175px;
  min-width: 110px;
  max-width: 175px;
  border: 1px solid rgba(128, 128, 128, 0.35);
  border-radius: 0;
  background: transparent;
  color: inherit;
}

.single-row .search-form input {
  padding: 11px 10px;
  font-size: 13px;
  color: inherit;
}

.single-row .search-form input::placeholder {
  color: inherit;
  opacity: 0.6;
}

.single-row .search-form button {
  padding: 8px;
  color: inherit;
}

.single-row .header-actions {
  flex-shrink: 0;
  gap: 20px;
}

.single-row .header-action {
  flex-direction: row;
  gap: 8px;
  font-size: 14px;
  white-space: nowrap;
}

.inline-bag-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 21px;
  height: 21px;
  padding: 0 4px;
  border-radius: 20px;
  background: var(--store-primary);
  color: var(--store-primary-text);
  font-size: 11px;
  box-sizing: border-box;
}

/* =====================================
   SINGLE-ROW TABLET RESPONSIVENESS
===================================== */

@media (max-width: 1200px) {
  .single-row .header-main {
    padding: 12px 24px;
    gap: 18px;
  }

  .single-row .business-brand {
    font-size: 23px;
  }

  .single-row .inline-navigation {
    gap: 12px 16px;
  }

  .single-row .header-actions {
    gap: 14px;
  }
}

@media (max-width: 1000px) {
  .single-row .header-main {
    flex-wrap: wrap;
    padding: 14px 20px;
    gap: 15px;
  }

  .single-row .mobile-toggle {
    display: block;
  }

  .single-row .business-brand {
    flex: 1;
    max-width: none;
    font-size: 21px;
  }

  .single-row .business-brand img {
    width: 45px;
    height: 45px;
  }

  .single-row .inline-navigation {
    display: none;
    order: 5;
    flex-basis: 100%;
  }

  .single-row .inline-navigation.mobile-open {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 15px 0;
    border-top: 1px solid rgba(128, 128, 128, 0.2);
  }
}

/* =====================================
   EXISTING MOBILE RESPONSIVENESS
===================================== */

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

  .single-row .search-form {
    order: 4;
    flex: 1 0 100%;
    width: 100%;
    max-width: none;
  }

  .single-row .business-brand {
    font-size: 19px;
    letter-spacing: 0.5px;
  }

  .single-row .header-action {
    font-size: 13px;
  }
}

@media (max-width: 480px) {
  .single-row .header-main {
    padding: 12px 16px;
    gap: 12px;
  }

  .single-row .business-brand img {
    width: 35px;
    height: 35px;
  }

  .single-row .header-actions {
    gap: 10px;
  }
}
</style>