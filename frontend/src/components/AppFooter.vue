<template>
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <div class="logo" style="color:#fff">MadeExlusif</div>
          <p style="margin-top:12px;max-width:300px">Exclusive fragrances, composed in small batches and made to be remembered.</p>
        </div>
        <div>
          <h4>Shop</h4>
          <router-link to="/shop">All Fragrances</router-link>
          <router-link :to="{ name: 'shop', query: { gender: 'women' } }">Women</router-link>
          <router-link :to="{ name: 'shop', query: { gender: 'men' } }">Men</router-link>
        </div>
        <div>
          <h4>Help</h4>
          <router-link to="/account">Track an order</router-link>
          <a href="mailto:hello@madeexlusif.test">Contact us</a>
        </div>
        <div>
          <h4>Delivery</h4>
          <p>Free UK delivery over £100, otherwise £5.95. Orders ship within 2 working days.</p>
        </div>
      </div>
      <div class="footer__legal">
        <span>© {{ new Date().getFullYear() }} MadeExlusif. All rights reserved.</span>
        <span>Privacy · Terms · Returns</span>
      </div>
    </div>
  </footer>
</template>
