
<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Menu, X, ArrowUpRight } from 'lucide-vue-next'

const route = useRoute()
const mobileOpen = ref(false)

watch(() => route.fullPath, () => {
  mobileOpen.value = false
})
</script>

<template>
  <header class="iv-header">
    <div class="iv-header-inner">

      <RouterLink to="/" class="iv-logo">
        <span class="iv-symbol">I</span>
        IVISION
      </RouterLink>

      <button
        class="iv-menu-toggle"
        type="button"
        :aria-expanded="mobileOpen"
        aria-controls="iv-navigation"
        aria-label="Toggle navigation"
        @click="mobileOpen = !mobileOpen"
      >
        <X v-if="mobileOpen" />
        <Menu v-else />
      </button>

      <nav
        id="iv-navigation"
        class="iv-navigation"
        :class="{ 'is-open': mobileOpen }"
        aria-label="Main navigation"
      >
        <RouterLink to="/#features">
          Features
        </RouterLink>

        <RouterLink to="/#solutions">
          Solutions
        </RouterLink>

        <RouterLink to="/#pricing">
          Pricing
        </RouterLink>

        <RouterLink to="/#about">
          About Us
        </RouterLink>

        <div class="iv-actions">

          <RouterLink to="/seller/login">
            Sign In
          </RouterLink>

          <RouterLink
            to="/business/register"
            class="iv-button"
          >
            Get Started
            <ArrowUpRight :size="17" />
          </RouterLink>

        </div>
      </nav>

    </div>
  </header>
</template>

<style scoped>
.iv-header {
  background: #fff;
  border-bottom: 1px solid #e7eaf0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.iv-header-inner {
  max-width: 1440px;
  margin: auto;
  padding: 18px 40px;
  display: flex;
  align-items: center;
  gap: 60px;
}

.iv-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #142033;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: 1px;
  text-decoration: none;
}

.iv-symbol {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: #142033;
  color: #fff;
}

.iv-navigation {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 30px;
}

.iv-navigation a {
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  color: #344054;
}

.iv-navigation a:hover {
  color: #315fc5;
}

.iv-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 25px;
}

.iv-navigation .iv-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #142033;
  color: #fff;
  padding: 13px 21px;
  border-radius: 7px;
}

.iv-menu-toggle {
  display: none;
  border: 0;
  background: transparent;
  cursor: pointer;
}

@media (max-width: 1000px) {
  .iv-header-inner {
    padding: 16px 22px;
    justify-content: space-between;
  }

  .iv-menu-toggle {
    display: block;
  }

  .iv-navigation {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    padding: 25px;
    background: white;
    border-bottom: 1px solid #ddd;
    align-items: stretch;
    flex-direction: column;
  }

  .iv-navigation.is-open {
    display: flex;
  }

  .iv-actions {
    margin-left: 0;
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
