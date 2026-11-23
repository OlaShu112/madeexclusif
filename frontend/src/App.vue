<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import AiChatWidget from '@/components/AiChatWidget.vue'

import { useRoute } from 'vue-router'
import { computed } from 'vue'

const route = useRoute()

// Display the AI assistant only on the main Made Exclusif website.
const showAiChat = computed(() => route.name === 'home')
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

  <AiChatWidget v-if="showAiChat" />
</template>

