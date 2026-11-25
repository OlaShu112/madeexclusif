<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function logout() {
  await auth.logout()
  await router.replace('/seller/login')
}
</script>

<template>
  <div class="onboarding-layout">

    <header class="onboarding-header">
      <div class="header-inner">

        <RouterLink
          to="/open-store"
          class="brand"
        >
          IVISION Business Centre
        </RouterLink>

        <div class="account-actions">
          <span>{{ auth.user?.name }}</span>

          <button @click="logout">
            Log Out
          </button>
        </div>

      </div>
    </header>

    <main class="onboarding-content">
      <RouterView />
    </main>

    <footer class="onboarding-footer">
      <p>
        Business Registration & Management
      </p>

      <p>
        © {{ new Date().getFullYear() }}
        IVISION Business Centre
      </p>
    </footer>

  </div>
</template>

<style scoped>
.onboarding-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f7f7f8;
  color: #202124;
}

.onboarding-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.header-inner {
  max-width: 1280px;
  margin: auto;
  padding: 20px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.brand {
  font-size: 22px;
  font-weight: 700;
  color: #202124;
  text-decoration: none;
}

.account-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

.account-actions button {
  background: transparent;
  border: 1px solid #ddd;
  padding: 10px 18px;
  border-radius: 6px;
  cursor: pointer;
}

.onboarding-content {
  flex: 1;
}

.onboarding-footer {
  text-align: center;
  background: #fff;
  padding: 24px;
  border-top: 1px solid #e5e7eb;
  color: #666;
  font-size: 13px;
}

.onboarding-footer p {
  margin: 5px 0;
}

@media (max-width: 600px) {
  .header-inner {
    padding: 16px;
  }

  .brand {
    font-size: 17px;
  }

  .account-actions span {
    display: none;
  }
}
</style>