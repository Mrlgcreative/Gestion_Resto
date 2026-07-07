<template>
  <MainLayout page-title="Sessions de caisse" page-description="Gérer les sessions de caisse">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-48">
          <Select v-model="filters.status" :options="statusOptions" placeholder="Tous les statuts" @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Input v-model="filters.date" type="date" @change="applyFilters" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/sessions/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Ouvrir une session
          </Button>
        </div>
      </div>
    </div>

    <!-- Sessions List -->
    <div class="space-y-4">
      <div v-for="session in sessions.data" :key="session.id" class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all p-5">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="session.closed_at ? 'bg-gray-100' : 'bg-emerald-100'">
              <CurrencyDollarIcon class="w-6 h-6" :class="session.closed_at ? 'text-gray-600' : 'text-emerald-600'" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">
                Session #{{ session.id }}
                <Badge :variant="session.closed_at ? 'default' : 'success'" class="ml-2">{{ session.closed_at ? 'Fermée' : 'Ouverte' }}</Badge>
              </h3>
              <p class="text-sm text-gray-500">{{ session.user?.name }} - {{ session.currency }}</p>
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
          <Button variant="secondary" size="sm" @click="$inertia.visit(`/sessions/${session.id}`)">
            Voir détails
          </Button>
        </div>
      </div>
    </div>

    <EmptyState v-if="sessions.data.length === 0" title="Aucune session" description="Commencez par ouvrir une session de caisse" action-label="Ouvrir une session" @action="$inertia.visit('/sessions/create')" />

    <div class="mt-6" v-if="sessions.data.length > 0">
      <Pagination :current-page="sessions.current_page" :total-pages="sessions.last_page" :total="sessions.total" :per-page="sessions.per_page" @page-change="goToPage" />
    </div>
  </MainLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Select, Input, Pagination, EmptyState } from '@/Components';
import { PlusIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline';

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
  return new Date(date).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatPrice = (price, currency = 'USD') => {
  const currencyCode = currency || 'USD';
  try {
    if (currencyCode === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currencyCode }).format(price || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currencyCode}`;
  }
};

const applyFilters = () => { router.get('/sessions', filters, { preserveState: true }); };
const goToPage = (page) => { router.get('/sessions', { ...filters, page }, { preserveState: true }); };
</script>
