<template>
  <MainLayout page-title="Catégories" page-description="Gérer les catégories de produits">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/categories/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvelle catégorie
      </Button>
    </template>

    <!-- Search -->
    <Card class="mb-6">
      <SearchInput
        v-model="filters.search"
        placeholder="Rechercher une catégorie..."
        @update:modelValue="applyFilters"
      />
    </Card>

    <!-- Table -->
    <Card>
      <Table
        :columns="columns"
        :data="categories.data"
      >
        <template #cell-products_count="{ value }">
          <Badge>{{ value }} produits</Badge>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/categories/${row.id}/edit`)">
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
        v-if="categories.data.length === 0"
        title="Aucune catégorie"
        description="Commencez par créer votre première catégorie"
        action-label="Créer une catégorie"
        @action="$inertia.visit('/categories/create')"
      />

      <!-- Pagination -->
      <div class="mt-4" v-if="categories.data.length > 0">
        <Pagination
          :current-page="categories.current_page"
          :total-pages="categories.last_page"
          :total="categories.total"
          :per-page="categories.per_page"
          @page-change="goToPage"
        />
      </div>
    </Card>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-model="showDeleteModal"
      title="Supprimer la catégorie"
      :message="`Êtes-vous sûr de vouloir supprimer ${categoryToDelete?.name} ?`"
      confirm-text="Supprimer"
      variant="danger"
      @confirm="deleteCategory"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, EmptyState, ConfirmDialog } from '@/Components';

const props = defineProps({
  categories: Object,
  filters: Object,
});

const showDeleteModal = ref(false);
const categoryToDelete = ref(null);

const filters = reactive({
  search: props.filters?.search || '',
});

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'description', label: 'Description' },
  { key: 'products_count', label: 'Produits' },
  { key: 'actions', label: 'Actions' },
];

const applyFilters = () => {
  router.get('/categories', filters, { preserveState: true });
};

const goToPage = (page) => {
  router.get('/categories', { ...filters, page }, { preserveState: true });
};

const confirmDelete = (category) => {
  categoryToDelete.value = category;
  showDeleteModal.value = true;
};

const deleteCategory = () => {
  router.delete(`/categories/${categoryToDelete.value.id}`);
  showDeleteModal.value = false;
};
</script>
