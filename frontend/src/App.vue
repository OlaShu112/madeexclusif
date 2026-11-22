<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import { useRoute } from 'vue-router'
const route = useRoute()
</script>

<template>
  <AnnouncementBar />
  <AppHeader />
  <main>
    <router-view v-slot="{ Component }">
      <component :is="Component" :key="route.path" />
    </router-view>
  </main>
  <AppFooter />
</template>
