<template>
  <MainLayout :page-title="`Session #${session.id}`" :page-description="session.closed_at ? 'Session fermée' : 'Session en cours'">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <StatCard label="Montant d'ouverture" :value="session.opening_amount" format="currency" :currency="session.currency" />
      <StatCard label="Total commandes" :value="stats.total_orders" format="number" />
      <StatCard label="Total ventes" :value="stats.total_sales" format="currency" :currency="session.currency" variant="success" />
      <StatCard label="En caisse (attendu)" :value="stats.expected_cash" format="currency" :currency="session.currency" variant="primary" />
    </div>

    <div class="flex gap-2 mb-6">
      <Button variant="secondary" @click="$inertia.visit('/sessions')">
        <ArrowLeftIcon class="w-4 h-4 mr-1" />
        Retour
      </Button>
      <Button v-if="!session.closed_at" variant="primary" @click="$inertia.visit('/orders/create')">
        <PlusIcon class="w-5 h-5 mr-1.5" />
        Nouvelle commande
      </Button>
      <Button v-if="!session.closed_at" variant="danger" @click="openCloseModal">Fermer la session</Button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations</h3>
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-500">Caissier</p>
            <p class="font-medium">{{ session.user?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Devise</p>
            <p class="font-medium">{{ session.currency_data?.name || session.currency }} ({{ session.currency }})</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Ouverture</p>
            <p class="font-medium">{{ formatDate(session.opened_at) }}</p>
          </div>
          <div v-if="session.closed_at">
            <p class="text-sm text-gray-500">Fermeture</p>
            <p class="font-medium">{{ formatDate(session.closed_at) }}</p>
          </div>
          <div v-if="session.notes">
            <p class="text-sm text-gray-500">Notes</p>
            <p class="font-medium">{{ session.notes }}</p>
          </div>
        </div>
      </div>

      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Commandes de la session</h3>
        <Table :columns="orderColumns" :data="session.orders || []">
          <template #cell-server="{ row }">{{ row.server?.name || '-' }}</template>
          <template #cell-status="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ getStatusLabel(value) }}</Badge>
          </template>
          <template #cell-total_amount="{ row }">{{ formatOrderTotal(row) }}</template>
          <template #cell-actions="{ row }">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/orders/${row.id}`)">Voir</Button>
          </template>
        </Table>
        <EmptyState v-if="!session.orders?.length" title="Aucune commande" description="Cette session n'a pas encore de commandes" />
      </div>
    </div>

    <!-- Close Session Modal -->
    <Modal :show="showCloseModal" title="Fermer la session" @close="showCloseModal = false">
      <form @submit.prevent="closeSession" class="space-y-4">
        <div class="bg-amber-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-amber-800">
            <strong>Attention:</strong> Assurez-vous que toutes les commandes sont finalisées avant de fermer la session.
          </p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Montant attendu en caisse: <span class="font-bold text-lg">{{ formatPrice(stats.expected_cash, session.currency) }}</span></p>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <p class="text-sm text-gray-600 mb-1">Montant de fermeture (auto-calculé)</p>
          <p class="font-bold text-xl text-blue-700">{{ formatPrice(closeForm.closing_amount, session.currency) }}</p>
        </div>
        <Textarea v-model="closeForm.notes" label="Notes de clôture (optionnel)" rows="3" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showCloseModal = false">Annuler</Button>
          <Button type="submit" variant="danger" :loading="closeForm.processing">Fermer la session</Button>
        </div>
      </form>
    </Modal>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, StatCard, Table, Badge, Modal, Input, Textarea, EmptyState } from '@/Components';
import { ArrowLeftIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  session: Object,
  stats: Object,
  exchangeRates: Array,
});

const showCloseModal = ref(false);

const closeForm = useForm({
  closing_amount: props.stats.expected_cash || 0,
  notes: '',
});

const orderColumns = [
  { key: 'id', label: '#' },
  { key: 'server', label: 'Serveur' },
  { key: 'total_amount', label: 'Total' },
  { key: 'status', label: 'Statut' },
  { key: 'actions', label: '' },
];

const openCloseModal = () => {
  closeForm.closing_amount = props.stats.expected_cash || 0;
  showCloseModal.value = true;
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

const getProductCurrency = (product) => product?.currency?.code || 'USD';
const getOrderCurrency = (order) => order.currency_relation?.code || order.currency || 'USD';

const calculateOrderTotal = (order) => {
  if (!order.items || order.items.length === 0) return order.total_amount || 0;
  const orderCurrency = getOrderCurrency(order);
  return order.items.reduce((sum, item) => {
    const itemTotal = item.unit_price * item.quantity;
    const productCurrency = getProductCurrency(item.product);
    if (productCurrency === orderCurrency) return sum + itemTotal;
    const fromRate = getExchangeRate(productCurrency);
    const toRate = getExchangeRate(orderCurrency);
    const amountInUSD = itemTotal / fromRate;
    return sum + (amountInUSD * toRate);
  }, 0);
};

const formatPrice = (price, currency = null) => {
  const currencyCode = currency || props.session.currency || 'USD';
  try {
    if (currencyCode === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currencyCode }).format(price || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currencyCode}`;
  }
};

const formatOrderTotal = (order) => {
  const total = calculateOrderTotal(order);
  const currency = getOrderCurrency(order);
  return formatPrice(total, currency);
};

const getStatusVariant = (status) => {
  const variants = { pending: 'warning', paid: 'success', canceled: 'danger' };
  return variants[status] || 'default';
};

const getStatusLabel = (status) => {
  const labels = { pending: 'En attente', paid: 'Payée', canceled: 'Annulée' };
  return labels[status] || status;
};

const closeSession = () => {
  closeForm.post(`/sessions/${props.session.id}/close`, {
    onSuccess: () => { showCloseModal.value = false; },
    onError: () => {},
  });
};
</script>
