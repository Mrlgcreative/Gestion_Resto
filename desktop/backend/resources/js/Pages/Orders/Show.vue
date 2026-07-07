<template>
  <MainLayout :page-title="`Commande #${order.id}`" :page-description="getStatusLabel(order.status)">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Order Details -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails</h3>
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-500">Statut</p>
            <Badge :variant="getStatusVariant(order.status)" class="mt-1">{{ getStatusLabel(order.status) }}</Badge>
          </div>
          <div>
            <p class="text-sm text-gray-500">Serveur</p>
            <p class="font-medium">{{ order.server?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Session</p>
            <p class="font-medium">Session #{{ order.cashier_session_id }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Date</p>
            <p class="font-medium">{{ formatDate(order.created_at) }}</p>
          </div>
          <div v-if="order.paid_at">
            <p class="text-sm text-gray-500">Payé le</p>
            <p class="font-medium">{{ formatDate(order.paid_at) }}</p>
          </div>
          <div v-if="order.notes">
            <p class="text-sm text-gray-500">Notes</p>
            <p class="font-medium">{{ order.notes }}</p>
          </div>
        </div>
        <div class="flex gap-2 mt-6 pt-4 border-t">
          <Button variant="secondary" @click="$inertia.visit('/orders')">
            <ArrowLeftIcon class="w-4 h-4 mr-1" />
            Retour
          </Button>
          <Button v-if="order.status === 'pending'" variant="danger" @click="cancelOrder">Annuler</Button>
          <Button variant="secondary" @click="printReceipt">
            <PrinterIcon class="w-4 h-4 mr-1" />
            Imprimer
          </Button>
        </div>
      </div>

      <!-- Order Items -->
      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Articles</h3>
        <Table :columns="itemColumns" :data="order.items || []">
          <template #cell-product="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                <img v-if="row.product?.image" :src="`${storageUrl}/${row.product.image}`" :alt="row.product?.name" class="w-full h-full object-cover" />
              </div>
              <span class="font-medium">{{ row.product?.name }}</span>
            </div>
          </template>
          <template #cell-unit_price="{ row }">
            <div>
              <span>{{ formatItemPrice(row.unit_price, row.product) }}</span>
              <p v-if="getProductCurrency(row.product) !== orderCurrency" class="text-xs text-blue-600">
                = {{ formatPrice(convertToCartCurrency(row.unit_price, row.product)) }}
              </p>
            </div>
          </template>
          <template #cell-subtotal="{ row }">
            <div class="text-right">
              <span class="font-semibold">{{ formatPrice(convertToCartCurrency(row.unit_price * row.quantity, row.product)) }}</span>
              <p v-if="getProductCurrency(row.product) !== orderCurrency" class="text-xs text-gray-500">
                ({{ formatItemPrice(row.unit_price * row.quantity, row.product) }})
              </p>
            </div>
          </template>
        </Table>
        <div class="border-t mt-4 pt-4">
          <div class="flex justify-end">
            <div class="w-64 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">Sous-total</span>
                <span>{{ formatPrice(calculatedSubtotal) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="text-blue-600">{{ formatPrice(calculatedSubtotal) }}</span>
              </div>
              <div v-if="equivalentCurrencies.length > 0" class="bg-gray-50 rounded-lg p-3 mt-2">
                <p class="text-xs text-gray-500 mb-2">Équivalent :</p>
                <div v-for="curr in equivalentCurrencies" :key="'equiv-' + curr.code" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ curr.code }}</span>
                  <span class="font-semibold text-gray-800">{{ formatPriceInCurrency(getTotalInCurrency(curr.code), curr.code) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invoice Modal -->
    <Modal v-model="showInvoiceModal" title="Reçu" size="sm">
      <Receipt :order="order" :settings="appSettings" :exchange-rates="props.exchangeRates" :default-currency="order.currency_relation?.code || 'USD'" />
    </Modal>

    <!-- Pay Modal -->
    <Modal :show="showPayModal" title="Encaisser la commande" @close="showPayModal = false">
      <div class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Montant à payer</p>
          <p class="text-2xl font-bold text-blue-600">{{ formatPrice(calculatedSubtotal) }}</p>
        </div>
        <Input v-model="payForm.amount_received" type="number" step="0.01" min="0" label="Montant reçu" required :error="payForm.errors.amount_received" />
        <div v-if="change > 0" class="bg-emerald-50 p-4 rounded-lg">
          <p class="text-sm text-emerald-600">Monnaie à rendre</p>
          <p class="text-xl font-bold text-emerald-700">{{ formatPrice(change) }}</p>
        </div>
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showPayModal = false">Annuler</Button>
          <Button type="button" variant="success" :loading="payForm.processing" :disabled="parseFloat(payForm.amount_received || 0) < calculatedSubtotal" @click="confirmPayment">
            Confirmer le paiement
          </Button>
        </div>
      </div>
    </Modal>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Modal, Input, Receipt } from '@/Components';
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/vue/24/outline';

const storageUrl = window.__STORAGE_URL__ || '/storage';

const props = defineProps({
  order: Object,
  settings: Object,
  exchangeRates: Array,
});

const showInvoiceModal = ref(false);
const appSettings = computed(() => props.settings || usePage().props.app);

const showPayModal = ref(false);

const payForm = useForm({
  amount_received: '',
});

const itemColumns = [
  { key: 'product', label: 'Produit' },
  { key: 'quantity', label: 'Qté' },
  { key: 'unit_price', label: 'Prix unit.' },
  { key: 'subtotal', label: 'Sous-total' },
];

const change = computed(() => {
  if (!payForm.amount_received) return 0;
  return Math.max(0, parseFloat(payForm.amount_received) - calculatedSubtotal.value);
});

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const orderCurrency = computed(() => props.order.currency_relation?.code || 'USD');

const getProductCurrency = (product) => {
  return product?.currency?.code || 'USD';
};

const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

const convertToCartCurrency = (amount, product) => {
  const fromCurrency = getProductCurrency(product);
  const toCurrency = orderCurrency.value;
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

const calculatedSubtotal = computed(() => {
  if (!props.order.items) return 0;
  return props.order.items.reduce((sum, item) => {
    const itemTotal = item.unit_price * item.quantity;
    return sum + convertToCartCurrency(itemTotal, item.product);
  }, 0);
});

const formatItemPrice = (price, product) => {
  const currency = getProductCurrency(product);
  try {
    if (currency === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(parseFloat(price) || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
  }
};

const formatPrice = (price) => {
  const currency = orderCurrency.value;
  try {
    if (currency === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(parseFloat(price) || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
  }
};

const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

const equivalentCurrencies = computed(() => {
  const currencies = [{ code: 'USD', rate: 1 }];
  props.exchangeRates?.forEach(rate => {
    if (rate.currency?.code && rate.currency.code !== 'USD') {
      currencies.push({ code: rate.currency.code, rate: parseFloat(rate.rate) });
    }
  });
  return currencies.filter(c => c.code !== orderCurrency.value);
});

const getTotalInCurrency = (targetCurrency) => {
  return convertCurrency(calculatedSubtotal.value, orderCurrency.value, targetCurrency);
};

const formatPriceInCurrency = (price, currency) => {
  try {
    if (currency === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(parseFloat(price) || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
  }
};

const getStatusVariant = (status) => {
  const variants = { pending: 'warning', paid: 'success', canceled: 'danger' };
  return variants[status] || 'default';
};

const getStatusLabel = (status) => {
  const labels = { pending: 'En attente', paid: 'Payée', canceled: 'Annulée' };
  return labels[status] || status;
};

const confirmPayment = () => {
  payForm.post(`/orders/${props.order.id}/pay`, {
    onSuccess: () => { showPayModal.value = false; },
  });
};

const cancelOrder = () => {
  if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
    router.post(`/orders/${props.order.id}/cancel`);
  }
};

const printReceipt = () => {
  showInvoiceModal.value = true;
};
</script>
