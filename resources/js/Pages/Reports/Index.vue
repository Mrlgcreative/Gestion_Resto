<template>
  <MainLayout page-title="Rapports" page-description="Tableau de bord et statistiques">
    <!-- Period Filter -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-48">
          <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
          <Input v-model="filters.start_date" type="date" @change="applyFilters" />
        </div>
        <div class="w-48">
          <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
          <Input v-model="filters.end_date" type="date" @change="applyFilters" />
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" size="sm" @click="setQuickPeriod('today')">Aujourd'hui</Button>
          <Button variant="secondary" size="sm" @click="setQuickPeriod('week')">Cette semaine</Button>
          <Button variant="secondary" size="sm" @click="setQuickPeriod('month')">Ce mois</Button>
        </div>
      </div>
    </Card>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-4">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="py-2 px-4 border-b-2 font-medium text-sm transition-colors"
            :class="activeTab === tab.id 
              ? 'border-primary-500 text-primary-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Sales Report -->
    <div v-if="activeTab === 'sales'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Chiffre d'affaires" :value="salesData.total_revenue" format="currency" variant="success" />
        <StatCard label="Nombre de commandes" :value="salesData.total_orders" format="number" />
        <StatCard label="Panier moyen" :value="salesData.average_order" format="currency" />
        <StatCard label="Commandes annulées" :value="salesData.canceled_orders" format="number" variant="danger" />
      </div>

      <Card title="Ventes par jour">
        <div class="h-72">
          <SalesChart :data="salesData.daily_sales || []" :show-orders="true" />
        </div>
      </Card>

      <Card title="Détails des ventes">
        <Table :columns="salesColumns" :data="salesData.daily_sales || []">
          <template #cell-revenue="{ value }">
            {{ formatPrice(value) }}
          </template>
          <template #cell-average="{ value }">
            {{ formatPrice(value) }}
          </template>
        </Table>
      </Card>
    </div>

    <!-- Products Report -->
    <div v-if="activeTab === 'products'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Produits vendus" :value="productsData.total_items_sold" format="number" />
        <StatCard label="Produits différents" :value="productsData.unique_products" format="number" />
        <StatCard label="Meilleur vendeur" :value="productsData.top_product?.name || '-'" format="text" variant="success" />
        <StatCard label="Revenu produits" :value="productsData.total_revenue" format="currency" />
      </div>

      <Card title="Palmarès des produits">
        <Table :columns="productColumns" :data="productsData.top_products || []">
          <template #cell-product="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden">
                <img v-if="row.image" :src="`/storage/${row.image}`" class="w-full h-full object-cover" />
              </div>
              <span class="font-medium">{{ row.name }}</span>
            </div>
          </template>
          <template #cell-revenue="{ value }">
            {{ formatPrice(value) }}
          </template>
        </Table>
      </Card>
    </div>

    <!-- Servers Report -->
    <div v-if="activeTab === 'servers'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Serveurs actifs" :value="serversData.active_servers" format="number" />
        <StatCard label="Total commandes" :value="serversData.total_orders" format="number" />
        <StatCard label="Meilleur serveur" :value="serversData.top_server?.name || '-'" format="text" variant="success" />
        <StatCard label="Chiffre moyen/serveur" :value="serversData.average_revenue" format="currency" />
      </div>

      <Card title="Performance des serveurs">
        <Table :columns="serverColumns" :data="serversData.servers || []">
          <template #cell-revenue="{ value }">
            {{ formatPrice(value) }}
          </template>
          <template #cell-average="{ value }">
            {{ formatPrice(value) }}
          </template>
        </Table>
      </Card>
    </div>

    <!-- Sessions Report -->
    <div v-if="activeTab === 'sessions'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Sessions ouvertes" :value="sessionsData.total_sessions" format="number" />
        <StatCard label="Sessions fermées" :value="sessionsData.closed_sessions" format="number" />
        <StatCard label="Total encaissé" :value="sessionsData.total_collected" format="currency" variant="success" />
        <StatCard label="Écart total" :value="sessionsData.total_difference" format="currency" :variant="sessionsData.total_difference >= 0 ? 'success' : 'danger'" />
      </div>

      <Card title="Historique des sessions">
        <Table :columns="sessionColumns" :data="sessionsData.sessions || []">
          <template #cell-user="{ row }">
            {{ row.user?.name }}
          </template>
          <template #cell-opening_amount="{ value }">
            {{ formatPrice(value) }}
          </template>
          <template #cell-closing_amount="{ value }">
            {{ value ? formatPrice(value) : '-' }}
          </template>
          <template #cell-difference="{ row }">
            <span v-if="row.closing_amount" :class="row.closing_amount - row.expected_amount >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatPrice(row.closing_amount - row.expected_amount) }}
            </span>
            <span v-else>-</span>
          </template>
        </Table>
      </Card>
    </div>

    <!-- Stocks Report -->
    <div v-if="activeTab === 'stocks'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Total ingrédients" :value="stocksData.total_ingredients" format="number" />
        <StatCard label="En alerte" :value="stocksData.low_stock_count" format="number" variant="danger" />
        <StatCard label="Valeur du stock" :value="stocksData.total_value" format="currency" />
        <StatCard label="Mouvements" :value="stocksData.movements_count" format="number" />
      </div>

      <Card title="Alertes de stock">
        <div v-if="stocksData.low_stock?.length > 0" class="space-y-2">
          <div 
            v-for="item in stocksData.low_stock" 
            :key="item.id"
            class="flex items-center justify-between p-3 bg-red-50 rounded-lg"
          >
            <div>
              <p class="font-medium text-red-900">{{ item.name }}</p>
              <p class="text-sm text-red-600">Quantité: {{ item.quantity }} {{ item.unit }}</p>
            </div>
            <Badge variant="danger">
              Seuil: {{ item.alert_level }} {{ item.unit }}
            </Badge>
          </div>
        </div>
        <EmptyState v-else title="Aucune alerte" description="Tous les stocks sont à un niveau suffisant" />
      </Card>

      <Card title="Mouvements récents">
        <Table :columns="movementColumns" :data="stocksData.recent_movements || []">
          <template #cell-type="{ value }">
            <Badge :variant="value === 'in' ? 'success' : 'warning'">
              {{ value === 'in' ? 'Entrée' : 'Sortie' }}
            </Badge>
          </template>
          <template #cell-ingredient="{ row }">
            {{ row.ingredient?.name }}
          </template>
          <template #cell-created_at="{ value }">
            {{ formatDate(value) }}
          </template>
        </Table>
      </Card>
    </div>

    <!-- Activity Report -->
    <div v-if="activeTab === 'activity'" class="space-y-6">
      <Card title="Journal d'activité">
        <div class="space-y-3">
          <div 
            v-for="log in activityData.logs || []" 
            :key="log.id"
            class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <div :class="getActivityIconClass(log.action)" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
              <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-900">{{ log.action_label }}</span>
                <Badge :variant="getActivityVariant(log.action)" size="sm">
                  {{ getActivityCategory(log.action) }}
                </Badge>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ log.description }}</p>
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ log.user?.name || 'Système' }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ formatDateTime(log.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <EmptyState v-if="!activityData.logs?.length" title="Aucune activité" description="Aucune activité enregistrée pour cette période" />
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Table, Badge, StatCard, EmptyState, SalesChart } from '@/Components';

