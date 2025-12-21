<template>
  <MainLayout page-title="Sessions de caisse" page-description="Gérer les sessions de caisse">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/sessions/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ouvrir une session
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="w-48">
          <Select
            v-model="filters.status"
            :options="statusOptions"
            placeholder="Tous les statuts"
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

    <!-- Sessions List -->
    <div class="space-y-4">
      <Card v-for="session in sessions.data" :key="session.id" class="hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="session.closed_at ? 'bg-gray-100' : 'bg-green-100'">
              <svg class="h-6 w-6" :class="session.closed_at ? 'text-gray-600' : 'text-green-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">
                Session #{{ session.id }}
                <Badge :variant="session.closed_at ? 'default' : 'success'" class="ml-2">
                  {{ session.closed_at ? 'Fermée' : 'Ouverte' }}
                </Badge>
              </h3>
              <p class="text-sm text-gray-500">
                {{ session.user?.name }} - {{ session.currency }}
              </p>
            </div>
          </div>

          <div class="text-right">
            <p class="text-sm text-gray-500">Ouvert le</p>
            <p class="font-medium">{{ formatDate(session.opened_at) }}</p>
          </div>

          <div class="text-right">
            <p class="text-sm text-gray-500">Montant d'ouverture</p>
            <p class="font-medium">{{ formatPrice(session.opening_amount, session.currency) }}</p>
          </div>

          <div class="text-right" v-if="session.closed_at">
            <p class="text-sm text-gray-500">Montant de clôture</p>
            <p class="font-medium">{{ formatPrice(session.closing_amount, session.currency) }}</p>
          </div>

          <div class="flex gap-2">
            <Button variant="secondary" size="sm" @click="$inertia.visit(`/sessions/${session.id}`)">
              Voir détails
            </Button>
          </div>
        </div>
      </Card>
    </div>

    <EmptyState
      v-if="sessions.data.length === 0"
      title="Aucune session"
      description="Commencez par ouvrir une session de caisse"
      action-label="Ouvrir une session"
      @action="$inertia.visit('/sessions/create')"
    />

    <!-- Pagination -->
    <div class="mt-6" v-if="sessions.data.length > 0">
      <Pagination
        :current-page="sessions.current_page"
        :total-pages="sessions.last_page"
        :total="sessions.total"
        :per-page="sessions.per_page"
        @page-change="goToPage"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Select, Input, Pagination, EmptyState } from '@/Components';

const props = defineProps({
  sessions: Object,
  filters: Object,
});

const filters = reactive({
  status: props.filters?.status || '',
  date: props.filters?.date || '',
});

const statusOptions = [
  { value: 'open', label: 'Ouvertes' },
  { value: 'closed', label: 'Fermées' },
];

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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

const applyFilters = () => {
  router.get('/sessions', filters, { preserveState: true });
};

const goToPage = (page) => {
  router.get('/sessions', { ...filters, page }, { preserveState: true });
};
</script>
