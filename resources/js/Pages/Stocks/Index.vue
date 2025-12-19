<template>
  <MainLayout page-title="Stocks" page-description="Gérer les ingrédients et le stock">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/stocks/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvel ingrédient
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <SearchInput
            v-model="filters.search"
            placeholder="Rechercher un ingrédient..."
            @update:modelValue="applyFilters"
          />
        </div>
        <div>
          <Checkbox
            v-model="showLowStock"
            label="Afficher uniquement les stocks bas"
            @update:modelValue="toggleLowStock"
          />
        </div>
      </div>
    </Card>

    <!-- Table -->
    <Card>
      <Table
        :columns="columns"
        :data="ingredients.data"
      >
        <template #cell-quantity="{ row }">
          <div class="flex items-center gap-2">
            <span :class="{ 'text-red-600 font-semibold': row.quantity <= row.alert_level }">
              {{ row.quantity }} {{ row.unit }}
            </span>
            <Badge v-if="row.quantity <= row.alert_level" variant="danger">Bas</Badge>
          </div>
        </template>
        <template #cell-alert_level="{ row }">
          {{ row.alert_level }} {{ row.unit }}
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <Button variant="success" size="sm" @click="openStockModal(row, 'add')">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </Button>
            <Button variant="warning" size="sm" @click="openStockModal(row, 'remove')">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/stocks/${row.id}/movements`)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/stocks/${row.id}/edit`)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="confirmDelete(row)">
              <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </div>
        </template>
      </Table>

      <EmptyState
        v-if="ingredients.data.length === 0"
        title="Aucun ingrédient"
        description="Commencez par ajouter vos ingrédients"
        action-label="Ajouter un ingrédient"
        @action="$inertia.visit('/stocks/create')"
      />

      <!-- Pagination -->
      <div class="mt-4" v-if="ingredients.data.length > 0">
        <Pagination
          :current-page="ingredients.current_page"
          :total-pages="ingredients.last_page"
          :total="ingredients.total"
          :per-page="ingredients.per_page"
          @page-change="goToPage"
        />
      </div>
    </Card>

    <!-- Stock Movement Modal -->
    <Modal v-model="showStockModal" :title="stockAction === 'add' ? 'Ajouter du stock' : 'Retirer du stock'">
      <form @submit.prevent="submitStock" class="space-y-4">
        <p class="text-gray-600">
          {{ selectedIngredient?.name }} - Stock actuel: {{ selectedIngredient?.quantity }} {{ selectedIngredient?.unit }}
        </p>

        <Input
          v-model="stockForm.quantity"
          type="number"
          step="0.01"
          min="0.01"
          :max="stockAction === 'remove' ? selectedIngredient?.quantity : undefined"
          label="Quantité"
          required
          :error="stockForm.errors.quantity"
        />

        <Input
          v-model="stockForm.reason"
          label="Raison (optionnel)"
          :error="stockForm.errors.reason"
        />

        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="showStockModal = false">
            Annuler
          </Button>
          <Button
            type="submit"
            :variant="stockAction === 'add' ? 'success' : 'warning'"
            :loading="stockForm.processing"
          >
            {{ stockAction === 'add' ? 'Ajouter' : 'Retirer' }}
          </Button>
        </div>
      </form>
    </Modal>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-model="showDeleteModal"
      title="Supprimer l'ingrédient"
      :message="`Êtes-vous sûr de vouloir supprimer ${ingredientToDelete?.name} ?`"
      confirm-text="Supprimer"
      variant="danger"
      @confirm="deleteIngredient"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, Checkbox, Modal, Input, EmptyState, ConfirmDialog } from '@/Components';

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

const stockForm = useForm({
  quantity: '',
  reason: '',
});

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'quantity', label: 'Quantité en stock' },
  { key: 'alert_level', label: 'Seuil d\'alerte' },
  { key: 'unit', label: 'Unité' },
  { key: 'actions', label: 'Actions' },
];

const applyFilters = () => {
  router.get('/stocks', filters, { preserveState: true });
};

const toggleLowStock = () => {
  filters.low_stock = showLowStock.value ? 'true' : '';
  applyFilters();
};

const goToPage = (page) => {
  router.get('/stocks', { ...filters, page }, { preserveState: true });
};

const openStockModal = (ingredient, action) => {
  selectedIngredient.value = ingredient;
  stockAction.value = action;
  stockForm.reset();
  showStockModal.value = true;
};

const submitStock = () => {
  const url = stockAction.value === 'add'
    ? `/stocks/${selectedIngredient.value.id}/add`
    : `/stocks/${selectedIngredient.value.id}/remove`;

  stockForm.post(url, {
    onSuccess: () => {
      showStockModal.value = false;
    },
  });
};

const confirmDelete = (ingredient) => {
  ingredientToDelete.value = ingredient;
  showDeleteModal.value = true;
};

const deleteIngredient = () => {
  router.delete(`/stocks/${ingredientToDelete.value.id}`);
  showDeleteModal.value = false;
};
</script>