const props = defineProps({
  filters: Object,
  salesData: { type: Object, default: () => ({}) },
  productsData: { type: Object, default: () => ({}) },
  serversData: { type: Object, default: () => ({}) },
  sessionsData: { type: Object, default: () => ({}) },
  stocksData: { type: Object, default: () => ({}) },
  activityData: { type: Object, default: () => ({}) },
});

const activeTab = ref('sales');

const filters = reactive({
  start_date: props.filters?.start_date || new Date().toISOString().split('T')[0],
  end_date: props.filters?.end_date || new Date().toISOString().split('T')[0],
});

const tabs = [
  { id: 'sales', label: 'Ventes' },
  { id: 'products', label: 'Produits' },
  { id: 'servers', label: 'Serveurs' },
  { id: 'sessions', label: 'Sessions' },
  { id: 'stocks', label: 'Stocks' },
  { id: 'activity', label: 'Activité' },
];

const salesColumns = [
  { key: 'date', label: 'Date' },
  { key: 'orders_count', label: 'Commandes' },
  { key: 'revenue', label: 'Chiffre d\'affaires' },
  { key: 'average', label: 'Panier moyen' },
];

const productColumns = [
  { key: 'product', label: 'Produit' },
  { key: 'quantity_sold', label: 'Quantité' },
  { key: 'revenue', label: 'Revenu' },
];

