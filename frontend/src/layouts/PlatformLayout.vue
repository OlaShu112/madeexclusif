<script setup>
import { onMounted } from 'vue'

import PlatformHeader from '@/components/platform/PlatformHeader.vue'
import PlatformFooter from '@/components/platform/PlatformFooter.vue'

import { usePlatformStore } from '@/stores/platform'

const platform = usePlatformStore()

/*
|--------------------------------------------------------------------------
| Load Platform Branding
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  try {
    await platform.loadBranding()
  } catch (error) {
    console.error(
      'Unable to load platform branding:',
      error
    )
  }
})
</script>

<template>
  <div class="iv-platform">

    <PlatformHeader />

    <main class="iv-platform-content">
      <RouterView />
    </main>

    <PlatformFooter />

  </div>
</template>

<style scoped>
.iv-platform {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #fff;
  color: #142033;

  font-family: var(
    --ivision-font-sans,
    'Geist Variable',
    system-ui,
    sans-serif
  );

  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.iv-platform-content {
  flex: 1;
  min-width: 0;
  width: 100%;
}
</style>