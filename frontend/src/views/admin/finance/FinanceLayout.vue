<script setup>
import {
  Landmark,
  LayoutDashboard
} from 'lucide-vue-next'

import '@/assets/platform-finance.css'

const navigation = [
  {
    label: 'Overview',
    route: 'admin-finance-overview',
    icon: LayoutDashboard
  }
]
</script>

<template>
  <section class="finance-module">

    <header class="finance-module-header">

      <div class="finance-module-heading">

        <div class="finance-module-icon">
          <Landmark :size="28" />
        </div>

        <div>
          <h1>Finance</h1>

          <p>
            IVISION platform financial management
          </p>
        </div>

      </div>

      <span class="finance-mode-badge">
        Test mode
      </span>

    </header>

    <nav
      class="finance-navigation"
      aria-label="Finance navigation"
    >

      <RouterLink
        v-for="item in navigation"
        :key="item.route"
        :to="{ name: item.route }"
        active-class="finance-nav-active"
        class="finance-nav-link"
      >

        <component
          :is="item.icon"
          :size="18"
        />

        {{ item.label }}

      </RouterLink>

    </nav>

    <div class="finance-mode-notice">
      You are viewing test financial information.
      Live financial operations are disabled.
    </div>

    <RouterView />

  </section>
</template>