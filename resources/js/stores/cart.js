import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const items     = ref([]);
    const heldCarts = ref([]);
    const discount  = ref({ type: 'fixed', amount: 0 });
    const taxRate   = ref(0.06); // 6% SST

    // Computed
    const subtotal = computed(() =>
        items.value.reduce((sum, i) => sum + i.price * i.quantity, 0)
    );

    const discountAmount = computed(() => {
        if (discount.value.type === 'percentage') {
            return subtotal.value * (discount.value.amount / 100);
        }
        return parseFloat(discount.value.amount) || 0;
    });

    const taxAmount = computed(() =>
        (subtotal.value - discountAmount.value) * taxRate.value
    );

    const total = computed(() =>
        subtotal.value - discountAmount.value + taxAmount.value
    );

    // Actions
    function addItem(product) {
        const existing = items.value.find(i => i.id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            items.value.push({ ...product, quantity: 1 });
        }
    }

    function removeItem(productId) {
        items.value = items.value.filter(i => i.id !== productId);
    }

    function updateQuantity(productId, quantity) {
        const item = items.value.find(i => i.id === productId);
        if (item) {
            if (quantity <= 0) removeItem(productId);
            else item.quantity = quantity;
        }
    }

    function clearCart() {
        items.value      = [];
        discount.value   = { type: 'fixed', amount: 0 };
    }

    function holdCart(label = '') {
        if (items.value.length === 0) return;
        heldCarts.value.push({
            id:        Date.now(),
            label:     label || `Cart ${heldCarts.value.length + 1}`,
            items:     [...items.value],
            discount:  { ...discount.value },
            savedAt:   new Date().toLocaleTimeString(),
        });
        clearCart();
    }

    function resumeCart(cartId) {
        const held = heldCarts.value.find(c => c.id === cartId);
        if (!held) return;
        items.value    = held.items;
        discount.value = held.discount;
        heldCarts.value = heldCarts.value.filter(c => c.id !== cartId);
    }

    function setDiscount(type, amount) {
        discount.value = { type, amount };
    }

    return {
        items, heldCarts, discount, taxRate,
        subtotal, discountAmount, taxAmount, total,
        addItem, removeItem, updateQuantity,
        clearCart, holdCart, resumeCart, setDiscount,
    };
});