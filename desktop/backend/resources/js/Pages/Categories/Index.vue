<template>
  <MainLayout page-title="Catégories" page-description="Gérer les catégories de produits">
    <!-- Search + Create -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <SearchInput v-model="filters.search" placeholder="Rechercher une catégorie..." @update:modelValue="applyFilters" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/categories/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouvelle catégorie
          </Button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <Table :columns="columns" :data="categories.data">
        <template #cell-products_count="{ value }">
          <Badge>{{ value }} produits</Badge>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <button @click="$inertia.visit(`/categories/${row.id}/edit`)" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Modifier">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="confirmDelete(row)" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Supprimer">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </template>
      </Table>
      <EmptyState v-if="categories.data.length === 0" title="Aucune catégorie" description="Commencez par créer votre première catégorie" action-label="Créer une catégorie" @action="$inertia.visit('/categories/create')" />
      <div class="p-4 border-t" v-if="categories.data.length > 0">
        <Pagination :current-page="categories.current_page" :total-pages="categories.last_page" :total="categories.total" :per-page="categories.per_page" @page-change="goToPage" />
      </div>
    </div>

    <ConfirmDialog v-model="showDeleteModal" title="Supprimer la catégorie" :message="`Êtes-vous sûr de vouloir supprimer ${categoryToDelete?.name} ?`" confirm-text="Supprimer" variant="danger" @confirm="deleteCategory" />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, EmptyState, ConfirmDialog } from '@/Components';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  categories: Object,
  filters: Object,
});

const showDeleteModal = ref(false);
const categoryToDelete = ref(null);

const filters = reactive({ search: props.filters?.search || '' });

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'description', label: 'Description' },
  { key: 'products_count', label: 'Produits' },
  { key: 'actions', label: 'Actions' },
];

const applyFilters = () => { router.get('/categories', filters, { preserveState: true }); };
const goToPage = (page) => { router.get('/categories', { ...filters, page }, { preserveState: true }); };
const confirmDelete = (category) => { categoryToDelete.value = category; showDeleteModal.value = true; };
const deleteCategory = () => { router.delete(`/categories/${categoryToDelete.value.id}`); showDeleteModal.value = false; };
</script>
