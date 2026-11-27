<script setup>
import {
  ref,
  computed,
  onMounted,
  watch,
  onBeforeUnmount
} from 'vue'

import { usePlatformStore } from '@/stores/platform'

import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'

/**
 * IVISION Platform Branding
 */

const platform = usePlatformStore()

/**
 * Administration layout state
 */

const collapsed = ref(
  localStorage.getItem('ivision-admin-sidebar-collapsed') === 'true'
)

const mobileOpen = ref(false)
const isMobile = ref(false)

const platformName = computed(() =>
  platform.branding.platform_name || 'IVISION'
)

const administrationTitle = computed(() =>
  platform.branding.administration_title ||
  'Platform Administration'
)

const copyrightText = computed(() =>
  platform.branding.copyright_text ||
  'All rights reserved.'
)

const currentYear = new Date().getFullYear()

let mediaQuery = null
let previousTitle = ''
let faviconElement = null
let previousFavicon = null
let createdFavicon = false

/**
 * Synchronise responsive layout
 */

function updateScreenSize() {
  isMobile.value = mediaQuery?.matches || false

  if (!isMobile.value) {
    mobileOpen.value = false
  }
}

/**
 * Load saved platform branding
 */

onMounted(async () => {
  previousTitle = document.title

  faviconElement = document.querySelector(
    'link[rel="icon"]'
  )

  if (faviconElement) {
    previousFavicon =
      faviconElement.getAttribute('href')
  }

  mediaQuery = window.matchMedia(
    '(max-width: 900px)'
  )

  updateScreenSize()

  mediaQuery.addEventListener(
    'change',
    updateScreenSize
  )

  try {
    await platform.loadBranding()
  } catch (error) {
    console.error(
      'Unable to load platform branding:',
      error
    )
  }
})

/**
 * Synchronise the browser title with platform branding
 */

watch(
  () => platform.branding.browser_title,
  title => {
    if (title) {
      document.title = title
    }
  },
  { immediate: false }
)

onMounted(() => {
  document.title =
    platform.branding.browser_title ||
    'IVISION — Administration Portal'
})

/**
 * Synchronise the platform favicon
 */

watch(
  () => platform.branding.favicon,
  favicon => {
    if (!favicon) {
      if (createdFavicon && faviconElement) {
        faviconElement.remove()
        faviconElement = null
        createdFavicon = false
      } else if (faviconElement) {
        if (previousFavicon !== null) {
          faviconElement.setAttribute(
            'href',
            previousFavicon
          )
        } else {
          faviconElement.removeAttribute('href')
        }
      }

      return
    }

    if (!faviconElement) {
      faviconElement =
        document.createElement('link')

      faviconElement.rel = 'icon'

      document.head.appendChild(
        faviconElement
      )

      createdFavicon = true
    }

    faviconElement.href = favicon
  },
  { flush: 'post' }
)

/**
 * Persist desktop sidebar preference
 */

watch(collapsed, value => {
  localStorage.setItem(
    'ivision-admin-sidebar-collapsed',
    String(value)
  )
})

/**
 * Toggle desktop or mobile sidebar
 */

function toggleSidebar() {
  if (isMobile.value) {
    mobileOpen.value = !mobileOpen.value
  } else {
    collapsed.value = !collapsed.value
  }
}

/**
 * Clean up administration layout
 */

onBeforeUnmount(() => {
  mediaQuery?.removeEventListener(
    'change',
    updateScreenSize
  )

  document.title = previousTitle

  if (createdFavicon && faviconElement) {
    faviconElement.remove()
  } else if (faviconElement) {
    if (previousFavicon !== null) {
      faviconElement.setAttribute(
        'href',
        previousFavicon
      )
    } else {
      faviconElement.removeAttribute('href')
    }
  }
})
</script>

<template>
  <div
    class="admin-layout"
    :class="{
      'dark-appearance':
        platform.branding.appearance === 'dark'
    }"
    :style="platform.themeVariables"
  >
    <!-- Mobile sidebar overlay -->

    <div
      v-if="mobileOpen"
      class="mobile-overlay"
      @click="mobileOpen = false"
    ></div>

    <!-- Administration sidebar -->

    <div
      class="sidebar-container"
      :class="{ 'mobile-open': mobileOpen }"
    >
      <AdminSidebar
        :collapsed="isMobile ? false : collapsed"
        @toggle="toggleSidebar"
        @navigate="mobileOpen = false"
      />
    </div>

    <!-- Administration main content -->

    <div class="admin-main">

      <!-- Administration header -->

      <AdminHeader
        @toggle="toggleSidebar"
      />

      <!-- Administration page content -->

      <main class="admin-content">
        <RouterView />
      </main>

      <!-- Dynamic IVISION administration footer -->

      <footer class="admin-footer">
        <span>
          © {{ currentYear }}
          {{ platformName }}.
          {{ copyrightText }}
        </span>

        <span>
          {{ administrationTitle }}
        </span>
      </footer>

    </div>
  </div>
</template>

<style scoped>

/* Administration layout */

.admin-layout {
  display: flex;
  min-height: 100dvh;
  width: 100%;

  background:
    var(--platform-background, #F5F6F9);

  color:
    var(--platform-text, #111827);

  font-family:
    var(--platform-font, system-ui, sans-serif);
}

/* Administration sidebar container */

.sidebar-container {
  position: sticky;
  top: 0;
  height: 100dvh;
  flex-shrink: 0;
  z-index: 50;
}

/* Administration main area */

.admin-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

/* Administration page content */

.admin-content {
  flex: 1;
  min-width: 0;
  padding: 28px;
}

/* Administration footer */

.admin-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  padding: 20px 28px;

  background:
    var(--platform-footer, #FFFFFF);

  border-top:
    1px solid rgba(128, 128, 128, 0.18);

  color:
    var(--platform-text, #111827);

  font-size: 12px;
}

.admin-footer span {
  opacity: 0.75;
}

/* Mobile overlay */

.mobile-overlay {
  display: none;
}

/* Dark appearance foundation */

.dark-appearance {
  color-scheme: dark;
}

/* Responsive administration layout */

@media (max-width: 900px) {
  .sidebar-container {
    position: fixed;
    left: 0;
    top: 0;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
  }

  .sidebar-container.mobile-open {
    transform: translateX(0);
  }

  .mobile-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 40;
  }

  .admin-content {
    padding: 16px;
  }

  .admin-footer {
    padding: 16px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .sidebar-container {
    transition: none;
  }
}

</style>