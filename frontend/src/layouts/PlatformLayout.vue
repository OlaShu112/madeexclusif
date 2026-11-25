<script setup>
import PlatformHeader from '@/components/platform/PlatformHeader.vue'
import PlatformFooter from '@/components/platform/PlatformFooter.vue'
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
  font-family: Inter, Arial, sans-serif;
}

.iv-platform-content {
  flex: 1;
}
</style>