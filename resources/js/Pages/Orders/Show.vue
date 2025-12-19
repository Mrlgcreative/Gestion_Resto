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
                  :src="`/storage/${row.product.image}`"
                  :alt="row.product?.name"
                  class="w-full h-full object-cover"
                />
              </div>
              <span class="font-medium">{{ row.product?.name }}</span>
            </div>
          </template>
          <template #cell-unit_price="{ value }">
            {{ formatPrice(value) }}
          </template>
          <template #cell-subtotal="{ row }">
            <span class="font-semibold">{{ formatPrice(row.unit_price * row.quantity) }}</span>
          </template>
        </Table>

        <!-- Total -->
        <div class="border-t mt-4 pt-4">
          <div class="flex justify-end">
            <div class="w-64 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">Sous-total</span>
                <span>{{ formatPrice(order.total_amount) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="text-primary-600">{{ formatPrice(order.total_amount) }}</span>
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
        :default-currency="order.currency?.code || 'USD'"
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

const props = defineProps({
  order: Object,
  settings: Object,
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

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: props.order.currency?.code || 'USD',
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
