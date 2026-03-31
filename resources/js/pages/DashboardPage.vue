<template>
    <div>
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>
  
      <!-- KPI Cards -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Today's Revenue</p>
          <p class="text-2xl font-bold text-emerald-600">RM {{ todayRevenue }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Today's Sales</p>
          <p class="text-2xl font-bold text-gray-800">{{ todaySales }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Total Products</p>
          <p class="text-2xl font-bold text-gray-800">{{ totalProducts }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 mb-1">Low Stock Items</p>
          <p class="text-2xl font-bold" :class="lowStockItems > 0 ? 'text-red-600' : 'text-gray-800'">
            {{ lowStockItems }}
          </p>
        </div>
      </div>
  
      <div class="grid grid-cols-2 gap-4">
        <!-- Top 5 Products Chart -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Top 5 Selling Products</h2>
          <canvas ref="topProductsChart" height="200"></canvas>
          <p v-if="topProducts.length === 0" class="text-center text-gray-400 text-sm py-8">No sales data yet.</p>
        </div>
  
        <!-- Sales by Hour Chart -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Peak Sales Hours (Today)</h2>
          <canvas ref="peakHoursChart" height="200"></canvas>
          <p v-if="salesByHour.every(v => v === 0)" class="text-center text-gray-400 text-sm py-8">No sales today yet.</p>
        </div>
  
        <!-- Low Stock Watchdog -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-700 mb-4">
            Stock Watchdog
            <span v-if="lowStockItems > 0" class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ lowStockItems }} alerts</span>
          </h2>
          <div v-if="lowStockProducts.length === 0" class="text-center text-gray-400 text-sm py-8">
            All products are well stocked ✓
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="p in lowStockProducts"
              :key="p.id"
              class="flex items-center justify-between bg-red-50 border border-red-100 rounded-lg px-3 py-2"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">{{ p.name }}</p>
                <p class="text-xs text-gray-400">Threshold: {{ p.low_stock_threshold }}</p>
              </div>
              <span class="text-red-600 font-bold text-lg">{{ p.stock_qty }}</span>
            </div>
          </div>
        </div>
  
        <!-- Recent Activity -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Recent Sales</h2>
          <div v-if="recentSales.length === 0" class="text-center text-gray-400 text-sm py-8">No recent sales.</div>
          <div v-else class="space-y-2">
            <div
              v-for="sale in recentSales"
              :key="sale.id"
              class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">Sale #{{ sale.id }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(sale.created_at) }} · {{ sale.payment_method }}</p>
              </div>
              <span class="font-bold text-emerald-600">RM {{ parseFloat(sale.total_amount).toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, nextTick } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';
  
  const sales           = ref([]);
  const products        = ref([]);
  const inventory       = ref([]);
  const topProductsChart = ref(null);
  const peakHoursChart  = ref(null);
  let chartInstances    = [];
  
  const today = new Date().toISOString().split('T')[0];
  
  const todaySales = computed(() =>
    sales.value.filter(s => s.created_at.startsWith(today)).length
  );
  const todayRevenue = computed(() =>
    sales.value
      .filter(s => s.created_at.startsWith(today))
      .reduce((sum, s) => sum + parseFloat(s.total_amount), 0)
      .toFixed(2)
  );
  const totalProducts  = computed(() => products.value.length);
  const lowStockItems  = computed(() => inventory.value.filter(p => p.is_low_stock).length);
  const lowStockProducts = computed(() => inventory.value.filter(p => p.is_low_stock));
  const recentSales    = computed(() => [...sales.value].slice(0, 5));
  
  // Top 5 products by qty sold
  const topProducts = computed(() => {
    const counts = {};
    sales.value.forEach(sale => {
      sale.items?.forEach(item => {
        counts[item.product_name] = (counts[item.product_name] || 0) + item.quantity;
      });
    });
    return Object.entries(counts)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 5);
  });
  
  // Sales count by hour of day
  const salesByHour = computed(() => {
    const hours = Array(24).fill(0);
    sales.value
      .filter(s => s.created_at.startsWith(today))
      .forEach(s => {
        const hour = new Date(s.created_at).getHours();
        hours[hour]++;
      });
    return hours;
  });
  
  function formatDate(dt) {
    return new Date(dt).toLocaleString('en-MY', { dateStyle: 'short', timeStyle: 'short' });
  }
  
  function renderCharts() {
    chartInstances.forEach(c => c.destroy());
    chartInstances = [];
  
    if (topProducts.value.length > 0 && topProductsChart.value) {
      chartInstances.push(new Chart(topProductsChart.value, {
        type: 'bar',
        data: {
          labels:   topProducts.value.map(p => p[0]),
          datasets: [{
            label:           'Units Sold',
            data:            topProducts.value.map(p => p[1]),
            backgroundColor: '#10b981',
            borderRadius:    6,
          }],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales:  { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
      }));
    }
  
    if (peakHoursChart.value) {
      chartInstances.push(new Chart(peakHoursChart.value, {
        type: 'line',
        data: {
          labels:   Array.from({ length: 24 }, (_, i) => `${i}:00`),
          datasets: [{
            label:           'Sales',
            data:            salesByHour.value,
            borderColor:     '#10b981',
            backgroundColor: 'rgba(16,185,129,0.1)',
            fill:            true,
            tension:         0.4,
            pointRadius:     3,
          }],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales:  { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
      }));
    }
  }
  
  onMounted(async () => {
    const [salesRes, productsRes, invRes] = await Promise.all([
      axios.get('/api/sales'),
      axios.get('/api/products'),
      axios.get('/api/inventory'),
    ]);
    sales.value     = salesRes.data;
    products.value  = productsRes.data;
    inventory.value = invRes.data;
    await nextTick();
    renderCharts();
  });
  </script>