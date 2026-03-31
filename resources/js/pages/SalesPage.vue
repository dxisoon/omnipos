<template>
    <div>
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Sales</h1>
        <div class="flex items-center gap-3">
          <input
            v-model="filterDate"
            type="date"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
          />
          <button
            @click="exportDaily"
            :disabled="exporting"
            class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:bg-gray-300 transition-colors"
          >
            {{ exporting ? 'Exporting...' : 'Export Excel' }}
          </button>
        </div>
      </div>
  
      <!-- Summary Cards -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Total Sales</p>
          <p class="text-2xl font-bold text-gray-800">{{ sales.length }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Total Revenue</p>
          <p class="text-2xl font-bold text-emerald-600">RM {{ totalRevenue }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Items Sold</p>
          <p class="text-2xl font-bold text-gray-800">{{ totalItems }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Avg. Sale</p>
          <p class="text-2xl font-bold text-gray-800">RM {{ avgSale }}</p>
        </div>
      </div>
  
      <!-- Loading -->
      <div v-if="loading" class="text-center py-20 text-gray-400 text-sm">Loading sales...</div>
  
      <!-- Sales Table -->
      <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">#</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Date & Time</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Items</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Subtotal</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Discount</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total</th>
              <th class="text-center px-4 py-3 text-gray-500 font-medium">Method</th>
              <th class="text-center px-4 py-3 text-gray-500 font-medium">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="sale in sales"
              :key="sale.id"
              class="border-t border-gray-100 hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3 text-gray-400 font-mono text-xs">#{{ sale.id }}</td>
              <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ formatDate(sale.created_at) }}</td>
              <td class="px-4 py-3">
                <div v-for="item in sale.items" :key="item.id" class="text-xs text-gray-600">
                  {{ item.product_name }} ×{{ item.quantity }}
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-600">RM {{ parseFloat(sale.subtotal).toFixed(2) }}</td>
              <td class="px-4 py-3 text-right text-red-400">
                {{ parseFloat(sale.discount_amount) > 0 ? '− RM ' + parseFloat(sale.discount_amount).toFixed(2) : '—' }}
              </td>
              <td class="px-4 py-3 text-right font-bold text-gray-800">RM {{ parseFloat(sale.total_amount).toFixed(2) }}</td>
              <td class="px-4 py-3 text-center">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full capitalize">{{ sale.payment_method }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-700': sale.payment_status === 'paid',
                    'bg-red-100 text-red-600': sale.payment_status === 'declined',
                    'bg-amber-100 text-amber-700': sale.payment_status === 'pending',
                  }"
                  class="text-xs px-2 py-1 rounded-full font-medium capitalize"
                >{{ sale.payment_status }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="viewReceipt(sale.receipt_token)"
                  class="text-xs text-emerald-600 hover:text-emerald-800"
                >Receipt</button>
              </td>
            </tr>
            <tr v-if="sales.length === 0">
              <td colspan="9" class="px-4 py-12 text-center text-gray-400">No sales found for this date.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, watch } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  const router     = useRouter();
  const sales      = ref([]);
  const loading    = ref(true);
  const exporting  = ref(false);
  const filterDate = ref(new Date().toISOString().split('T')[0]);
  
  const totalRevenue = computed(() =>
    sales.value.reduce((s, x) => s + parseFloat(x.total_amount), 0).toFixed(2)
  );
  const totalItems = computed(() =>
    sales.value.reduce((s, x) => s + x.items.reduce((a, i) => a + i.quantity, 0), 0)
  );
  const avgSale = computed(() =>
    sales.value.length ? (parseFloat(totalRevenue.value) / sales.value.length).toFixed(2) : '0.00'
  );
  
  async function fetchSales() {
    loading.value = true;
    try {
      const res  = await axios.get('/api/sales', { params: { date: filterDate.value } });
      sales.value = res.data;
    } finally {
      loading.value = false;
    }
  }
  
  async function exportDaily() {
    exporting.value = true;
    try {
      const res = await axios.get('/api/sales/export/daily', {
        params:       { date: filterDate.value },
        responseType: 'blob',
      });
      const url  = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href  = url;
      link.setAttribute('download', `sales-${filterDate.value}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch {
      alert('No sales to export for this date.');
    } finally {
      exporting.value = false;
    }
  }
  
  function viewReceipt(token) {
    router.push(`/receipt/${token}`);
  }
  
  function formatDate(dt) {
    return new Date(dt).toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
  }
  
  watch(filterDate, fetchSales);
  onMounted(fetchSales);
  </script>