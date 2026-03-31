<template>
  <div class="max-w-lg mx-auto">
    <div v-if="loading" class="text-center py-20 text-gray-400">Loading receipt...</div>
    <div v-else-if="error" class="text-center py-20 text-red-400">{{ error }}</div>
    <div v-else-if="sale" id="receipt-content">

      <!-- Print Controls (hidden when printing) -->
      <div class="flex gap-3 mb-4 no-print">
        <button @click="printMode = 'a4'"
          :class="printMode === 'a4' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">A4 Invoice</button>
        <button @click="printMode = 'thermal'"
          :class="printMode === 'thermal' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Thermal (80mm)</button>
        <button @click="printReceipt"
          class="ml-auto bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors">Print</button>
      </div>

      <!-- A4 Invoice Mode -->
      <div v-if="printMode === 'a4'" class="bg-white rounded-2xl border border-gray-200 p-10 a4-receipt">
        <!-- Header -->
        <div class="flex items-start justify-between mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Omni<span class="text-emerald-600">POS</span></h1>
            <p class="text-gray-400 text-sm mt-1">Official Tax Invoice</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-400">Invoice No.</p>
            <p class="font-mono font-bold text-gray-700">#{{ sale.id.toString().padStart(6, '0') }}</p>
            <p class="text-xs text-gray-400 mt-1">Date</p>
            <p class="text-sm text-gray-700">{{ formatDate(sale.created_at) }}</p>
          </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-200 mb-6"></div>

        <!-- Items Table -->
        <table class="w-full text-sm mb-6">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-2 text-gray-500 font-medium">Item</th>
              <th class="text-center py-2 text-gray-500 font-medium">Qty</th>
              <th class="text-right py-2 text-gray-500 font-medium">Unit Price</th>
              <th class="text-right py-2 text-gray-500 font-medium">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in sale.items" :key="item.id" class="border-b border-gray-100">
              <td class="py-3 text-gray-800 font-medium">{{ item.product_name }}</td>
              <td class="py-3 text-center text-gray-600">{{ item.quantity }}</td>
              <td class="py-3 text-right text-gray-600">RM {{ parseFloat(item.unit_price).toFixed(2) }}</td>
              <td class="py-3 text-right font-medium text-gray-800">RM {{ parseFloat(item.subtotal).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
          <div class="w-64 space-y-2 text-sm">
            <div class="flex justify-between text-gray-500">
              <span>Subtotal</span>
              <span>RM {{ parseFloat(sale.subtotal).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
              <span>Discount</span>
              <span class="text-red-500">− RM {{ parseFloat(sale.discount_amount).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
              <span>SST (6%)</span>
              <span>RM {{ parseFloat(sale.tax_amount).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-800 text-base border-t border-gray-200 pt-2">
              <span>Total</span>
              <span>RM {{ parseFloat(sale.total_amount).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Footer with QR -->
        <div class="border-t border-gray-200 pt-6 flex items-end justify-between">
          <div>
            <p class="text-xs text-gray-400 mb-1">Payment Method</p>
            <p class="text-sm font-medium capitalize text-gray-700">{{ sale.payment_method }}</p>
            <p class="text-xs text-gray-400 mt-3 mb-1">Receipt Token</p>
            <p class="font-mono text-xs text-gray-500 break-all max-w-xs">{{ sale.receipt_token }}</p>
          </div>
          <div class="text-center">
            <img :src="qrCodeUrl" alt="QR Receipt" class="w-24 h-24 mx-auto" />
            <p class="text-xs text-gray-400 mt-1">Scan for digital receipt</p>
          </div>
        </div>

        <p class="text-center text-xs text-gray-300 mt-8">Thank you for your purchase · OmniPOS v1.0</p>
      </div>

      <!-- Thermal Mode -->
      <div v-if="printMode === 'thermal'" class="thermal-receipt bg-white border border-gray-200 rounded-xl p-4 mx-auto"
        style="max-width: 300px; font-family: monospace;">
        <div class="text-center mb-3">
          <p class="font-bold text-lg">OmniPOS</p>
          <p class="text-xs text-gray-500">Official Receipt</p>
          <p class="text-xs text-gray-400">{{ formatDate(sale.created_at) }}</p>
        </div>
        <div class="border-t border-dashed border-gray-300 my-2"></div>
        <div v-for="item in sale.items" :key="item.id" class="text-xs mb-1">
          <div class="flex justify-between">
            <span>{{ item.product_name }}</span>
            <span>RM {{ parseFloat(item.subtotal).toFixed(2) }}</span>
          </div>
          <div class="text-gray-400 pl-2">{{ item.quantity }} x RM {{ parseFloat(item.unit_price).toFixed(2) }}</div>
        </div>
        <div class="border-t border-dashed border-gray-300 my-2"></div>
        <div class="text-xs space-y-1">
          <div class="flex justify-between"><span>Subtotal</span><span>RM {{ parseFloat(sale.subtotal).toFixed(2)
              }}</span></div>
          <div class="flex justify-between"><span>Discount</span><span>-RM {{
            parseFloat(sale.discount_amount).toFixed(2) }}</span></div>
          <div class="flex justify-between"><span>SST</span><span>RM {{ parseFloat(sale.tax_amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-bold text-sm border-t border-dashed border-gray-300 pt-1 mt-1">
            <span>TOTAL</span><span>RM {{ parseFloat(sale.total_amount).toFixed(2) }}</span>
          </div>
        </div>
        <div class="border-t border-dashed border-gray-300 my-3"></div>
        <div class="text-center">
          <img :src="qrCodeUrl" alt="QR" class="w-20 h-20 mx-auto mb-1" />
          <p class="text-xs text-gray-400">Scan for digital receipt</p>
          <p class="text-xs text-gray-300 mt-2">Thank you!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const sale = ref(null);
const loading = ref(true);
const error = ref(null);
const printMode = ref('a4');

const qrCodeUrl = computed(() => {
  if (!sale.value) return '';
  const receiptUrl = `${window.location.origin}/receipt/${sale.value.receipt_token}`;
  return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(receiptUrl)}`;
});

function formatDate(dt) {
  return new Date(dt).toLocaleString('en-MY', { dateStyle: 'long', timeStyle: 'short' });
}

function printReceipt() {
  window.print();
}

onMounted(async () => {
  try {
    const res = await axios.get(`/api/sales/receipt/${route.params.token}`);
    sale.value = res.data;
  } catch {
    error.value = 'Receipt not found.';
  } finally {
    loading.value = false;
  }
});
</script>

<style>
@media print {
  .no-print {
    display: none !important;
  }

  nav {
    display: none !important;
  }

  body {
    background: white !important;
  }

  /* A4 mode */
  .a4-receipt {
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }

  /* Thermal mode — force 80mm width */
  .thermal-receipt {
    width: 80mm !important;
    max-width: 80mm !important;
    margin: 0 auto !important;
    border: none !important;
    font-size: 10px !important;
  }
}
</style>