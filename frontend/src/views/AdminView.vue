<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { money, dateShort } from '@/utils/format'
import ProductImage from '@/components/ProductImage.vue'
import { usePlatformStore } from '@/stores/platform'

const route = useRoute()
const platform = usePlatformStore()

const tab = computed(() =>
  route.query.tab === 'orders' ? 'orders' : 'products'
)
const error = ref('')
const notice = ref('')

async function guard(fn) {
  error.value = ''
  try { await fn() } catch (e) { error.value = e.message }
}

// ---------------- Products ----------------
const products = ref([])
const lists = ref({ brands: [], categories: [] })
const form = ref(null)          // null = list view, object = add/edit form
const imageFile = ref(null)
const imagePreview = ref(null)
const newBrand = ref('')
const newCategory = ref('')
const saving = ref(false)

const blank = () => ({
  id: null, name: '', brand_id: '', category_id: '', gender: 'unisex', concentration: 'Eau de Parfum',
  description: '', notes_top: '', notes_heart: '', notes_base: '', accent: '#c9a86a',
  is_featured: false, is_active: true, remove_image: false,
  price_50: '', stock_50: 20, price_100: '', stock_100: 20, image_url: null,
})

async function loadLists() {
  lists.value = await api('/filters')
}
async function loadProducts() {
  products.value = (await api('/admin/products')).data
}

function openForm(p) {
  imageFile.value = null
  newBrand.value = newCategory.value = ''
  if (!p) {
    form.value = blank()
    imagePreview.value = null
    return
  }
  const v = (ml) => p.variants.find((x) => x.size_ml === ml) || {}
  form.value = {
    ...blank(), ...p,
    description: p.description || '', notes_top: p.notes_top || '', notes_heart: p.notes_heart || '', notes_base: p.notes_base || '',
    price_50: v(50).price ?? '', stock_50: v(50).stock ?? 0,
    price_100: v(100).price ?? '', stock_100: v(100).stock ?? 0,
    remove_image: false,
  }
  imagePreview.value = p.image_url
}

function pickImage(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 4 * 1024 * 1024) { error.value = 'Image must be 4MB or smaller.'; e.target.value = ''; return }
  imageFile.value = file
  form.value.remove_image = false
  imagePreview.value = URL.createObjectURL(file)
}
function dropImage() {
  imageFile.value = null
  imagePreview.value = null
  form.value.remove_image = true
}

async function addBrand() {
  if (!newBrand.value.trim()) return
  await guard(async () => {
    const b = await api('/admin/brands', { method: 'POST', body: { name: newBrand.value.trim() } })
    lists.value.brands.push(b)
    form.value.brand_id = b.id
    newBrand.value = ''
  })
}
async function addCategory() {
  if (!newCategory.value.trim()) return
  await guard(async () => {
    const c = await api('/admin/categories', { method: 'POST', body: { name: newCategory.value.trim() } })
    lists.value.categories.push(c)
    form.value.category_id = c.id
    newCategory.value = ''
  })
}

