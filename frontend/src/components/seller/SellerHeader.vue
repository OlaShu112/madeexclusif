<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

import {
  Menu,
  UserRound,
  Building2,
} from 'lucide-vue-next'

defineProps({
  store: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['toggle-sidebar'])

const auth = useAuthStore()

const userName = computed(() =>
  auth.user?.name || 'Business Owner'
)
</script>

<template>
  <header class="seller-header">

    <div class="header-left">

      <button
        type="button"
        class="menu-button"
        aria-label="Toggle seller sidebar"
        @click="emit('toggle-sidebar')"
      >
        <Menu :size="23" />
      </button>

      <div class="portal-brand">
        <strong>IVISION</strong>
        <small>BUSINESS PORTAL</small>
      </div>

    </div>

    <div class="header-right">

      <div v-if="store" class="business-name">
        <Building2 :size="19" />

        <span>{{ store.name }}</span>
      </div>

      <div class="user-profile">

        <img
          v-if="auth.user?.avatar_url"
          :src="auth.user.avatar_url"
          alt="Profile picture"
          class="avatar"
        />

        <div v-else class="avatar-placeholder">
          <UserRound :size="20" />
        </div>

        <div class="user-details">
          <strong>{{ userName }}</strong>
          <small>Business Owner</small>
        </div>

      </div>

    </div>

  </header>
</template>

<style scoped>
.seller-header {
  min-height: 76px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 12px 26px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  color: #171717;
}

.header-left,
.header-right,
.business-name,
.user-profile {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-right {
  gap: 24px;
}

.menu-button {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 9px;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #222;
  cursor: pointer;
}

.menu-button:hover {
  background: #f3f4f6;
}

.portal-brand strong {
  display: block;
  font-size: 21px;
  letter-spacing: 2px;
  font-weight: 700;
}

.portal-brand small {
  display: block;
  margin-top: 3px;
  font-size: 9px;
  letter-spacing: 2px;
  color: #777;
}

.business-name {
  font-size: 13px;
  font-weight: 600;
  color: #555;
}

.avatar,
.avatar-placeholder {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  flex-shrink: 0;
}

.avatar {
  object-fit: cover;
}

.avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eee;
  color: #444;
}

.user-details strong {
  display: block;
  font-size: 13px;
}

.user-details small {
  display: block;
  margin-top: 4px;
  font-size: 11px;
  color: #777;
}

@media (max-width: 768px) {
  .seller-header {
    padding: 12px 15px;
  }

  .business-name,
  .user-details {
    display: none;
  }
}
</style>