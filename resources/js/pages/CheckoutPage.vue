<template>
    <div class="flex gap-4 h-[calc(100vh-80px)]">

        <!-- LEFT PANEL: Scanner + Product Search -->
        <div class="flex flex-col gap-4 w-2/3">

            <!-- Barcode Scanner Bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <h2 class="font-semibold text-gray-700">Barcode Scanner</h2>
                    <button @click="toggleCamera"
                        :class="cameraActive ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'"
                        class="text-xs px-3 py-1 rounded-full font-medium transition-colors">
                        {{ cameraActive ? 'Stop Camera' : 'Start Camera' }}
                    </button>
                </div>

                <!-- Camera View -->
                <div v-if="cameraActive" id="qr-reader" class="w-full rounded-lg overflow-hidden mb-3"></div>

                <!-- Manual Barcode Input -->
                <div class="flex gap-2">
                    <input v-model="barcodeInput" @keyup.enter="searchBarcode" type="text"
                        placeholder="Scan or type barcode..."
                        class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                    <button @click="searchBarcode"
                        class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
                        Search
                    </button>
                </div>

                <!-- Scanner feedback -->
                <p v-if="scanMessage" :class="scanSuccess ? 'text-emerald-600' : 'text-red-500'" class="text-xs mt-2">
                    {{ scanMessage }}
                </p>
            </div>

            <!-- Held Carts -->
            <div v-if="cart.heldCarts.length > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-amber-700 mb-2">Held Carts</h3>
                <div class="flex flex-wrap gap-2">
                    <button v-for="held in cart.heldCarts" :key="held.id" @click="cart.resumeCart(held.id)"
                        class="bg-white border border-amber-300 text-amber-700 text-xs px-3 py-1.5 rounded-lg hover:bg-amber-100 transition-colors">
                        {{ held.label }} · {{ held.savedAt }}
                    </button>
                </div>
            </div>

            <!-- Cart Items Table -->
            <div class="bg-white rounded-xl border border-gray-200 flex-1 overflow-auto">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700">Cart ({{ cart.items.length }} items)</h2>
                    <button v-if="cart.items.length > 0" @click="cart.clearCart()"
                        class="text-xs text-red-500 hover:text-red-700">
                        Clear all
                    </button>
                </div>

                <!-- Empty cart -->
                <div v-if="cart.items.length === 0"
                    class="flex flex-col items-center justify-center h-48 text-gray-400">
                    <div class="text-5xl mb-3">🛒</div>
                    <p class="text-sm">Cart is empty — scan a product to begin</p>
                </div>

                <!-- Cart rows -->
                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-gray-500 font-medium">Product</th>
                            <th class="text-center px-4 py-2 text-gray-500 font-medium">Qty</th>
                            <th class="text-right px-4 py-2 text-gray-500 font-medium">Unit</th>
                            <th class="text-right px-4 py-2 text-gray-500 font-medium">Subtotal</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in cart.items" :key="item.id"
                            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ item.name }}</p>
                                <p class="text-xs text-gray-400">{{ item.barcode }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="cart.updateQuantity(item.id, item.quantity - 1)"
                                        class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-lg leading-none">−</button>
                                    <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
                                    <button @click="cart.updateQuantity(item.id, item.quantity + 1)"
                                        class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-lg leading-none">+</button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">RM {{ parseFloat(item.price).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">
                                RM {{ (parseFloat(item.price) * item.quantity).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="cart.removeItem(item.id)"
                                    class="text-red-400 hover:text-red-600 text-lg">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT PANEL: Totals + Payment -->
        <div class="flex flex-col gap-4 w-1/3">

            <!-- Discount -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-3">Discount</h3>
                <div class="flex gap-2 mb-2">
                    <button @click="discountType = 'fixed'"
                        :class="discountType === 'fixed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors">RM Fixed</button>
                    <button @click="discountType = 'percentage'"
                        :class="discountType === 'percentage' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors">% Percent</button>
                </div>
                <input v-model="discountValue" @input="cart.setDiscount(discountType, discountValue)" type="number"
                    min="0" :placeholder="discountType === 'fixed' ? 'Enter amount (RM)' : 'Enter percent (%)'"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            </div>

            <!-- Currency Toggle -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-700 mb-3">Currency</h3>
                <div class="flex flex-wrap gap-2">
                    <button v-for="cur in currencies" :key="cur" @click="selectedCurrency = cur"
                        :class="selectedCurrency === cur ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">{{ cur }}</button>
                </div>
                <p v-if="selectedCurrency !== 'MYR' && rates" class="text-xs text-gray-400 mt-2">
                    1 MYR = {{ rates[selectedCurrency] }} {{ selectedCurrency }}
                </p>
            </div>

            <!-- Order Summary -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex-1">
                <h3 class="font-semibold text-gray-700 mb-4">Order Summary</h3>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>{{ formatAmount(cart.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Discount</span>
                        <span class="text-red-500">− {{ formatAmount(cart.discountAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>SST (6%)</span>
                        <span>{{ formatAmount(cart.taxAmount) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3 flex justify-between font-bold text-gray-800 text-lg">
                        <span>Total</span>
                        <span>{{ formatAmount(cart.total) }}</span>
                    </div>
                    <div v-if="selectedCurrency !== 'MYR' && rates" class="text-xs text-emerald-600 text-right">
                        ≈ {{ convertedTotal }} {{ selectedCurrency }}
                    </div>
                </div>

                <!-- Hold Cart -->
                <button v-if="cart.items.length > 0" @click="holdCurrentCart"
                    class="w-full mt-4 border border-amber-400 text-amber-600 py-2 rounded-lg text-sm font-medium hover:bg-amber-50 transition-colors">
                    Hold Cart
                </button>

                <!-- Payment Method -->
                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-500 mb-2">Payment Method</p>
                    <div class="flex gap-2">
                        <button v-for="method in ['cash', 'card', 'simulation']" :key="method"
                            @click="paymentMethod = method"
                            :class="paymentMethod === method ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'"
                            class="flex-1 py-1.5 rounded-lg text-xs font-medium capitalize transition-colors">{{ method
                            }}</button>
                    </div>
                </div>

                <!-- Card number input for simulation -->
                <div v-if="paymentMethod === 'simulation'" class="mt-3">
                    <input v-model="testCardNumber" type="text" placeholder="Test card: 4111111111111111"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                    <p class="text-xs text-gray-400 mt-1">Decline: 4000000000000002</p>
                </div>

                <!-- Checkout Button -->
                <button @click="processCheckout" :disabled="cart.items.length === 0 || processing"
                    class="w-full mt-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-xl font-bold text-base transition-colors">
                    {{ processing ? 'Processing...' : `Pay ${formatAmount(cart.total)}` }}
                </button>
            </div>
        </div>

        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center">
                <div class="text-6xl mb-4">✅</div>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Payment Successful!</h2>
                <p class="text-gray-500 text-sm mb-4">Transaction completed</p>
                <div class="bg-gray-50 rounded-lg p-3 text-sm text-left mb-4 space-y-1">
                    <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold">{{
                        formatAmount(lastSaleTotal) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Method</span><span
                            class="capitalize">{{ lastPaymentMethod }}</span></div>
                    <div class="flex justify-between text-xs"><span class="text-gray-400">Receipt</span><span
                            class="text-emerald-600 truncate ml-2">{{ lastReceiptToken?.slice(0, 18) }}...</span></div>
                </div>
                <div class="flex gap-2">
                    <button @click="viewReceipt"
                        class="flex-1 border border-emerald-500 text-emerald-600 py-2 rounded-lg text-sm font-medium hover:bg-emerald-50">
                        View Receipt
                    </button>
                    <button @click="showSuccessModal = false"
                        class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">
                        New Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- Decline Modal -->
        <div v-if="showDeclineModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center">
                <div class="text-6xl mb-4">❌</div>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Payment Declined</h2>
                <p class="text-gray-500 text-sm mb-6">{{ declineMessage }}</p>
                <button @click="showDeclineModal = false"
                    class="w-full bg-red-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-red-600">
                    Try Again
                </button>
            </div>
        </div>

        <!-- Register Product Modal -->
        <div v-if="showRegisterModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Register New Product</h2>
                <div class="space-y-3">
                    <input v-model="newProduct.barcode" type="text" placeholder="Barcode" readonly
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                    <input v-model="newProduct.name" type="text" placeholder="Product name *"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                    <input v-model="newProduct.price" type="number" placeholder="Price (RM) *" min="0" step="0.01"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                    <input v-model="newProduct.stock_qty" type="number" placeholder="Stock quantity *" min="0"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="showRegisterModal = false"
                        class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm">Cancel</button>
                    <button @click="registerProduct"
                        class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Register</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../stores/cart';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

const cart = useCartStore();
const router = useRouter();
const barcodeInput = ref('');
const scanMessage = ref('');
const scanSuccess = ref(false);
const cameraActive = ref(false);
const discountType = ref('fixed');
const discountValue = ref('');
const paymentMethod = ref('cash');
const testCardNumber = ref('');
const processing = ref(false);
const selectedCurrency = ref('MYR');
const currencies = ['MYR', 'USD', 'SGD', 'IDR'];
const rates = ref(null);

// Modals
const showSuccessModal = ref(false);
const showDeclineModal = ref(false);
const showRegisterModal = ref(false);
const declineMessage = ref('');
const lastSaleTotal = ref(0);
const lastPaymentMethod = ref('');
const lastReceiptToken = ref('');
const newProduct = ref({ barcode: '', name: '', price: '', stock_qty: '' });

let html5QrCode = null;

// Format amount with currency conversion
const convertedTotal = computed(() => {
    if (!rates.value || selectedCurrency.value === 'MYR') return null;
    return (cart.total * rates.value[selectedCurrency.value]).toFixed(2);
});

function formatAmount(amount) {
    return `RM ${parseFloat(amount || 0).toFixed(2)}`;
}

// Fetch live currency rates
async function fetchRates() {
    try {
        const res = await axios.get('/api/currency/rates');
        rates.value = res.data.rates;
    } catch (e) {
        console.error('Could not fetch currency rates');
    }
}

// Barcode search
// Barcode search
async function searchBarcode() {
    const code = barcodeInput.value.trim();
    if (!code) return;

    try {
        const res = await axios.get(`/api/products/barcode/${code}`);
        if (res.data.found) {
            const p = res.data.product;
            cart.addItem({ id: p.id, name: p.name, barcode: p.barcode, price: p.price });
            scanMessage.value = `✓ ${p.name} added to cart`;
            scanSuccess.value = true;
            playBeep(); // Beep on success
        }
    } catch (err) {
        if (err.response?.status === 404) {
            // Product not found - open the register modal
            newProduct.value = { barcode: code, name: '', price: '', stock_qty: '' };
            showRegisterModal.value = true;
        } else {
            scanMessage.value = 'Invalid barcode format.';
            scanSuccess.value = false;
        }
    }

    barcodeInput.value = '';
    setTimeout(() => { scanMessage.value = ''; }, 3000);
}

// Camera scanner
async function toggleCamera() {
    if (cameraActive.value) {
        try { await html5QrCode?.stop(); } catch { }
        cameraActive.value = false;
        return;
    }

    cameraActive.value = true;
    await new Promise(r => setTimeout(r, 200));

    html5QrCode = new Html5Qrcode('qr-reader');

    const config = {
        fps: 15,
        qrbox: { width: 300, height: 150 },
        formatsToSupport: [
            0,  // QR_CODE
            4,  // EAN_13
            5,  // EAN_8
            6,  // CODE_39
            7,  // CODE_93
            8,  // CODE_128
            10, // UPC_A
            11, // UPC_E
        ],
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true,
        },
    };
    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            config,
            async (decodedText) => {
                if (barcodeInput.value === decodedText) return; // debounce same scan
                barcodeInput.value = decodedText;
                await searchBarcode();
            },
            () => { }
        );
    } catch (err) {
        // Fallback to any available camera if rear not found
        const devices = await Html5Qrcode.getCameras();
        if (devices.length > 0) {
            await html5QrCode.start(
                devices[0].id,
                config,
                async (decodedText) => {
                    if (barcodeInput.value === decodedText) return;
                    barcodeInput.value = decodedText;
                    await searchBarcode();
                },
                () => { }
            );
        }
    }
}

// Beep sound on scan
function playBeep() {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    osc.connect(ctx.destination);
    osc.frequency.value = 880;
    osc.start();
    osc.stop(ctx.currentTime + 0.1);
}

// Hold cart
function holdCurrentCart() {
    const label = prompt('Label for this held cart? (optional)') || '';
    cart.holdCart(label);
}

// Register new product
async function registerProduct() {
    try {
        const res = await axios.post('/api/products', {
            barcode: newProduct.value.barcode,
            name: newProduct.value.name,
            price: newProduct.value.price,
            stock_qty: newProduct.value.stock_qty,
        });
        cart.addItem({ id: res.data.id, name: res.data.name, barcode: res.data.barcode, price: res.data.price });
        showRegisterModal.value = false;
        scanMessage.value = `✓ ${res.data.name} registered and added to cart`;
        scanSuccess.value = true;
    } catch (e) {
        alert('Failed to register product. Check all fields.');
    }
}

// Checkout
async function processCheckout() {
    if (cart.items.length === 0 || processing.value) return;
    processing.value = true;

    try {
        // If simulation, hit payment API first
        if (paymentMethod.value === 'simulation') {
            const payRes = await axios.post('/api/payment/process', {
                card_number: testCardNumber.value,
                amount: cart.total,
                currency: selectedCurrency.value,
            });

            if (payRes.data.status !== 'approved') {
                showDeclineModal.value = true;
                declineMessage.value = payRes.data.message;
                processing.value = false;
                return;
            }
        }

        // Submit sale
        const saleRes = await axios.post('/api/sales', {
            items: cart.items.map(i => ({
                product_id: i.id,
                quantity: i.quantity,
            })),
            discount_amount: cart.discountAmount,
            discount_type: discountType.value,
            tax_amount: cart.taxAmount,
            payment_method: paymentMethod.value,
            currency: selectedCurrency.value,
        });

        lastSaleTotal.value = saleRes.data.total_amount;
        lastPaymentMethod.value = paymentMethod.value;
        lastReceiptToken.value = saleRes.data.receipt_token;

        cart.clearCart();
        discountValue.value = '';
        showSuccessModal.value = true;

    } catch (err) {
        if (err.response?.status === 402) {
            showDeclineModal.value = true;
            declineMessage.value = err.response.data.message;
        } else {
            alert(err.response?.data?.message || 'Checkout failed. Please try again.');
        }
    } finally {
        processing.value = false;
    }
}

function viewReceipt() {
    showSuccessModal.value = false;
    router.push(`/receipt/${lastReceiptToken.value}`);
}

onMounted(fetchRates);
onUnmounted(async () => {
    if (html5QrCode) await html5QrCode.stop().catch(() => { });
});
</script>