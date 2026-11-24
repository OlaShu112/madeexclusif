
<script setup>
import {
  Menu,
  CircleUserRound,
  ExternalLink,
} from 'lucide-vue-next'

defineEmits(['toggle'])
</script>

<template>
  <header class="admin-header">
    <div class="header-left">
      <button
        class="menu-button"
        type="button"
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

      <RouterLink
        to="/account"
        class="admin-profile"
        aria-label="My account"
      >
        <div class="profile-icon">
          <CircleUserRound :size="23" />
        </div>

        <div class="profile-details">
          <strong>Administrator</strong>
          <small>My Account</small>
        </div>
      </RouterLink>
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
}

.header-left,
.header-right,
.admin-profile {
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
  margin-top: 4px;
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

.admin-profile {
  text-decoration: none;
  padding-left: 16px;
  border-left: 1px solid #e9e6df;
}

.profile-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f5efe3;
  color: #9a7942;
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
