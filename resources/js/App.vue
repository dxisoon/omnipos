<template>
    <div class="min-h-screen bg-gray-100">
      <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-xl font-bold text-gray-800">Omni</span>
          <span class="text-xl font-bold text-emerald-600">POS</span>
        </div>
        <div class="flex items-center gap-6 text-sm font-medium text-gray-600">
          <router-link to="/" class="hover:text-emerald-600 transition-colors">Checkout</router-link>
          <router-link to="/products" class="hover:text-emerald-600 transition-colors">Products</router-link>
          <router-link to="/inventory" class="hover:text-emerald-600 transition-colors">Inventory</router-link>
          <router-link to="/sales" class="hover:text-emerald-600 transition-colors">Sales</router-link>
          <router-link to="/dashboard" class="hover:text-emerald-600 transition-colors">Dashboard</router-link>
        </div>
      </nav>
      <main class="p-6">
        <router-view />
      </main>
    </div>
  </template>