async function saveProduct() {
  error.value = notice.value = ''
  saving.value = true
  const f = form.value
  const fd = new FormData()
  ;['name', 'brand_id', 'category_id', 'gender', 'concentration', 'description', 'notes_top', 'notes_heart', 'notes_base',
    'accent', 'price_50', 'stock_50', 'price_100', 'stock_100'].forEach((k) => fd.append(k, f[k] ?? ''))
  fd.append('is_featured', f.is_featured ? '1' : '0')
  fd.append('is_active', f.is_active ? '1' : '0')
  if (imageFile.value) fd.append('image', imageFile.value)
  if (f.remove_image) fd.append('remove_image', '1')

  try {
    if (f.id) {
      fd.append('_method', 'PUT') // multipart can't use PUT directly; Laravel reads this field
      await api(`/admin/products/${f.id}`, { method: 'POST', body: fd })
    } else {
      await api('/admin/products', { method: 'POST', body: fd })
    }
    notice.value = f.id ? 'Perfume updated.' : 'Perfume added to the shop.'
    form.value = null
    await loadProducts()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function setActive(p, active) {
  await guard(async () => {
    const v = (ml) => p.variants.find((x) => x.size_ml === ml) || {}
    const fd = new FormData()
    // Send every field back unchanged so hiding/showing never wipes the description or notes.
    ;[['name', p.name], ['brand_id', p.brand_id], ['category_id', p.category_id], ['gender', p.gender],
      ['concentration', p.concentration], ['description', p.description ?? ''], ['notes_top', p.notes_top ?? ''],
      ['notes_heart', p.notes_heart ?? ''], ['notes_base', p.notes_base ?? ''], ['accent', p.accent || '#c9a86a'],
      ['price_50', v(50).price ?? 0], ['stock_50', v(50).stock ?? 0],
      ['price_100', v(100).price ?? 0], ['stock_100', v(100).stock ?? 0]].forEach(([k, val]) => fd.append(k, val))
    fd.append('is_featured', p.is_featured ? '1' : '0')
    fd.append('is_active', active ? '1' : '0')
    fd.append('_method', 'PUT')
    await api(`/admin/products/${p.id}`, { method: 'POST', body: fd })
    await loadProducts()
  })
}

// ---------------- Orders ----------------
const orders = ref([])
const orderStatus = ref('')
const statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']

async function loadOrders() {
  orders.value = (await api('/admin/orders', { params: { status: orderStatus.value } })).data
}
function allowedStatuses(order) {
  const transitions = {
    paid: ['processing'],
    processing: ['shipped'],
    shipped: ['delivered'],
  }

  if (order.payment_status !== 'paid') {
    return [order.status]
  }

  return [
    order.status,
    ...(transitions[order.status] || []),
  ]
}

async function setStatus(o, status) {
  if (
    status === o.status ||
    !allowedStatuses(o).includes(status)
  ) return

  error.value = ''
  notice.value = ''

  const tracking = status === 'shipped' ? prompt('Tracking number (optional):', o.tracking_number || '') : null
  try {
    await api(`/admin/orders/${o.id}`, { method: 'PATCH', body: { status, tracking_number: tracking || undefined } })
    notice.value = `Order ${o.number} marked ${status}.`
  } catch (e) { error.value = e.message }
  await loadOrders()
}

watch(tab, (t) => {
  notice.value = ''
  form.value = null

  if (t === 'products') {
    guard(async () => {
      await Promise.all([loadProducts(), loadLists()])
    })
  }

  if (t === 'orders') {
    guard(loadOrders)
  }
}, { immediate: true })

watch(orderStatus, () => {
  if (tab.value === 'orders') {
    guard(loadOrders)
  }
})
</script>

<template>
  <div class="admin-management">
    <div class="management-heading">
      <p class="eyebrow">{{ platform.branding.platform_name || 'IVISION' }} Administration</p>
      <h1>{{ tab === 'orders' ? 'Order Management' : 'Product Management' }}</h1>
      <p class="muted">
        {{ tab === 'orders'
          ? 'Manage customer orders and fulfilment.'
          : 'Manage your product catalogue, pricing and inventory.' }}
      </p>
    </div>
    
    <div v-if="error" class="alert">{{ error }}</div>
    <div v-if="notice" class="alert alert--ok">{{ notice }}</div>

    <!-- Products: list -->
    <template v-if="tab === 'products' && !form">
      <p><button class="btn btn--sm" @click="openForm(null)">+ Add perfume</button></p>
      <table class="table">
        <thead><tr><th /><th>Perfume</th><th>50ml</th><th>100ml</th><th>Status</th><th /></tr></thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td style="width:56px"><div class="thumb-box"><ProductImage :src="p.image_url" :accent="p.accent" :label="p.name" /></div></td>
            <td>{{ p.name }}<div class="muted">{{ p.brand?.name }} · {{ p.category?.name }}</div></td>
            <td v-for="ml in [50, 100]" :key="ml">
              <template v-for="v in p.variants.filter((x) => x.size_ml === ml)" :key="v.id">{{ money(v.price) }}<div class="muted">{{ v.stock }} in stock</div></template>
            </td>
            <td><span class="pill">{{ p.is_active ? 'live' : 'hidden' }}</span></td>
            <td style="text-align:right;white-space:nowrap">
              <button class="linkbtn" @click="openForm(p)">Edit</button> ·
              <button class="linkbtn" @click="setActive(p, !p.is_active)">{{ p.is_active ? 'Hide' : 'Show' }}</button>
            </td>
          </tr>
          <tr v-if="!products.length"><td colspan="6" class="muted">No perfumes yet.</td></tr>
        </tbody>
      </table>
    </template>

    <!-- Products: add / edit form -->
    <form v-if="tab === 'products' && form" style="max-width:760px" @submit.prevent="saveProduct">
      <h3>{{ form.id ? `Edit ${form.name}` : 'Add a new perfume' }}</h3>

      <div class="field"><label>Perfume name</label><input v-model="form.name" class="input" required maxlength="160" /></div>

      <div class="row">
        <div class="field">
          <label>Brand</label>
          <select v-model="form.brand_id" class="input" required><option value="" disabled>Select…</option><option v-for="b in lists.brands" :key="b.id" :value="b.id">{{ b.name }}</option></select>
          <div class="inline-add"><input v-model="newBrand" class="input" placeholder="…or add a new brand" @keydown.enter.prevent="addBrand" /><button type="button" class="btn btn--ghost btn--sm" @click="addBrand">Add</button></div>
        </div>
        <div class="field">
          <label>Category</label>
          <select v-model="form.category_id" class="input" required><option value="" disabled>Select…</option><option v-for="c in lists.categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <div class="inline-add"><input v-model="newCategory" class="input" placeholder="…or add a new category" @keydown.enter.prevent="addCategory" /><button type="button" class="btn btn--ghost btn--sm" @click="addCategory">Add</button></div>
        </div>
      </div>

      <div class="row">
        <div class="field"><label>For</label><select v-model="form.gender" class="input"><option value="women">Women</option><option value="men">Men</option><option value="unisex">Unisex</option></select></div>
        <div class="field"><label>Concentration</label>
          <select v-model="form.concentration" class="input"><option>Eau de Toilette</option><option>Eau de Parfum</option><option>Parfum</option></select>
        </div>
      </div>

      <div class="field"><label>Description</label><textarea v-model="form.description" class="input" rows="3" maxlength="5000" /></div>
      <div class="row">
        <div class="field"><label>Top notes</label><input v-model="form.notes_top" class="input" placeholder="e.g. Bergamot, Pink Pepper" /></div>
        <div class="field"><label>Heart notes</label><input v-model="form.notes_heart" class="input" /></div>
      </div>
      <div class="field"><label>Base notes</label><input v-model="form.notes_base" class="input" /></div>

      <div class="field">
        <label>Photo (JPG, PNG or WebP, max 4MB)</label>
        <div class="dropzone">
          <img v-if="imagePreview" :src="imagePreview" alt="Preview" />
          <div>
            <input type="file" accept="image/jpeg,image/png,image/webp" @change="pickImage" />
            <div v-if="imagePreview" style="margin-top:8px"><button type="button" class="linkbtn muted" @click="dropImage">Remove photo</button></div>
            <p v-else class="muted" style="margin:8px 0 0">No photo - a bottle illustration in the colour below will be shown instead.</p>
          </div>
        </div>
      </div>
      <div class="field" style="max-width:200px"><label>Bottle colour (used without a photo)</label><input v-model="form.accent" type="color" class="input" style="height:46px;padding:4px" /></div>

      <div class="row">
        <div class="size-box">
          <h4>50 ml</h4>
          <div class="row">
            <div class="field"><label>Price (£)</label><input v-model="form.price_50" type="number" step="0.01" min="0.5" class="input" required /></div>
            <div class="field"><label>Stock</label><input v-model="form.stock_50" type="number" min="0" step="1" class="input" required /></div>
          </div>
        </div>
        <div class="size-box">
          <h4>100 ml</h4>
          <div class="row">
            <div class="field"><label>Price (£)</label><input v-model="form.price_100" type="number" step="0.01" min="0.5" class="input" required /></div>
            <div class="field"><label>Stock</label><input v-model="form.stock_100" type="number" min="0" step="1" class="input" required /></div>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:24px;margin-bottom:8px">
        <label style="display:flex;gap:8px;align-items:center"><input v-model="form.is_featured" type="checkbox" /> Show as bestseller</label>
        <label style="display:flex;gap:8px;align-items:center"><input v-model="form.is_active" type="checkbox" /> Visible in shop</label>
      </div>

      <p style="margin-top:24px">
        <button class="btn" :disabled="saving">{{ saving ? 'Saving…' : form.id ? 'Save changes' : 'Add perfume' }}</button>
        <button type="button" class="btn btn--ghost" @click="form = null">Cancel</button>
      </p>
    </form>

    <!-- Orders -->
    <template v-if="tab === 'orders'">
      <div class="toolbar">
        <select v-model="orderStatus" class="input" style="width:auto"><option value="">All statuses</option><option v-for="s in statuses" :key="s" :value="s">{{ s }}</option></select>
      </div>
      <table class="table">
        <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>{{ o.number }}<div class="muted">{{ dateShort(o.created_at) }}</div></td>
            <td>{{ o.email }}<div class="muted">{{ o.shipping_address.city }}, {{ o.shipping_address.postcode }}</div></td>
            <td>{{ o.items.map((i) => `${i.name} ${i.size_ml}ml × ${i.quantity}`).join(', ') }}</td>
            <td>{{ money(o.total) }}</td>
            <td><span class="pill" :class="o.payment_status === 'paid' ? 'pill--paid' : ''">{{ o.payment_status }}</span></td>
            <td><select class="input" style="padding:6px" :value="o.status" @change="setStatus(o, $event.target.value)"><option v-for="s in allowedStatuses(o)" :key="s" :value="s">{{ s }}</option></select></td>
          </tr>
          <tr v-if="!orders.length"><td colspan="6" class="muted">No orders found.</td></tr>
        </tbody>
      </table>
    </template>
  </div>
</template>
<style scoped>
.admin-management {
  width: 100%;
  min-width: 0;
  padding: 24px;
  background: #ffffff;
  border: 1px solid #e8e5df;
  border-radius: 12px;
  overflow-x: auto;
}

.management-heading {
  margin-bottom: 28px;
}

.management-heading .eyebrow {
  color: #a48346;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 1.5px;
  margin-bottom: 10px;
}

.management-heading h1 {
  font-size: 30px;
  font-weight: 600;
  color: #222222;
  margin: 0 0 10px;
}

.management-heading .muted {
  font-size: 14px;
  color: #777777;
}

.admin-management .table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.admin-management .table th {
  background: #f7f5f0;
  color: #555555;
  font-weight: 600;
  text-align: left;
  padding: 15px 12px;
}

.admin-management .table td {
  padding: 16px 12px;
  border-bottom: 1px solid #eeeae4;
}

.admin-management .table tbody tr:hover {
  background: #fcfbf8;
}

.admin-management .toolbar {
  margin-bottom: 20px;
}

.admin-management .thumb-box {
  border-radius: 8px;
  overflow: hidden;
}

@media (max-width: 768px) {
  .admin-management {
    padding: 15px;
  }

  .management-heading h1 {
    font-size: 24px;
  }

  .admin-management .table {
    min-width: 650px;
  }
}
</style>
