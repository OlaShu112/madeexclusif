<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue'

const route = useRoute()

const independentLayout = computed(() =>
  [
    'platform',
    'admin',
    'onboarding',
    'seller',
    'storefront',
    'portal-auth'
  ].includes(route.meta.layout)
)
</script>

<template>
  <RouterView v-if="independentLayout" />
  <MarketplaceLayout v-else />
</template>