<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Products</h1>
      <button @click="openCreate"
        class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
        + Add Product
      </button>
    </div>

    <!-- Search + Filter -->
    <div class="flex gap-3 mb-4">
      <input v-model="search" type="text" placeholder="Search by name or barcode..."
        class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
      <select v-model="filterCategory"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-20 text-gray-400 text-sm">Loading products...</div>

    <!-- Products Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Product</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Barcode</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Category</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Price</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Stock</th>
            <th class="text-center px-4 py-3 text-gray-500 font-medium">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in filteredProducts" :key="product.id"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-800">{{ product.name }}</td>
            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ product.barcode }}</td>
            <td class="px-4 py-3 text-gray-500">{{ product.category?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-right text-gray-700">RM {{ parseFloat(product.price).toFixed(2) }}</td>
            <td class="px-4 py-3 text-right">
              <span :class="product.is_low_stock ? 'text-red-600 font-bold' : 'text-gray-700'">
                {{ product.stock_qty }}
                <span v-if="product.is_low_stock"
                  class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Low</span>
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2 py-1 rounded-full font-medium">
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(product)" class="text-xs text-blue-500 hover:text-blue-700">Edit</button>
                <button @click="confirmDelete(product)" class="text-xs text-red-400 hover:text-red-600">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredProducts.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">No products found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create / Edit Modal -->
    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-bold text-gray-800 mb-4">{{ editingProduct ? 'Edit Product' : 'Add Product' }}</h2>

        <!-- Scan-to-Fill section (only on create) -->
        <div v-if="!editingProduct" class="mb-4">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-medium text-gray-500">Scan-to-Fill Barcode</span>
            <button @click="toggleFormScanner"
              :class="formScannerActive ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'"
              class="text-xs px-3 py-1 rounded-full font-medium transition-colors">
              {{ formScannerActive ? 'Stop Scanner' : 'Scan Barcode' }}
            </button>
          </div>
          <div v-if="formScannerActive" id="form-qr-reader" class="w-full rounded-lg overflow-hidden mb-2"></div>
          <p v-if="formScanMessage" class="text-xs text-emerald-600">{{ formScanMessage }}</p>
        </div>

        <div class="space-y-3">
          <div>
            <label class="text-xs font-medium text-gray-500 mb-1 block">Barcode *</label>
            <input v-model="form.barcode" type="text" placeholder="e.g. 1234567890123"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 mb-1 block">Product Name *</label>
            <input v-model="form.name" type="text" placeholder="e.g. Teh Tarik"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
          </div>
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Price (RM) *</label>
              <input v-model="form.price" type="number" min="0" step="0.01" placeholder="0.00"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
            <div class="flex-1">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Cost Price (RM)</label>
              <input v-model="form.cost_price" type="number" min="0" step="0.01" placeholder="0.00"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
          </div>
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Stock Qty *</label>
              <input v-model="form.stock_qty" type="number" min="0" placeholder="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
            <div class="flex-1">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Low Stock Alert</label>
              <input v-model="form.low_stock_threshold" type="number" min="0" placeholder="5"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 mb-1 block">Category</label>
            <div class="flex gap-2">
              <select v-model="form.category_id"
                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <option value="">No category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <button @click="showCategoryModal = true"
                class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-lg transition-colors whitespace-nowrap">+
                New</button>
            </div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 mb-1 block">Description</label>
            <textarea v-model="form.description" rows="2" placeholder="Optional description..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"></textarea>
          </div>
          <div v-if="editingProduct" class="flex items-center gap-2">
            <input type="checkbox" v-model="form.is_active" id="is_active" class="rounded" />
            <label for="is_active" class="text-sm text-gray-600">Active</label>
          </div>
        </div>
        <p v-if="formError" class="text-red-500 text-xs mt-3">{{ formError }}</p>
        <div class="flex gap-2 mt-5">
          <button @click="closeModal"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
          <button @click="saveProduct" :disabled="saving"
            class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:bg-gray-300">
            {{ saving ? 'Saving...' : (editingProduct ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add Category Modal -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-60">
      <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add Category</h2>
        <div class="space-y-3">
          <input v-model="newCategory.name" type="text" placeholder="Category name *"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
          <input v-model="newCategory.description" type="text" placeholder="Description (optional)"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
        </div>
        <div class="flex gap-2 mt-4">
          <button @click="showCategoryModal = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm">Cancel</button>
          <button @click="saveCategory"
            class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

const products           = ref([]);
const categories         = ref([]);
const loading            = ref(true);
const search             = ref('');
const filterCategory     = ref('');
const showModal          = ref(false);
const showCategoryModal  = ref(false);
const editingProduct     = ref(null);
const saving             = ref(false);
const formError          = ref('');
const formScannerActive  = ref(false);
const formScanMessage    = ref('');
let formQrCode           = null;

const newCategory = ref({ name: '', description: '' });

const form = ref({
  barcode: '', name: '', price: '', cost_price: '',
  stock_qty: '', low_stock_threshold: 5,
  category_id: '', description: '', is_active: true,
});

const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchSearch = !search.value ||
      p.name.toLowerCase().includes(search.value.toLowerCase()) ||
      p.barcode.includes(search.value);
    const matchCat = !filterCategory.value || p.category_id == filterCategory.value;
    return matchSearch && matchCat;
  });
});

async function fetchProducts() {
  loading.value = true;
  try {
    const [pRes, cRes] = await Promise.all([
      axios.get('/api/products'),
      axios.get('/api/categories'),
    ]);
    products.value   = pRes.data;
    categories.value = cRes.data;
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editingProduct.value = null;
  form.value = { barcode: '', name: '', price: '', cost_price: '', stock_qty: '', low_stock_threshold: 5, category_id: '', description: '', is_active: true };
  formError.value      = '';
  formScanMessage.value = '';
  showModal.value      = true;
}

function openEdit(product) {
  editingProduct.value = product;
  form.value = {
    barcode:             product.barcode,
    name:                product.name,
    price:               product.price,
    cost_price:          product.cost_price ?? '',
    stock_qty:           product.stock_qty,
    low_stock_threshold: product.low_stock_threshold,
    category_id:         product.category_id ?? '',
    description:         product.description ?? '',
    is_active:           product.is_active,
  };
  formError.value = '';
  showModal.value = true;
}

async function closeModal() {
  await stopFormScanner();
  showModal.value = false;
}

// Scan-to-Fill for the product form
async function toggleFormScanner() {
  if (formScannerActive.value) {
    await stopFormScanner();
    return;
  }

  formScannerActive.value = true;
  await new Promise(r => setTimeout(r, 200));

  formQrCode = new Html5Qrcode('form-qr-reader');

  const config = {
    fps: 15,
    qrbox: { width: 280, height: 120 },
    formatsToSupport: [0, 4, 5, 6, 7, 8, 10, 11],
    experimentalFeatures: { useBarCodeDetectorIfSupported: true },
  };

  try {
    await formQrCode.start(
      { facingMode: 'environment' },
      config,
      async (decodedText) => {
        form.value.barcode    = decodedText;
        formScanMessage.value = `✓ Barcode captured: ${decodedText}`;
        await stopFormScanner();
      },
      () => {}
    );
  } catch {
    const devices = await Html5Qrcode.getCameras();
    if (devices.length > 0) {
      await formQrCode.start(devices[0].id, config,
        async (decodedText) => {
          form.value.barcode    = decodedText;
          formScanMessage.value = `✓ Barcode captured: ${decodedText}`;
          await stopFormScanner();
        },
        () => {}
      );
    }
  }
}

async function stopFormScanner() {
  try { await formQrCode?.stop(); } catch {}
  formScannerActive.value = false;
}

async function saveCategory() {
  if (!newCategory.value.name.trim()) return;
  try {
    const res = await axios.post('/api/categories', newCategory.value);
    categories.value.push(res.data);
    form.value.category_id = res.data.id;
    showCategoryModal.value = false;
    newCategory.value = { name: '', description: '' };
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to create category.');
  }
}

async function saveProduct() {
  formError.value = '';
  saving.value    = true;
  try {
    if (editingProduct.value) {
      const res = await axios.put(`/api/products/${editingProduct.value.id}`, form.value);
      const idx = products.value.findIndex(p => p.id === editingProduct.value.id);
      if (idx !== -1) products.value[idx] = res.data;
    } else {
      const res = await axios.post('/api/products', form.value);
      products.value.push(res.data);
    }
    await closeModal();
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to save product.';
  } finally {
    saving.value = false;
  }
}

async function confirmDelete(product) {
  if (!confirm(`Delete "${product.name}"? This cannot be undone.`)) return;
  try {
    await axios.delete(`/api/products/${product.id}`);
    products.value = products.value.filter(p => p.id !== product.id);
  } catch {
    alert('Could not delete product.');
  }
}

onMounted(fetchProducts);
onUnmounted(async () => { await stopFormScanner(); });
</script>