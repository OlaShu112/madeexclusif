
<script setup>
import { ref } from 'vue'

import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'

const collapsed = ref(false)
const mobileOpen = ref(false)

function toggleSidebar() {
  if (window.matchMedia('(max-width: 900px)').matches) {
    mobileOpen.value = !mobileOpen.value
  } else {
    collapsed.value = !collapsed.value
  }
}
</script>

<template>
  <div class="admin-layout">
    <div
      v-if="mobileOpen"
      class="mobile-overlay"
      @click="mobileOpen = false"
    ></div>

    <div
      class="sidebar-container"
      :class="{ 'mobile-open': mobileOpen }"
    >
      <AdminSidebar
        :collapsed="collapsed"
        @toggle="toggleSidebar"
        @navigate="mobileOpen = false"
      />
    </div>

    <div class="admin-main">
      <AdminHeader
        @toggle="toggleSidebar"
      />

      <main class="admin-content">
        <RouterView />
      </main>

      <footer class="admin-footer">
        <span>
          © {{ new Date().getFullYear() }}
          Made Exclusif. All rights reserved.
        </span>

        <span>
          Administration Portal
        </span>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100dvh;
  width: 100%;
  background: #f7f7f5;
  color: #242424;
  font-family: Arial, Helvetica, sans-serif;
}

.sidebar-container {
  position: sticky;
  top: 0;
  height: 100dvh;
  flex-shrink: 0;
  z-index: 50;
}

.admin-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

.admin-content {
  flex: 1;
  min-width: 0;
  padding: 28px;
}

.admin-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 20px 28px;
  border-top: 1px solid #e7e4df;
  color: #777;
  font-size: 12px;
}

.mobile-overlay {
  display: none;
}

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
</style>
