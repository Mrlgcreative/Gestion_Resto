<template>
  <MainLayout page-title="Dashboard" :page-description="getWelcomeMessage()">
    <template #header-actions>
      <Button variant="ghost" size="sm" @click="logout">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Déconnexion
      </Button>
    </template>

    <!-- Session Status Card (Caissier) -->
    <div v-if="permissions.canOpenSession" class="mb-6">
      <Card :class="currentSession ? 'border-green-500 bg-green-50' : 'border-yellow-500 bg-yellow-50'">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <div :class="[
              'w-12 h-12 rounded-full flex items-center justify-center',
              currentSession ? 'bg-green-100' : 'bg-yellow-100'
            ]">
              <svg class="h-6 w-6" :class="currentSession ? 'text-green-600' : 'text-yellow-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <p class="font-semibold" :class="currentSession ? 'text-green-800' : 'text-yellow-800'">
                {{ currentSession ? 'Session de caisse active' : 'Aucune session active' }}
              </p>
              <p class="text-sm" :class="currentSession ? 'text-green-600' : 'text-yellow-600'">
                {{ currentSession 
                  ? `Ouverte depuis ${formatTime(currentSession.opened_at)} - ${currentSession.currency}`
                  : 'Ouvrez une session pour commencer à encaisser'
                }}
              </p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button 
              v-if="currentSession" 
              variant="success"
              @click="$inertia.visit('/orders/create')"
            >
              <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Nouvelle commande
            </Button>
            <Button 
              v-if="currentSession" 
              variant="secondary"
              @click="$inertia.visit(`/sessions/${currentSession.id}`)"
            >
              Voir la session
            </Button>
            <Button 
              v-else 
              variant="primary"
              @click="$inertia.visit('/sessions/create')"
            >
              <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Ouvrir une session
            </Button>
          </div>
        </div>
      </Card>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Ventes du jour -->
      <StatCard
        v-if="stats.todaySales !== undefined"
        label="Ventes du jour"
        :value="stats.todaySales"
        format="currency"
        :currency="stats.currency"
        variant="primary"
      >
        <template #icon>
          <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </template>
      </StatCard>

      <!-- Commandes du jour -->
      <StatCard
        v-if="stats.todayOrders !== undefined"
        label="Commandes du jour"
        :value="stats.todayOrders"
        format="number"
        variant="success"
      >
        <template #icon>
          <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </template>
      </StatCard>

      <!-- Commandes en attente -->
      <StatCard
        v-if="stats.pendingOrders !== undefined"
        label="En attente"
        :value="stats.pendingOrders"
        format="number"
        :variant="stats.pendingOrders > 0 ? 'warning' : 'default'"
      >
        <template #icon>
          <svg class="h-6 w-6" :class="stats.pendingOrders > 0 ? 'text-yellow-600' : 'text-gray-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </template>
      </StatCard>

      <!-- Produits actifs - Admin/Gérant -->
      <StatCard
        v-if="stats.activeProducts !== undefined"
        label="Produits actifs"
        :value="stats.activeProducts"
        format="number"
      >
        <template #icon>
          <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </template>
      </StatCard>

      <!-- Stocks bas - Admin/Gérant -->
      <StatCard
        v-if="stats.lowStock !== undefined"
        label="Stocks bas"
        :value="stats.lowStock"
        format="number"
        :variant="stats.lowStock > 0 ? 'danger' : 'default'"
      >
        <template #icon>
          <svg class="h-6 w-6" :class="stats.lowStock > 0 ? 'text-red-600' : 'text-gray-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </template>
      </StatCard>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Orders -->
      <Card v-if="permissions.canViewOrders" title="Commandes récentes">
        <template #actions>
          <Button variant="ghost" size="sm" @click="$inertia.visit('/orders')">Voir tout</Button>
        </template>

        <Table
          v-if="recentOrders.length > 0"
          :columns="orderColumns"
          :data="recentOrders"
        >
          <template #cell-server="{ row }">
            {{ row.server?.name || '-' }}
          </template>
          <template #cell-status="{ value }">
            <Badge :variant="getStatusVariant(value)">
              {{ getStatusLabel(value) }}
            </Badge>
          </template>
          <template #cell-total_amount="{ value }">
            {{ formatCurrency(value) }}
          </template>
        </Table>

        <EmptyState 
          v-else
          title="Aucune commande"
          description="Aucune commande récente à afficher"
        />
      </Card>

      <!-- Top Products - Admin/Gérant only -->
      <Card v-if="permissions.canViewProducts && topProducts.length > 0" title="Produits populaires">
        <div class="space-y-4">
          <div
            v-for="product in topProducts"
            :key="product.id"
            class="flex items-center gap-4"
          >
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <span class="text-lg">🍽️</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
              <p class="text-xs text-gray-500">{{ product.orders_count }} commandes</p>
            </div>
            <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(product.selling_price) }}</p>
          </div>
        </div>
      </Card>

      <!-- Quick Actions for Caissier -->
      <Card v-if="permissions.canOpenSession && !permissions.canViewProducts" title="Actions rapides">
        <div class="grid grid-cols-2 gap-4">
          <Button 
            v-if="currentSession"
            variant="primary" 
            class="h-24 flex-col"
            @click="$inertia.visit('/orders/create')"
          >
            <svg class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nouvelle commande
          </Button>
          <Button 
            variant="secondary" 
            class="h-24 flex-col"
            @click="$inertia.visit('/orders')"
          >
            <svg class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Mes commandes
          </Button>
          <Button 
            variant="secondary" 
            class="h-24 flex-col"
            @click="$inertia.visit('/sessions')"
          >
            <svg class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Mes sessions
          </Button>
        </div>
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, StatCard, Card, Table, Badge, EmptyState } from '@/Components';

const page = usePage();

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      currency: 'USD',
    }),
  },
  recentOrders: {
    type: Array,
    default: () => [],
  },
  topProducts: {
    type: Array,
    default: () => [],
  },
  currentSession: {
    type: Object,
    default: null,
  },
});

// Utiliser les permissions partagées globalement
const permissions = computed(() => page.props.permissions || {});

const orderColumns = [
  { key: 'id', label: '#' },
  { key: 'server', label: 'Serveur' },
  { key: 'total_amount', label: 'Total' },
  { key: 'status', label: 'Statut' },
];

const getWelcomeMessage = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Bonjour ! Bonne matinée';
  if (hour < 18) return 'Bon après-midi !';
  return 'Bonsoir !';
};

const logout = () => {
  router.post('/logout');
};

const formatTime = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: props.stats.currency || 'USD',
  }).format(value || 0);
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
</script>
