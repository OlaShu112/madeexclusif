<script setup>
import {
  ref,
  onMounted,
  onUnmounted,
} from 'vue'

import { api } from '@/api/http'

import SellerHeader from '@/components/seller/SellerHeader.vue'
import SellerSidebar from '@/components/seller/SellerSidebar.vue'
import SellerFooter from '@/components/seller/SellerFooter.vue'

const store = ref(null)
const storeError = ref('')

const isMobile = ref(
  window.innerWidth <= 900
)

const sidebarOpen = ref(
  !isMobile.value
)

/**
 * Retrieve the authenticated seller's business.
 */

async function loadStore() {
  storeError.value = ''

  try {
    const response = await api('/my-store')

    store.value = response?.store ?? null
  } catch (error) {
    store.value = null

    storeError.value =
      error?.message ||
      'Unable to retrieve business information.'
  }
}

/**
 * Toggle seller navigation.
 */

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

/**
 * Close mobile navigation after selecting a link.
 */

function handleNavigation() {
  if (isMobile.value) {
    sidebarOpen.value = false
  }
}

/**
 * Responsive sidebar behaviour.
 */

function handleResize() {
  const mobile = window.innerWidth <= 900

  if (mobile !== isMobile.value) {
    isMobile.value = mobile
    sidebarOpen.value = !mobile
  }
}

onMounted(() => {
  loadStore()

  window.addEventListener(
    'resize',
    handleResize
  )
})

onUnmounted(() => {
  window.removeEventListener(
    'resize',
    handleResize
  )
})
</script>

<template>
  <div class="seller-layout">

    <!-- Mobile sidebar overlay -->

    <div
      v-if="isMobile && sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    />

    <!-- Independent seller sidebar -->

    <div
      v-if="sidebarOpen"
      class="sidebar-wrapper"
    >
      <SellerSidebar
        :store="store"
        @navigate="handleNavigation"
      />
    </div>

    <!-- Seller workspace -->

    <div class="seller-workspace">

      <!-- Independent seller header -->

      <SellerHeader
        :store="store"
        @toggle-sidebar="toggleSidebar"
      />

      <!-- Business information errors -->

      <div
        v-if="storeError"
        class="store-error"
        role="alert"
      >
        <span>{{ storeError }}</span>

        <button
          type="button"
          @click="loadStore"
        >
          Try Again
        </button>
      </div>

      <!-- Seller page content -->

      <div class="seller-content">

        <RouterView />

      </div>

      <!-- Independent seller footer -->

      <SellerFooter />

    </div>

  </div>
</template>

<style scoped>
.seller-layout {
  display: flex;
  min-height: 100dvh;
  width: 100%;
  background: #f6f7f9;
  color: #171717;
  font-family: Arial, Helvetica, sans-serif;
}

.sidebar-wrapper {
  position: sticky;
  top: 0;
  height: 100dvh;
  flex-shrink: 0;
  z-index: 50;
}

.seller-workspace {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  min-height: 100dvh;
}

.seller-content {
  flex: 1;
  min-width: 0;
  padding: 24px;
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 90;
}

.store-error {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 15px 24px;
  background: #fff1f1;
  color: #b42318;
  font-size: 13px;
}

.store-error button {
  border: 1px solid #b42318;
  border-radius: 5px;
  background: transparent;
  color: #b42318;
  padding: 7px 12px;
  cursor: pointer;
}

@media (max-width: 900px) {
  .sidebar-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
  }

  .seller-content {
    padding: 15px;
  }
}
</style>