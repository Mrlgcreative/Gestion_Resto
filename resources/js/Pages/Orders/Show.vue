<template>
  <MainLayout :page-title="`Commande #${order.id}`" :page-description="getStatusLabel(order.status)">
    <template #header-actions>
      <div class="flex gap-2">
        <Button variant="secondary" @click="$inertia.visit('/orders')">
          ← Retour
        </Button>
        <Button 
          v-if="order.status === 'pending'" 
          variant="success" 
          @click="showPayModal = true"
        >
          Encaisser
        </Button>
        <Button 
          v-if="order.status === 'pending'" 
          variant="danger" 
          @click="cancelOrder"
        >
          Annuler
        </Button>
        <Button variant="secondary" @click="printReceipt">
          <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Imprimer
        </Button>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Order Details -->
      <Card title="Détails" class="lg:col-span-1">
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-500">Statut</p>
            <Badge :variant="getStatusVariant(order.status)" class="mt-1">
              {{ getStatusLabel(order.status) }}
            </Badge>
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
      </Card>

      <!-- Order Items -->
      <Card title="Articles" class="lg:col-span-2">
        <Table
          :columns="itemColumns"
          :data="order.items || []"
        >
          <template #cell-product="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                <img
                  v-if="row.product?.image"
                  :src="`${storageUrl}/${row.product.image}`"
                  :alt="row.product?.name"
                  class="w-full h-full object-cover"
                />
              </div>
              <span class="font-medium">{{ row.product?.name }}</span>
            </div>
          </template>
          <template #cell-unit_price="{ row }">
            <div>
              <span>{{ formatItemPrice(row.unit_price, row.product) }}</span>
              <!-- Afficher la conversion si devise différente du panier -->
              <p v-if="getProductCurrency(row.product) !== orderCurrency" class="text-xs text-primary-600">
                = {{ formatPrice(convertToCartCurrency(row.unit_price, row.product)) }}
              </p>
            </div>
          </template>
          <template #cell-subtotal="{ row }">
            <div class="text-right">
              <!-- Sous-total converti dans la devise du panier -->
              <span class="font-semibold">{{ formatPrice(convertToCartCurrency(row.unit_price * row.quantity, row.product)) }}</span>
              <!-- Afficher le prix original si devise différente -->
              <p v-if="getProductCurrency(row.product) !== orderCurrency" class="text-xs text-gray-500">
                ({{ formatItemPrice(row.unit_price * row.quantity, row.product) }})
              </p>
            </div>
          </template>
        </Table>

        <!-- Total -->
        <div class="border-t mt-4 pt-4">
          <div class="flex justify-end">
            <div class="w-64 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">Sous-total</span>
                <span>{{ formatPrice(calculatedSubtotal) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="text-primary-600">{{ formatPrice(calculatedSubtotal) }}</span>
              </div>
              <!-- Équivalent en autres devises -->
              <div v-if="equivalentCurrencies.length > 0" class="bg-gray-50 rounded-lg p-3 mt-2">
                <p class="text-xs text-gray-500 mb-2">Équivalent :</p>
                <div 
                  v-for="curr in equivalentCurrencies" 
                  :key="'equiv-' + curr.code"
                  class="flex justify-between text-sm"
                >
                  <span class="text-gray-600">{{ curr.code }}</span>
                  <span class="font-semibold text-gray-800">{{ formatPriceInCurrency(getTotalInCurrency(curr.code), curr.code) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Invoice Modal -->
    <Modal v-model="showInvoiceModal" title="Reçu" size="sm">
      <Receipt 
        :order="order" 
        :settings="appSettings"
        :exchange-rates="props.exchangeRates"
        :default-currency="order.currency_relation?.code || 'USD'"
      />
    </Modal>

    <!-- Pay Modal -->
    <Modal :show="showPayModal" title="Encaisser la commande" @close="showPayModal = false">
      <div class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Montant à payer</p>
          <p class="text-2xl font-bold text-primary-600">
            {{ formatPrice(order.total_amount) }}
          </p>
        </div>

        <Input
          v-model="payForm.amount_received"
          type="number"
          step="0.01"
          min="0"
          label="Montant reçu"
          required
          :error="payForm.errors.amount_received"
        />

        <div v-if="change > 0" class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-green-600">Monnaie à rendre</p>
          <p class="text-xl font-bold text-green-700">
            {{ formatPrice(change) }}
          </p>
        </div>

        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showPayModal = false">
            Annuler
          </Button>
          <Button 
            type="button" 
            variant="success" 
            :loading="payForm.processing"
            :disabled="parseFloat(payForm.amount_received) < order.total_amount"
            @click="confirmPayment"
          >
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

// URL de base pour les images storage
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
  amount_received: props.order.total_amount,
});

const itemColumns = [
  { key: 'product', label: 'Produit' },
  { key: 'quantity', label: 'Qté' },
  { key: 'unit_price', label: 'Prix unit.' },
  { key: 'subtotal', label: 'Sous-total' },
];

const change = computed(() => {
  if (!payForm.amount_received) return 0;
  return Math.max(0, parseFloat(payForm.amount_received) - props.order.total_amount);
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

// Devise de la commande (relation currencyRelation chargée depuis le backend)
// order.currency_relation est un objet avec .code, .name, .symbol
const orderCurrency = computed(() => props.order.currency_relation?.code || 'USD');

// Obtenir la devise d'un produit
const getProductCurrency = (product) => {
  return product?.currency?.code || 'USD';
};

// Obtenir le taux de change pour une devise (par rapport à USD)
const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

// Convertir un montant de la devise du produit vers la devise du panier
const convertToCartCurrency = (amount, product) => {
  const fromCurrency = getProductCurrency(product);
  const toCurrency = orderCurrency.value;
  
  if (fromCurrency === toCurrency) return parseFloat(amount);
  
  // Conversion via USD comme pivot
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

// Calculer le sous-total en convertissant tous les articles dans la devise du panier
const calculatedSubtotal = computed(() => {
  if (!props.order.items) return 0;
  return props.order.items.reduce((sum, item) => {
    const itemTotal = item.unit_price * item.quantity;
    return sum + convertToCartCurrency(itemTotal, item.product);
  }, 0);
});

// Formater le prix d'un article dans la devise de son produit
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

// Convertir un montant d'une devise vers une autre
const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

// Devises disponibles pour les équivalents
const equivalentCurrencies = computed(() => {
  const currencies = [{ code: 'USD', rate: 1 }];
  props.exchangeRates?.forEach(rate => {
    if (rate.currency?.code && rate.currency.code !== 'USD') {
      currencies.push({ code: rate.currency.code, rate: parseFloat(rate.rate) });
    }
  });
  return currencies.filter(c => c.code !== orderCurrency.value);
});

// Obtenir le total dans une autre devise
const getTotalInCurrency = (targetCurrency) => {
  return convertCurrency(calculatedSubtotal.value, orderCurrency.value, targetCurrency);
};

// Formater un prix dans une devise spécifique
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
  const variants = {
    pending: 'warning',
    paid: 'success',
    canceled: 'danger',
  };
  return variants[status] || 'default';
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    paid: 'Payée',
    canceled: 'Annulée',
  };
  return labels[status] || status;
};

const confirmPayment = () => {
  payForm.post(`/orders/${props.order.id}/pay`, {
    onSuccess: () => {
      showPayModal.value = false;
    },
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
