<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { ShieldCheck, Store, LockKeyhole } from 'lucide-vue-next'

const route = useRoute()

const isAdmin = computed(() =>
  route.path.startsWith('/admin/')
)

const portalName = computed(() =>
  isAdmin.value
    ? 'Platform Administration'
    : 'Business Portal'
)
</script>

<template>
  <div class="portal-auth">

    <header class="portal-header">
      <div class="portal-brand">

        <component
          :is="isAdmin ? ShieldCheck : Store"
          :size="30"
        />

        <RouterLink to="/" class="ivision-brand">IVISION</RouterLink><span>{{ portalName }}</span>

      </div>

      <div class="secure-label">
        <LockKeyhole :size="15" />
        Secure Portal
      </div>
    </header>

    <main class="portal-content">
      <RouterView />
    </main>

    <footer class="portal-footer">

      <p>
        &copy; {{ new Date().getFullYear() }}
        IVISION. All rights reserved.
      </p>

      <p>
        Secure Authentication & Account Management
      </p>

    </footer>

  </div>
</template>

<style scoped>
.portal-auth {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #f5f6f8;
  color: #171717;
  font-family: Arial, sans-serif;
}

.portal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 40px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.portal-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 20px;
  font-weight: 700;
}

.secure-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: #666;
}

.portal-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 50px 20px;
}

.portal-footer {
  padding: 24px;
  text-align: center;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  font-size: 13px;
  color: #777;
}

.portal-footer p {
  margin: 6px 0;
}

@media (max-width: 600px) {
  .portal-header {
    padding: 18px;
  }

  .portal-brand {
    font-size: 16px;
  }

  .secure-label {
    display: none;
  }

  .portal-content {
    padding: 30px 16px;
  }
}

.ivision-brand {
  color: #142033;
  font-weight: 800;
  letter-spacing: 2px;
  text-decoration: none;
}

.portal-brand {
  flex-wrap: wrap;
}

.portal-brand span {
  font-size: 13px;
  font-weight: 500;
  color: #667085;
}
</style>