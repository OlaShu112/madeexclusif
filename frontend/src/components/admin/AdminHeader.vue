
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import {
  Menu,
  CircleUserRound,
  ExternalLink,
  ChevronDown,
  UserRound,
  LockKeyhole,
  LogOut,
} from 'lucide-vue-next'

defineEmits(['toggle'])

const auth = useAuthStore()
const router = useRouter()

const dropdownOpen = ref(false)
const profileMenu = ref(null)
const loggingOut = ref(false)

function closeDropdown() {
  dropdownOpen.value = false
}

function handleOutsideClick(event) {
  if (
    profileMenu.value &&
    !profileMenu.value.contains(event.target)
  ) {
    closeDropdown()
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    closeDropdown()
  }
}

async function handleLogout() {
  if (loggingOut.value) return

  loggingOut.value = true

  try {
    await auth.logout()
    closeDropdown()
    await router.replace('/admin/login')
  } finally {
    loggingOut.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <header class="admin-header">

    <div class="header-left">

      <button
        type="button"
        class="menu-button"
        aria-label="Toggle navigation"
        @click="$emit('toggle')"
      >
        <Menu :size="23" />
      </button>

      <div class="header-title">
        <strong>Administration Portal</strong>
        <small>Made Exclusif Marketplace</small>
      </div>

    </div>

    <div class="header-right">

      <RouterLink
        to="/"
        class="storefront-link"
      >
        <ExternalLink :size="17" />
        <span>View Storefront</span>
      </RouterLink>

      <div
        ref="profileMenu"
        class="profile-container"
      >

        <button
          type="button"
          class="admin-profile"
          :aria-expanded="dropdownOpen"
          aria-label="Administrator account menu"
          @click="dropdownOpen = !dropdownOpen"
        >

          <div class="profile-icon">

            <img
              v-if="auth.user?.avatar_url"
              :src="auth.user.avatar_url"
              alt="Profile photograph"
            />

            <CircleUserRound
              v-else
              :size="24"
            />

          </div>

          <div class="profile-details">

            <strong>
              {{ auth.user?.name || 'Administrator' }}
            </strong>

            <small>Administrator</small>

          </div>

          <ChevronDown
            :size="16"
            class="chevron"
            :class="{ rotated: dropdownOpen }"
          />

        </button>

        <Transition name="dropdown">

          <div
            v-if="dropdownOpen"
            class="profile-dropdown"
          >

            <div class="dropdown-user">

              <strong>
                {{ auth.user?.name || 'Administrator' }}
              </strong>

              <small>
                {{ auth.user?.email }}
              </small>

              <span class="role-label">
                Administrator
              </span>

            </div>

            <div class="dropdown-divider"></div>

            <RouterLink
              to="/admin/profile"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <UserRound :size="18" />
              My Profile
            </RouterLink>

            <RouterLink
              to="/admin/profile"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <LockKeyhole :size="18" />
              Account & Security
            </RouterLink>

            <RouterLink
              to="/"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <ExternalLink :size="18" />
              View Storefront
            </RouterLink>

            <div class="dropdown-divider"></div>

            <button
              type="button"
              class="dropdown-item logout-item"
              :disabled="loggingOut"
              @click="handleLogout"
            >
              <LogOut :size="18" />

              {{
                loggingOut
                  ? 'Logging out...'
                  : 'Log Out'
              }}

            </button>

          </div>

        </Transition>

      </div>

    </div>

  </header>
</template>

<style scoped>
.admin-header {
  min-height: 76px;
  background: #fff;
  border-bottom: 1px solid #e9e6df;
  padding: 14px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  position: relative;
  z-index: 30;
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.menu-button {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  background: #fff;
  border: 1px solid #e7e4df;
  border-radius: 8px;
  cursor: pointer;
  color: #252525;
}

.header-title strong,
.profile-details strong {
  display: block;
  font-size: 14px;
  color: #222;
}

.header-title small,
.profile-details small {
  display: block;
  margin-top: 5px;
  font-size: 11px;
  color: #888;
}

.storefront-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #76603b;
  text-decoration: none;
  font-size: 13px;
}

.profile-container {
  position: relative;
}

.admin-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  background: transparent;
  border: none;
  border-left: 1px solid #e9e6df;
  padding: 5px 0 5px 16px;
  cursor: pointer;
  text-align: left;
}

.profile-icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f5efe3;
  color: #9a7942;
  overflow: hidden;
  flex-shrink: 0;
}

.profile-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.chevron {
  color: #888;
  transition: transform 0.2s ease;
}

.chevron.rotated {
  transform: rotate(180deg);
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 14px);
  right: 0;
  width: 280px;
  max-width: calc(100vw - 30px);
  padding: 8px;
  background: #fff;
  border: 1px solid #e9e6df;
  border-radius: 12px;
  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
  z-index: 100;
}

.dropdown-user {
  padding: 15px 12px;
}

.dropdown-user strong {
  display: block;
  font-size: 14px;
  color: #222;
}

.dropdown-user small {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #777;
  overflow-wrap: anywhere;
}

.role-label {
  display: inline-block;
  margin-top: 10px;
  padding: 5px 9px;
  border-radius: 5px;
  background: #f5efe3;
  color: #8c6c37;
  font-size: 10px;
  font-weight: 600;
}

.dropdown-divider {
  height: 1px;
  background: #eeeae4;
  margin: 6px 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 11px;
  box-sizing: border-box;
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 7px;
  background: transparent;
  color: #333;
  font-size: 13px;
  text-decoration: none;
  text-align: left;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f6f4ef;
}

.logout-item {
  color: #c03939;
}

.logout-item:hover {
  background: #fff0f0;
}

.logout-item:disabled {
  opacity: 0.5;
  cursor: wait;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease,
              transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

@media (max-width: 650px) {
  .admin-header {
    padding: 12px 15px;
  }

  .storefront-link span,
  .profile-details,
  .header-title small {
    display: none;
  }

  .header-left,
  .header-right {
    gap: 10px;
  }
}
</style>
