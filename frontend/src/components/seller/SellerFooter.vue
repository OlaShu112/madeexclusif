<script setup>
const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="seller-footer">

    <div>
      <strong>IVISION</strong>

      <span>
        Business Management Platform
      </span>
    </div>

    <p>
      &copy; {{ currentYear }} IVISION.
      All rights reserved.
    </p>

  </footer>
</template>

<style scoped>
.seller-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 20px 28px;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  color: #777;
  font-size: 12px;
}

.seller-footer strong {
  color: #171717;
  font-size: 14px;
  letter-spacing: 1.5px;
  margin-right: 12px;
}

.seller-footer p {
  margin: 0;
}

@media (max-width: 768px) {
  .seller-footer {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
}
</style>