const serverColumns = [
  { key: 'name', label: 'Serveur' },
  { key: 'orders_count', label: 'Commandes' },
  { key: 'revenue', label: 'Chiffre d\'affaires' },
  { key: 'average', label: 'Panier moyen' },
];

const sessionColumns = [
  { key: 'id', label: '#' },
  { key: 'user', label: 'Caissier' },
  { key: 'opening_amount', label: 'Ouverture' },
  { key: 'closing_amount', label: 'Clôture' },
  { key: 'difference', label: 'Écart' },
];

const movementColumns = [
  { key: 'ingredient', label: 'Ingrédient' },
  { key: 'type', label: 'Type' },
  { key: 'quantity', label: 'Quantité' },
  { key: 'reason', label: 'Raison' },
  { key: 'created_at', label: 'Date' },
];

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'USD',
  }).format(price || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Activity log helpers
const getActivityIconClass = (action) => {
  if (action.includes('login') || action.includes('logout')) {
    return 'bg-blue-100 text-blue-600';
  }
  if (action.includes('created') || action.includes('_created')) {
    return 'bg-green-100 text-green-600';
  }
  if (action.includes('deleted') || action.includes('canceled') || action.includes('_deleted')) {
    return 'bg-red-100 text-red-600';
  }
  if (action.includes('updated') || action.includes('_updated')) {
    return 'bg-yellow-100 text-yellow-600';
  }
  if (action.includes('paid') || action.includes('session_closed')) {
    return 'bg-emerald-100 text-emerald-600';
  }
  if (action.includes('stock')) {
    return 'bg-purple-100 text-purple-600';
  }
  return 'bg-gray-100 text-gray-600';
};

const getActivityIcon = (action) => {
  // Return SVG icon based on action
  return 'svg';
};

const getActivityVariant = (action) => {
  if (action.includes('created') || action.includes('_created') || action.includes('paid')) {
    return 'success';
  }
  if (action.includes('deleted') || action.includes('canceled') || action.includes('_deleted')) {
    return 'danger';
  }
  if (action.includes('updated') || action.includes('_updated')) {
    return 'warning';
  }
  if (action.includes('login') || action.includes('logout')) {
    return 'info';
  }
  return 'default';
};

const getActivityCategory = (action) => {
  if (action.includes('login') || action.includes('logout')) {
    return 'Authentification';
  }
  if (action.includes('order')) {
    return 'Commande';
  }
  if (action.includes('session')) {
    return 'Caisse';
  }
  if (action.includes('stock')) {
    return 'Stock';
  }
  if (action.includes('product')) {
    return 'Produit';
  }
  if (action.includes('user')) {
    return 'Utilisateur';
  }
  if (action.includes('settings') || action.includes('options')) {
    return 'Paramètres';
  }
  if (action.includes('currency') || action.includes('exchange')) {
    return 'Devise';
  }
  if (action.includes('created')) {
    return 'Création';
  }
  if (action.includes('updated')) {
    return 'Modification';
  }
  if (action.includes('deleted')) {
    return 'Suppression';
  }
  return 'Système';
};

const applyFilters = () => {
  router.get('/reports', filters, { preserveState: true });
};

const setQuickPeriod = (period) => {
  const today = new Date();
  
  if (period === 'today') {
    filters.start_date = today.toISOString().split('T')[0];
    filters.end_date = today.toISOString().split('T')[0];
  } else if (period === 'week') {
    const firstDay = new Date(today.setDate(today.getDate() - today.getDay() + 1));
    filters.start_date = firstDay.toISOString().split('T')[0];
    filters.end_date = new Date().toISOString().split('T')[0];
  } else if (period === 'month') {
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    filters.start_date = firstDay.toISOString().split('T')[0];
    filters.end_date = new Date().toISOString().split('T')[0];
  }
  
  applyFilters();
};
</script>
