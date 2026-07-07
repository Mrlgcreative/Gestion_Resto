<template>
  <MainLayout page-title="Commandes" page-description="Gérer toutes les commandes">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <Input
            v-model="filters.search"
            placeholder="Rechercher (# commande, serveur...)"
            @input="debounceSearch"
          />
        </div>
        <div class="w-48">
          <Select
            v-model="filters.status"
            :options="statusOptions"
            placeholder="Tous les statuts"
            @update:modelValue="applyFilters"
          />
        </div>
        <div class="w-48">
          <Select
            v-model="filters.server_id"
            :options="serverOptions"
            placeholder="Tous les serveurs"
            @update:modelValue="applyFilters"
          />
        </div>
        <div class="w-48">
          <Input
            v-model="filters.date"
            type="date"
            @change="applyFilters"
          />
        </div>
        <div class="flex items-end">
          <Button variant="primary" @click="$inertia.visit('/orders/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouvelle commande
          </Button>
        </div>
      </div>
    </div>

    <!-- Orders List -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <Table :columns="columns" :data="orders.data">
        <template #cell-id="{ value }">
          <span class="font-mono font-semibold">#{{ value }}</span>
        </template>
        <template #cell-server="{ row }">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <span class="text-blue-600 font-semibold text-sm">{{ row.server?.name?.[0] }}</span>
            </div>
            <span>{{ row.server?.name }}</span>
          </div>
        </template>
        <template #cell-items_count="{ value }">
          <Badge variant="default">{{ value }} articles</Badge>
        </template>
        <template #cell-total_amount="{ row }">
          <span class="font-semibold">{{ formatPrice(calculateOrderTotal(row), getOrderCurrency(row)) }}</span>
        </template>
        <template #cell-status="{ value }">
          <Badge :variant="getStatusVariant(value)">{{ getStatusLabel(value) }}</Badge>
        </template>
        <template #cell-created_at="{ value }">
          {{ formatDate(value) }}
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/orders/${row.id}`)">Voir</Button>
            <Button variant="ghost" size="sm" @click="showInvoice(row)" title="Imprimer la facture">
              <PrinterIcon class="w-4 h-4" />
            </Button>
            <Button v-if="row.status === 'pending'" variant="success" size="sm" @click="payOrder(row)">Payer</Button>
            <Button v-if="row.status === 'pending'" variant="ghost" size="sm" @click="cancelOrder(row)">Annuler</Button>
          </div>
        </template>
      </Table>
      <EmptyState v-if="orders.data.length === 0" title="Aucune commande" description="Aucune commande ne correspond à vos critères" />
    </div>

    <div class="mt-6" v-if="orders.data.length > 0">
      <Pagination
        :current-page="orders.current_page"
        :total-pages="orders.last_page"
        :total="orders.total"
        :per-page="orders.per_page"
        @page-change="goToPage"
      />
    </div>

    <!-- Invoice Modal -->
    <Modal v-model="showInvoiceModal" title="Reçu" size="sm">
      <Receipt
        v-if="selectedOrderForInvoice"
        :order="selectedOrderForInvoice"
        :settings="appSettings"
        :exchange-rates="props.exchangeRates"
        :default-currency="selectedOrderForInvoice.currency_relation?.code || selectedOrderForInvoice.currency || 'USD'"
      />
    </Modal>

    <!-- Pay Modal -->
    <Modal v-model="showPayModal" title="Paiement de la commande">
      <div v-if="selectedOrder" class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Montant à payer</p>
          <p class="text-2xl font-bold text-blue-600">{{ formatPrice(selectedOrderTotal, getOrderCurrency(selectedOrder)) }}</p>
          <div v-if="paymentEquivalent" class="mt-2 pt-2 border-t border-gray-200">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Équivalent {{ paymentEquivalent.code }} :</span>
              <span class="font-medium text-gray-700">{{ paymentEquivalent.formatted }}</span>
            </div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Montant reçu</label>
          <div class="bg-gray-100 border border-gray-300 rounded-lg px-4 py-3 text-lg font-semibold text-gray-800">
            {{ formatPrice(selectedOrderTotal, getOrderCurrency(selectedOrder)) }}
          </div>
        </div>
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showPayModal = false">Annuler</Button>
          <Button type="button" variant="success" :loading="payForm.processing" @click="confirmPayment">
            Confirmer le paiement
          </Button>
        </div>
      </div>
    </Modal>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Select, Input, Pagination, EmptyState, Modal, Receipt } from '@/Components';
import { PlusIcon, PrinterIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  orders: Object,
  servers: Array,
  filters: Object,
  settings: Object,
  exchangeRates: Array,
});

const showInvoiceModal = ref(false);
const selectedOrderForInvoice = ref(null);
const appSettings = computed(() => props.settings || usePage().props.app);

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  server_id: props.filters?.server_id || '',
  date: props.filters?.date || '',
});

const showPayModal = ref(false);
const selectedOrder = ref(null);
const payForm = useForm({
  amount_received: '',
});

const statusOptions = [
  { value: 'pending', label: 'En attente' },
  { value: 'paid', label: 'Payées' },
  { value: 'canceled', label: 'Annulées' },
];

const serverOptions = props.servers?.map(s => ({
  value: s.id,
  label: s.name,
})) || [];

const columns = [
  { key: 'id', label: '#' },
  { key: 'server', label: 'Serveur' },
  { key: 'items_count', label: 'Articles' },
  { key: 'total_amount', label: 'Total' },
  { key: 'status', label: 'Statut' },
  { key: 'created_at', label: 'Date' },
  { key: 'actions', label: '' },
];

const change = computed(() => {
  if (!selectedOrder.value || !payForm.amount_received) return 0;
  return Math.max(0, parseFloat(payForm.amount_received) - selectedOrder.value.total_amount);
});

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  router.get('/orders', filters, { preserveState: true });
};

const goToPage = (page) => {
  router.get('/orders', { ...filters, page }, { preserveState: true });
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getOrderCurrency = (order) => {
  return order.currency_relation?.code || order.currency || 'USD';
};

const getProductCurrency = (product) => {
  return product?.currency?.code || 'USD';
};

const calculateOrderTotal = (order) => {
  if (!order.items || order.items.length === 0) return order.total_amount || 0;
  const orderCurrency = getOrderCurrency(order);
  return order.items.reduce((sum, item) => {
    const itemTotal = item.unit_price * item.quantity;
    const productCurrency = getProductCurrency(item.product);
    if (productCurrency === orderCurrency) {
      return sum + itemTotal;
    }
    const fromRate = getExchangeRate(productCurrency);
    const toRate = getExchangeRate(orderCurrency);
    const amountInUSD = itemTotal / fromRate;
    return sum + (amountInUSD * toRate);
  }, 0);
};

const formatPrice = (price, currency = 'USD') => {
  const currencyCode = currency || 'USD';
  try {
    if (currencyCode === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currencyCode,
    }).format(price || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currencyCode}`;
  }
};

