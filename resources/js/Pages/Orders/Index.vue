<template>
  <MainLayout page-title="Commandes" page-description="Gérer toutes les commandes">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/orders/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvelle commande
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
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
      </div>
    </Card>

    <!-- Orders List -->
    <Card>
      <Table
        :columns="columns"
        :data="orders.data"
      >
        <template #cell-id="{ value }">
          <span class="font-mono font-semibold">#{{ value }}</span>
        </template>
        <template #cell-server="{ row }">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
              <span class="text-primary-600 font-semibold text-sm">
                {{ row.server?.name?.[0] }}
              </span>
            </div>
            <span>{{ row.server?.name }}</span>
          </div>
        </template>
        <template #cell-items_count="{ value }">
          <Badge variant="default">{{ value }} articles</Badge>
        </template>
        <template #cell-total_amount="{ row }">
          <span class="font-semibold">{{ formatPrice(row.total_amount, row.currency?.code) }}</span>
        </template>
        <template #cell-status="{ value }">
          <Badge :variant="getStatusVariant(value)">
            {{ getStatusLabel(value) }}
          </Badge>
        </template>
        <template #cell-created_at="{ value }">
          {{ formatDate(value) }}
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/orders/${row.id}`)">
              Voir
            </Button>
            <Button 
              variant="ghost" 
              size="sm" 
              @click="showInvoice(row)"
              title="Imprimer la facture"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </Button>
            <Button 
              v-if="row.status === 'pending'" 
              variant="success" 
              size="sm" 
              @click="payOrder(row)"
            >
              Payer
            </Button>
            <Button 
              v-if="row.status === 'pending'" 
              variant="ghost" 
              size="sm" 
              @click="cancelOrder(row)"
            >
              Annuler
            </Button>
          </div>
        </template>
      </Table>

      <EmptyState
        v-if="orders.data.length === 0"
        title="Aucune commande"
        description="Aucune commande ne correspond à vos critères"
      />
    </Card>

    <!-- Pagination -->
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
        :default-currency="selectedOrderForInvoice.currency?.code || 'USD'"
      />
    </Modal>

    <!-- Pay Modal -->
    <Modal v-model="showPayModal" title="Paiement de la commande">
      <div v-if="selectedOrder" class="space-y-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Montant à payer</p>
          <p class="text-2xl font-bold text-primary-600">
            {{ formatPrice(selectedOrder.total_amount, selectedOrder.currency?.code) }}
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
            {{ formatPrice(change, selectedOrder.currency?.code) }}
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
            :disabled="parseFloat(payForm.amount_received) < selectedOrder.total_amount"
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
import { ref, reactive, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Select, Input, Pagination, EmptyState, Modal, Receipt } from '@/Components';

const props = defineProps({
  orders: Object,
  servers: Array,
  filters: Object,
  settings: Object,
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

const formatPrice = (price, currency = 'USD') => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(price || 0);
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

const payOrder = (order) => {
  selectedOrder.value = order;
  payForm.amount_received = order.total_amount;
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
