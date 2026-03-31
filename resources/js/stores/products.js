import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useProductStore = defineStore('products', () => {
    const products    = ref([]);
    const loading     = ref(false);
    const error       = ref(null);

    async function fetchProducts() {
        loading.value = true;
        try {
            const res     = await axios.get('/api/products');
            products.value = res.data;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    async function findByBarcode(barcode) {
        const res = await axios.get(`/api/products/barcode/${barcode}`);
        return res.data;
    }

    async function createProduct(data) {
        const res = await axios.post('/api/products', data);
        products.value.push(res.data);
        return res.data;
    }

    async function updateProduct(id, data) {
        const res = await axios.put(`/api/products/${id}`, data);
        const idx = products.value.findIndex(p => p.id === id);
        if (idx !== -1) products.value[idx] = res.data;
        return res.data;
    }

    async function deleteProduct(id) {
        await axios.delete(`/api/products/${id}`);
        products.value = products.value.filter(p => p.id !== id);
    }

    return {
        products, loading, error,
        fetchProducts, findByBarcode,
        createProduct, updateProduct, deleteProduct,
    };
});