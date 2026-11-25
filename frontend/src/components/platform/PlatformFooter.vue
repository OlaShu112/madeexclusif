
<script setup>
const year = new Date().getFullYear()
</script>

<template>
  <footer class="iv-footer">
    <div class="iv-footer-inner">

      <div class="iv-footer-grid">

        <div class="iv-company">
          <RouterLink to="/" class="iv-brand">
            IVISION
          </RouterLink>

          <p>
            Intelligent commerce technology for
            ambitious businesses. Build, manage
            and grow your business on one platform.
          </p>
        </div>

        <div>
          <h3>Platform</h3>
          <RouterLink to="/#features">Features</RouterLink>
          <RouterLink to="/#solutions">Solutions</RouterLink>
          <RouterLink to="/#pricing">Pricing</RouterLink>
        </div>

        <div>
          <h3>Business</h3>
          <RouterLink to="/business/register">
            Start Your Business
          </RouterLink>
          <RouterLink to="/seller/login">
            Business Sign In
          </RouterLink>
          <RouterLink to="/open-store">
            Business Application
          </RouterLink>
        </div>

        <div>
          <h3>Company</h3>
          <RouterLink to="/#about">
            About IVISION
          </RouterLink>
          <RouterLink to="/#faq">
            Frequently Asked Questions
          </RouterLink>
        </div>

      </div>

      <div class="iv-footer-bottom">
        <span>
          © {{ year }} IVISION.
          All rights reserved.
        </span>

        <span>
          Intelligent Commerce Technology
        </span>
      </div>

    </div>
  </footer>
</template>

<style scoped>
.iv-footer {
  background: #101828;
  color: #fff;
  padding: 65px 30px 25px;
}

.iv-footer-inner {
  max-width: 1280px;
  margin: auto;
}

.iv-footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 45px;
}

.iv-brand {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: 2px;
  color: #fff;
  text-decoration: none;
}

.iv-company p {
  max-width: 330px;
  color: #b4bdcd;
  font-size: 14px;
  line-height: 1.8;
}

.iv-footer h3 {
  font-size: 15px;
  margin: 0 0 20px;
  color: #fff;
}

.iv-footer-grid > div > a:not(.iv-brand) {
  display: block;
  color: #b4bdcd;
  text-decoration: none;
  font-size: 14px;
  margin-bottom: 14px;
}

.iv-footer a:hover {
  color: #fff;
}

.iv-footer-bottom {
  border-top: 1px solid #344054;
  margin-top: 50px;
  padding-top: 25px;
  display: flex;
  justify-content: space-between;
  gap: 15px;
  color: #b4bdcd;
  font-size: 13px;
}

@media (max-width: 800px) {
  .iv-footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .iv-footer-grid {
    grid-template-columns: 1fr;
  }

  .iv-footer-bottom {
    flex-direction: column;
  }
}
</style>
