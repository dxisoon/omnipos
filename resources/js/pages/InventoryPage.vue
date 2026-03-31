<template>
    <div>
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Inventory</h1>
        <div class="flex items-center gap-3">
          <span v-if="lowStockCount > 0" class="bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5 rounded-full">
            ⚠ {{ lowStockCount }} low stock
          </span>
          <button
            @click="activeTab = 'stock'"
            :class="activeTab === 'stock' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          >Stock</button>
          <button
            @click="activeTab = 'logs'"
            :class="activeTab === 'logs' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          >Audit Log</button>
        </div>
      </div>
  
      <!-- Stock Tab -->
      <div v-if="activeTab === 'stock'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Product</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Stock</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Threshold</th>
                <th class="text-center px-4 py-3 text-gray-500 font-medium">Status</th>
                <th class="text-center px-4 py-3 text-gray-500 font-medium">Adjust</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in products"
                :key="product.id"
                class="border-t border-gray-100 hover:bg-gray-50 transition-colors"
                :class="product.is_low_stock ? 'bg-red-50' : ''"
              >
                <td class="px-4 py-3">
                  <p class="font-medium text-gray-800">{{ product.name }}</p>
                  <p class="text-xs text-gray-400 font-mono">{{ product.barcode }}</p>
                </td>
                <td class="px-4 py-3 text-right font-bold" :class="product.is_low_stock ? 'text-red-600' : 'text-gray-800'">
                  {{ product.stock_qty }}
                </td>
                <td class="px-4 py-3 text-right text-gray-500">{{ product.low_stock_threshold }}</td>
                <td class="px-4 py-3 text-center">
                  <span v-if="product.is_low_stock" class="bg-red-100 text-red-600 text-xs font-semibold px-2 py-1 rounded-full">Low Stock</span>
                  <span v-else class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2 py-1 rounded-full">OK</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    @click="openAdjust(product)"
                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg transition-colors"
                  >Adjust</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  
      <!-- Audit Log Tab -->
      <div v-if="activeTab === 'logs'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Time</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Product</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">By</th>
                <th class="text-center px-4 py-3 text-gray-500 font-medium">Change</th>
                <th class="text-center px-4 py-3 text-gray-500 font-medium">Before → After</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Reason</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs" :key="log.id" class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ log.product?.name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ log.user?.name }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="log.change_qty > 0 ? 'text-emerald-600' : 'text-red-500'" class="font-bold">
                    {{ log.change_qty > 0 ? '+' : '' }}{{ log.change_qty }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center text-gray-500">{{ log.stock_before }} → {{ log.stock_after }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full capitalize">{{ log.reason?.replace('_', ' ') }}</span>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ log.notes ?? '—' }}</td>
              </tr>
              <tr v-if="logs.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">No inventory logs yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  
      <!-- Adjust Modal -->
      <div v-if="showAdjustModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
          <h2 class="text-lg font-bold text-gray-800 mb-1">Adjust Stock</h2>
          <p class="text-sm text-gray-500 mb-4">{{ adjustingProduct?.name }} — current stock: <strong>{{ adjustingProduct?.stock_qty }}</strong></p>
          <div class="space-y-3">
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Change Qty (use − for reduction)</label>
              <input v-model="adjustForm.change_qty" type="number" placeholder="e.g. +10 or -5"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Reason *</label>
              <select v-model="adjustForm.reason"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <option value="restock">Restock</option>
                <option value="manual_adjustment">Manual Adjustment</option>
                <option value="damage">Damage / Loss</option>
                <option value="correction">Correction</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Notes</label>
              <input v-model="adjustForm.notes" type="text" placeholder="Optional notes..."
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-sm">
              <span class="text-gray-500">New stock will be: </span>
              <strong :class="projectedStock < 0 ? 'text-red-600' : 'text-emerald-600'">
                {{ projectedStock }}
              </strong>
            </div>
          </div>
          <p v-if="adjustError" class="text-red-500 text-xs mt-2">{{ adjustError }}</p>
          <div class="flex gap-2 mt-4">
            <button @click="showAdjustModal = false" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm">Cancel</button>
            <button @click="submitAdjust" :disabled="adjusting || projectedStock < 0"
              class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:bg-gray-300">
              {{ adjusting ? 'Saving...' : 'Confirm' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import axios from 'axios';
  
  const products         = ref([]);
  const logs             = ref([]);
  const activeTab        = ref('stock');
  const showAdjustModal  = ref(false);
  const adjustingProduct = ref(null);
  const adjusting        = ref(false);
  const adjustError      = ref('');
  const adjustForm       = ref({ change_qty: '', reason: 'restock', notes: '' });
  
  const lowStockCount = computed(() => products.value.filter(p => p.is_low_stock).length);
  
  const projectedStock = computed(() => {
    if (!adjustingProduct.value) return 0;
    return adjustingProduct.value.stock_qty + parseInt(adjustForm.value.change_qty || 0);
  });
  
  async function fetchAll() {
    const [invRes, logRes] = await Promise.all([
      axios.get('/api/inventory'),
      axios.get('/api/inventory/logs'),
    ]);
    products.value = invRes.data;
    logs.value     = logRes.data;
  }
  
  function openAdjust(product) {
    adjustingProduct.value = product;
    adjustForm.value       = { change_qty: '', reason: 'restock', notes: '' };
    adjustError.value      = '';
    showAdjustModal.value  = true;
  }
  
  async function submitAdjust() {
    adjustError.value = '';
    adjusting.value   = true;
    try {
      await axios.post(`/api/inventory/${adjustingProduct.value.id}/adjust`, adjustForm.value);
      await fetchAll();
      showAdjustModal.value = false;
    } catch (e) {
      adjustError.value = e.response?.data?.message || 'Adjustment failed.';
    } finally {
      adjusting.value = false;
    }
  }
  
  function formatDate(dt) {
    return new Date(dt).toLocaleString('en-MY', { dateStyle: 'short', timeStyle: 'short' });
  }
  
  onMounted(fetchAll);
  </script>