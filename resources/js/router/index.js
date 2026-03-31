import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'checkout',
        component: () => import('../pages/CheckoutPage.vue'),
    },
    {
        path: '/products',
        name: 'products',
        component: () => import('../pages/ProductsPage.vue'),
    },
    {
        path: '/inventory',
        name: 'inventory',
        component: () => import('../pages/InventoryPage.vue'),
    },
    {
        path: '/sales',
        name: 'sales',
        component: () => import('../pages/SalesPage.vue'),
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('../pages/DashboardPage.vue'),
    },
    {
        path: '/receipt/:token',
        name: 'receipt',
        component: () => import('../pages/ReceiptPage.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;