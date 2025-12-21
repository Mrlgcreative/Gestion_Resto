<template>
  <MainLayout :page-title="`Session #${session.id}`" :page-description="session.closed_at ? 'Session fermée' : 'Session en cours'">
    <template #header-actions>
      <div class="flex gap-2">
        <Button variant="secondary" @click="$inertia.visit('/sessions')">
          ← Retour
        </Button>
        <Button v-if="!session.closed_at" variant="primary" @click="$inertia.visit('/orders/create')">
          Nouvelle commande
        </Button>
        <Button v-if="!session.closed_at" variant="danger" @click="showCloseModal = true">
          Fermer la session
        </Button>
      </div>
    </template>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <StatCard
        label="Montant d'ouverture"
        :value="session.opening_amount"
        format="currency"
        :currency="session.currency"
      />
      <StatCard
        label="Total commandes"
        :value="stats.total_orders"
        format="number"
      />
      <StatCard
        label="Total ventes"
        :value="stats.total_sales"
        format="currency"
        :currency="session.currency"
        variant="success"
      />
      <StatCard
        label="En caisse (attendu)"
        :value="stats.expected_cash"
        format="currency"
        :currency="session.currency"
        variant="primary"
      />
    </div>

    <!-- Session Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <Card title="Informations" class="lg:col-span-1">
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
      </Card>

      <!-- Orders -->
      <Card title="Commandes de la session" class="lg:col-span-2">
        <Table
          :columns="orderColumns"
          :data="session.orders || []"
        >
          <template #cell-server="{ row }">
            {{ row.server?.name || '-' }}
          </template>
          <template #cell-status="{ value }">
            <Badge :variant="getStatusVariant(value)">
              {{ getStatusLabel(value) }}
            </Badge>
          </template>
          <template #cell-total_amount="{ row }">
            {{ formatOrderTotal(row) }}
          </template>
          <template #cell-actions="{ row }">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/orders/${row.id}`)">
              Voir
            </Button>
          </template>
        </Table>

        <EmptyState
          v-if="!session.orders?.length"
          title="Aucune commande"
          description="Cette session n'a pas encore de commandes"
        />
      </Card>
    </div>

    <!-- Close Session Modal -->
    <Modal :show="showCloseModal" title="Fermer la session" @close="showCloseModal = false">
      <form @submit.prevent="closeSession" class="space-y-4">
        <div class="bg-yellow-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-yellow-800">
            <strong>Attention:</strong> Assurez-vous que toutes les commandes sont finalisées avant de fermer la session.
          </p>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">
            Montant attendu en caisse: <span class="font-bold text-lg">{{ formatPrice(stats.expected_cash) }}</span>
          </p>
        </div>

        <Input
          v-model="closeForm.closing_amount"
          type="number"
          step="0.01"
          min="0"
          label="Montant réel en caisse"
          required
          :error="closeForm.errors.closing_amount"
        />

        <div v-if="closeForm.closing_amount && difference !== 0" class="p-4 rounded-lg" :class="difference > 0 ? 'bg-green-50' : 'bg-red-50'">
          <p class="text-sm" :class="difference > 0 ? 'text-green-800' : 'text-red-800'">
            {{ difference > 0 ? 'Excédent' : 'Manque' }}: <strong>{{ formatPrice(Math.abs(difference)) }}</strong>
          </p>
        </div>

        <Textarea
          v-model="closeForm.notes"
          label="Notes de clôture (optionnel)"
          rows="3"
        />

        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showCloseModal = false">
            Annuler
          </Button>
          <Button type="submit" variant="danger" :loading="closeForm.processing">
            Fermer la session
          </Button>
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

const props = defineProps({
  session: Object,
  stats: Object,
  exchangeRates: Array,
});

const showCloseModal = ref(false);

const closeForm = useForm({
  closing_amount: '',
  notes: '',
});

const orderColumns = [
  { key: 'id', label: '#' },
  { key: 'server', label: 'Serveur' },
  { key: 'total_amount', label: 'Total' },
  { key: 'status', label: 'Statut' },
  { key: 'actions', label: '' },
];

const difference = computed(() => {
  if (!closeForm.closing_amount) return 0;
  return parseFloat(closeForm.closing_amount) - props.stats.expected_cash;
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

// Obtenir le taux de change pour une devise (par rapport à USD)
const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

// Obtenir la devise d'un produit
const getProductCurrency = (product) => {
  return product?.currency?.code || 'USD';
};

// Obtenir la devise d'une commande
const getOrderCurrency = (order) => {
  return order.currency_relation?.code || order.currency || 'USD';
};

// Calculer le total d'une commande en convertissant les articles
const calculateOrderTotal = (order) => {
  if (!order.items || order.items.length === 0) return order.total_amount || 0;
  
  const orderCurrency = getOrderCurrency(order);
  
  return order.items.reduce((sum, item) => {
    const itemTotal = item.unit_price * item.quantity;
    const productCurrency = getProductCurrency(item.product);
    
    if (productCurrency === orderCurrency) {
      return sum + itemTotal;
    }
    
    // Conversion via USD comme pivot
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
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currencyCode,
    }).format(price || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currencyCode}`;
  }
};

// Formater le total d'une commande
const formatOrderTotal = (order) => {
  const total = calculateOrderTotal(order);
  const currency = getOrderCurrency(order);
  return formatPrice(total, currency);
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

const closeSession = () => {
  closeForm.post(`/sessions/${props.session.id}/close`);
};
</script>
