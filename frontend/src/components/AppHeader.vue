<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useStorefrontStore } from '@/stores/storefront'

/**
 * Made Exclusif - Application Header
 *
 * Supports:
 * - Main perfume store
 * - Independent seller storefronts
 * - Dynamic store branding
 * - Product search
 * - Mobile navigation
 * - Customer, seller and admin links
 * - Shopping cart counter
 */

const auth = useAuthStore()
const cart = useCartStore()
const sf = useStorefrontStore()

const route = useRoute()
const router = useRouter()

const open = ref(false)
const q = ref('')
const loggingOut = ref(false)

const slug = computed(() => {
  const value = route.params.store

  if (typeof value === 'string') {
    return value
  }

  return null
})

const isStorefront = computed(() => Boolean(slug.value))

const store = computed(() => {
  if (!slug.value) return null

  return sf.store?.slug === slug.value
    ? sf.store
    : null
})

const storeName = computed(() => {
  return store.value?.name || 'Made Exclusif'
})

const storeLogo = computed(() => {
  return store.value?.logo_url || null
})

const isClothingStore = computed(() => {
  return store.value?.type === 'clothing'
})

const homeTo = computed(() => {
  if (slug.value) {
    return {
      name: 'store-home',
      params: { store: slug.value },
    }
  }

  return { name: 'home' }
})

const navigation = computed(() => {
  if (isClothingStore.value) {
    return [
      {
        label: 'Shop All',
        query: {},
      },
      {
        label: 'Women',
        query: { gender: 'women' },
      },
      {
        label: 'Men',
        query: { gender: 'men' },
      },
      {
        label: 'Unisex',
        query: { gender: 'unisex' },
      },
      {
        label: 'Featured',
        query: { featured: 1 },
      },
    ]
  }

  return [
    {
      label: 'All Fragrances',
      query: {},
    },
    {
      label: 'Women',
      query: { gender: 'women' },
    },
    {
      label: 'Men',
      query: { gender: 'men' },
    },
    {
      label: 'Unisex',
      query: { gender: 'unisex' },
    },
    {
      label: 'Bestsellers',
      query: { featured: 1 },
    },
  ]
})

const isAdmin = computed(() => {
  return Boolean(auth.isAdmin)
})

const hasSellerStore = computed(() => {
  return Boolean(auth.user?.store)
})

function shopTo(query = {}) {
  if (slug.value) {
    return {
      name: 'store-shop',
      params: {
        store: slug.value,
      },
      query,
    }
  }

  return {
    name: 'shop',
    query,
  }
}

async function loadStorefront(value) {
  if (!value) {
    sf.reset()
    return
  }

  await sf.load(value)
}

watch(
  slug,
  (value) => {
    loadStorefront(value)
  },
  { immediate: true }
)

watch(
  () => route.fullPath,
  () => {
    open.value = false
  }
)

function search() {
  const query = q.value.trim()

  if (!query) return

  router.push(
    shopTo({
      q: query,
    })
  )

  q.value = ''
  open.value = false
}

function closeMenu() {
  open.value = false
}

function toggleMenu() {
  open.value = !open.value
}

async function logout() {
  if (loggingOut.value) return

  loggingOut.value = true

  try {
    await auth.logout()
    open.value = false

    await router.push({
      name: 'home',
    })
  } catch (error) {
    console.error('Logout failed:', error)
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <header class="header">
    <div class="container header__inner">
      <!-- Mobile menu button -->
      <button
        type="button"
        class="menu-toggle"
        :aria-expanded="open"
        aria-controls="main-navigation"
        :aria-label="open ? 'Close menu' : 'Open menu'"
        @click="toggleMenu"
      >
        <span aria-hidden="true">
          {{ open ? '✕' : '☰' }}
        </span>
      </button>

      <!-- Dynamic store branding -->
      <router-link
        :to="homeTo"
        class="logo brand"
        :aria-label="`${storeName} home`"
        @click="closeMenu"
      >
        <template v-if="isStorefront">
          <img
            v-if="storeLogo"
            :src="storeLogo"
            :alt="storeName"
            class="brand__img"
          />

          <span class="brand__name">
            {{ store?.name || (sf.loading ? 'Loading...' : 'Store') }}
          </span>
        </template>

        <template v-else>
          <span class="brand__name">
            Made Exclusif
          </span>
        </template>
      </router-link>

      <!-- Main navigation -->
      <nav
        id="main-navigation"
        class="nav"
        :class="{ open }"
        aria-label="Main navigation"
        @click="closeMenu"
      >
        <router-link
          v-for="item in navigation"
          :key="item.label"
          :to="shopTo(item.query)"
        >
          {{ item.label }}
        </router-link>

        <router-link
          v-if="isStorefront"
          :to="{ name: 'home' }"
          class="muted"
        >
          ← Made Exclusif
        </router-link>
      </nav>

      <!-- Header tools -->
      <div class="header__tools">
        <!-- Product search -->
        <form
          class="header__search"
          role="search"
          @submit.prevent="search"
        >
          <input
            v-model="q"
            class="input header__search-input"
            type="search"
            placeholder="Search products"
            aria-label="Search products"
          />
        </form>

        <!-- Administrator -->
        <template v-if="isAdmin">
          <router-link
            to="/admin"
            @click="closeMenu"
          >
            Admin
          </router-link>

          <router-link
            to="/admin/stores"
            @click="closeMenu"
          >
            Stores
          </router-link>
        </template>

        <!-- Seller dashboard -->
        <router-link
          v-if="hasSellerStore"
          to="/seller"
          @click="closeMenu"
        >
          My Store
        </router-link>

        <!-- Customer account -->
        <template v-if="auth.user">
          <router-link
            to="/account"
            @click="closeMenu"
          >
            Account
          </router-link>

          <button
            type="button"
            class="linkbtn"
            :disabled="loggingOut"
            @click="logout"
          >
            {{ loggingOut ? 'Logging out...' : 'Log out' }}
          </button>
        </template>

        <router-link
          v-else
          to="/login"
          @click="closeMenu"
        >
          Sign in
        </router-link>

        <!-- Shopping cart -->
        <router-link
          to="/cart"
          class="header__cart"
          :aria-label="`Shopping bag, ${cart.count} items`"
          @click="closeMenu"
        >
          Bag

          <span
            v-if="cart.count > 0"
            class="badge"
            aria-hidden="true"
          >
            {{ cart.count }}
          </span>
        </router-link>
      </div>
    </div>
  </header>
</template>

<style scoped>
.header__search {
  display: flex;
  align-items: center;
  margin: 0;
}

.header__search-input {
  width: 140px;
  min-width: 0;
  padding: 7px 10px;
  font: inherit;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  text-decoration: none;
}

.brand__img {
  display: block;
  width: 38px;
  height: 38px;
  object-fit: contain;
  flex-shrink: 0;
}

.brand__name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.header__cart {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}

.menu-toggle {
  cursor: pointer;
}

.linkbtn:disabled {
  opacity: 0.6;
  cursor: wait;
}

@media (max-width: 900px) {
  .header__search-input {
    width: 110px;
  }
}

@media (max-width: 640px) {
  .brand__img {
    width: 30px;
    height: 30px;
  }

  .brand__name {
    max-width: 150px;
  }

  .header__search-input {
    width: 95px;
    font-size: 12px;
  }
}
</style>