const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

const selectedOrderTotal = computed(() => {
  if (!selectedOrder.value) return 0;
  return calculateOrderTotal(selectedOrder.value);
});

const paymentEquivalent = computed(() => {
  if (!selectedOrder.value) return null;
  const orderCurrency = getOrderCurrency(selectedOrder.value);
  const targetCode = orderCurrency === 'CDF' ? 'USD' : 'CDF';
  const amount = convertCurrency(selectedOrderTotal.value, orderCurrency, targetCode);
  let formatted;
  if (targetCode === 'CDF') {
    formatted = new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FC';
  } else {
    try {
      formatted = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: targetCode,
      }).format(amount);
    } catch (e) {
      formatted = amount.toFixed(2) + ' ' + targetCode;
    }
  }
  return { code: targetCode, amount, formatted };
});

const getStatusVariant = (status) => {
  const variants = { pending: 'warning', paid: 'success', canceled: 'danger' };
  return variants[status] || 'default';
};

const getStatusLabel = (status) => {
  const labels = { pending: 'En attente', paid: 'Payée', canceled: 'Annulée' };
  return labels[status] || status;
};

const payOrder = (order) => {
  selectedOrder.value = order;
  payForm.amount_received = calculateOrderTotal(order);
  showPayModal.value = true;
};

const confirmPayment = () => {
  payForm.post(`/orders/${selectedOrder.value.id}/pay`, {
    onSuccess: () => {
      showPayModal.value = false;
      selectedOrder.value = null;
    },
  });
};

const cancelOrder = (order) => {
  if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
    router.post(`/orders/${order.id}/cancel`);
  }
};

const showInvoice = (order) => {
  selectedOrderForInvoice.value = order;
  showInvoiceModal.value = true;
};
</script>
