<template>
  <MainLayout page-title="Stocks" page-description="Gérer les ingrédients et le stock">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <SearchInput v-model="filters.search" placeholder="Rechercher un ingrédient..." @update:modelValue="applyFilters" />
        </div>
        <div>
          <Checkbox v-model="showLowStock" label="Afficher uniquement les stocks bas" @update:modelValue="toggleLowStock" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/stocks/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouvel ingrédient
          </Button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <Table :columns="columns" :data="ingredients.data">
        <template #cell-quantity="{ row }">
          <div class="flex items-center gap-2">
            <span :class="{ 'text-red-600 font-semibold': row.quantity <= row.alert_level }">{{ row.quantity }} {{ row.unit }}</span>
            <Badge v-if="row.quantity <= row.alert_level" variant="danger">Bas</Badge>
          </div>
        </template>
        <template #cell-alert_level="{ row }">
          {{ row.alert_level }} {{ row.unit }}
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-1">
            <button @click="openStockModal(row, 'add')" class="p-1.5 rounded-lg hover:bg-emerald-50 text-emerald-600 transition-colors" title="Ajouter du stock">
              <PlusIcon class="w-4 h-4" />
            </button>
            <button @click="openStockModal(row, 'remove')" class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-600 transition-colors" title="Retirer du stock">
              <MinusIcon class="w-4 h-4" />
            </button>
            <button @click="$inertia.visit(`/stocks/${row.id}/movements`)" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Mouvements">
              <ArrowPathIcon class="w-4 h-4" />
            </button>
            <button @click="$inertia.visit(`/stocks/${row.id}/edit`)" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Modifier">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="confirmDelete(row)" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Supprimer">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </template>
      </Table>
      <EmptyState v-if="ingredients.data.length === 0" title="Aucun ingrédient" description="Commencez par ajouter vos ingrédients" action-label="Ajouter un ingrédient" @action="$inertia.visit('/stocks/create')" />
      <div class="p-4 border-t" v-if="ingredients.data.length > 0">
        <Pagination :current-page="ingredients.current_page" :total-pages="ingredients.last_page" :total="ingredients.total" :per-page="ingredients.per_page" @page-change="goToPage" />
      </div>
    </div>

    <!-- Stock Movement Modal -->
    <Modal v-model="showStockModal" :title="stockAction === 'add' ? 'Ajouter du stock' : 'Retirer du stock'">
      <form @submit.prevent="submitStock" class="space-y-4">
        <p class="text-gray-600">{{ selectedIngredient?.name }} - Stock actuel: {{ selectedIngredient?.quantity }} {{ selectedIngredient?.unit }}</p>
        <Input v-model="stockForm.quantity" type="number" step="0.01" min="0.01" :max="stockAction === 'remove' ? selectedIngredient?.quantity : undefined" label="Quantité" required :error="stockForm.errors.quantity" />
        <Input v-model="stockForm.reason" label="Raison (optionnel)" :error="stockForm.errors.reason" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showStockModal = false">Annuler</Button>
          <Button type="submit" :variant="stockAction === 'add' ? 'success' : 'warning'" :loading="stockForm.processing">
            {{ stockAction === 'add' ? 'Ajouter' : 'Retirer' }}
          </Button>
        </div>
      </form>
    </Modal>

    <ConfirmDialog v-model="showDeleteModal" title="Supprimer l'ingrédient" :message="`Êtes-vous sûr de vouloir supprimer ${ingredientToDelete?.name} ?`" confirm-text="Supprimer" variant="danger" @confirm="deleteIngredient" />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, Checkbox, Modal, Input, EmptyState, ConfirmDialog } from '@/Components';
import { PlusIcon, MinusIcon, PencilIcon, TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  ingredients: Object,
  filters: Object,
});

const showDeleteModal = ref(false);
const ingredientToDelete = ref(null);
const showStockModal = ref(false);
const selectedIngredient = ref(null);
const stockAction = ref('add');
const showLowStock = ref(props.filters?.low_stock === 'true');

const filters = reactive({
  search: props.filters?.search || '',
  low_stock: props.filters?.low_stock || '',
});

const stockForm = useForm({ quantity: '', reason: '' });

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'quantity', label: 'Quantité en stock' },
  { key: 'alert_level', label: "Seuil d'alerte" },
  { key: 'unit', label: 'Unité' },
  { key: 'actions', label: 'Actions' },
];

const applyFilters = () => { router.get('/stocks', filters, { preserveState: true }); };
const toggleLowStock = () => { filters.low_stock = showLowStock.value ? 'true' : ''; applyFilters(); };
const goToPage = (page) => { router.get('/stocks', { ...filters, page }, { preserveState: true }); };
const openStockModal = (ingredient, action) => {
  selectedIngredient.value = ingredient;
  stockAction.value = action;
  stockForm.reset();
  showStockModal.value = true;
};
const submitStock = () => {
  const url = stockAction.value === 'add' ? `/stocks/${selectedIngredient.value.id}/add` : `/stocks/${selectedIngredient.value.id}/remove`;
  stockForm.post(url, { onSuccess: () => { showStockModal.value = false; } });
};
const confirmDelete = (ingredient) => { ingredientToDelete.value = ingredient; showDeleteModal.value = true; };
const deleteIngredient = () => { router.delete(`/stocks/${ingredientToDelete.value.id}`); showDeleteModal.value = false; };
</script>
