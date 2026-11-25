<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AnnouncementBar from '@/components/AnnouncementBar.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import AiChatWidget from '@/components/AiChatWidget.vue'

const route = useRoute()

const showAiChat = computed(() =>
  route.name === 'home'
)
</script>

<template>
  <AnnouncementBar />

  <AppHeader />

  <main>
    <RouterView v-slot="{ Component }">
      <component
        :is="Component"
        :key="route.path"
      />
    </RouterView>
  </main>

  <AppFooter />

  <AiChatWidget v-if="showAiChat" />
</template>