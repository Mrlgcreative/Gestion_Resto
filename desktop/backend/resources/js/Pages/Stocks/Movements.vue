<template>
  <MainLayout page-title="Mouvements de stock" :page-description="ingredient.name">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <StatCard label="Stock actuel" :value="`${ingredient.quantity} ${ingredient.unit}`" format="text" :variant="ingredient.quantity <= ingredient.alert_level ? 'danger' : 'default'" />
      <StatCard label="Seuil d'alerte" :value="`${ingredient.alert_level} ${ingredient.unit}`" format="text" />
      <StatCard label="Statut" :value="ingredient.quantity <= ingredient.alert_level ? 'Stock bas' : 'Stock OK'" format="text" :variant="ingredient.quantity <= ingredient.alert_level ? 'danger' : 'success'" />
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Historique des mouvements</h3>
        <Button variant="secondary" @click="$inertia.visit('/stocks')">
          <ArrowLeftIcon class="w-4 h-4 mr-1" />
          Retour aux stocks
        </Button>
      </div>
      <Table :columns="columns" :data="movements.data">
        <template #cell-type="{ value }">
          <Badge :variant="value === 'in' ? 'success' : 'danger'">{{ value === 'in' ? 'Entrée' : 'Sortie' }}</Badge>
        </template>
        <template #cell-quantity="{ row }">
          <span :class="row.type === 'in' ? 'text-emerald-600' : 'text-red-600'">
            {{ row.type === 'in' ? '+' : '-' }}{{ row.quantity }} {{ ingredient.unit }}
          </span>
        </template>
        <template #cell-user="{ row }">
          {{ row.user?.name || 'Système' }}
        </template>
        <template #cell-created_at="{ value }">
          {{ formatDate(value) }}
        </template>
      </Table>
      <EmptyState v-if="movements.data.length === 0" title="Aucun mouvement" description="Aucun mouvement de stock enregistré" />
      <div class="mt-4" v-if="movements.data.length > 0">
        <Pagination :current-page="movements.current_page" :total-pages="movements.last_page" :total="movements.total" :per-page="movements.per_page" @page-change="goToPage" />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, StatCard, EmptyState } from '@/Components';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  ingredient: Object,
  movements: Object,
});

const columns = [
  { key: 'type', label: 'Type' },
  { key: 'quantity', label: 'Quantité' },
  { key: 'reason', label: 'Raison' },
  { key: 'user', label: 'Par' },
  { key: 'created_at', label: 'Date' },
];

const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const goToPage = (page) => {
  router.get(`/stocks/${props.ingredient.id}/movements`, { page }, { preserveState: true });
};
</script>
