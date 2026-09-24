<template>
  <MainLayout page-title="Produits" page-description="Gérer les produits du menu">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <SearchInput v-model="filters.search" placeholder="Rechercher un produit..." @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Select v-model="filters.category" :options="categoryOptions" placeholder="Toutes les catégories" @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Select v-model="filters.status" :options="statusOptions" placeholder="Tous les statuts" @update:modelValue="applyFilters" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/products/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouveau produit
          </Button>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="product in products.data" :key="product.id" class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all overflow-hidden">
        <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
          <img v-if="product.image" :src="`${storageUrl}/${product.image}`" :alt="product.name" class="w-full h-full object-cover" />
          <PhotoIcon v-else class="w-16 h-16 text-gray-300" />
        </div>
        <div class="p-4">
          <div class="flex items-start justify-between mb-2">
            <div class="min-w-0 flex-1">
              <h3 class="font-semibold text-gray-900 truncate">{{ product.name }}</h3>
              <p class="text-sm text-gray-500">{{ product.category?.name }}</p>
            </div>
            <Badge :variant="product.status === 'available' ? 'success' : 'danger'" class="ml-2 shrink-0">
              {{ product.status === 'available' ? 'Dispo' : 'Indispo' }}
            </Badge>
          </div>
          <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ product.description || 'Aucune description' }}</p>
          <div class="flex items-center justify-between border-t pt-4">
            <div>
              <p class="text-xs text-gray-500">Prix</p>
              <p class="font-bold text-blue-600">{{ formatPrice(product.selling_price, product.currency?.code || 'USD') }}</p>
              <p v-if="getEquivalent(product)" class="text-xs text-gray-500">
                ~ {{ formatPrice(getEquivalent(product).price, getEquivalent(product).currency) }}
              </p>
            </div>
            <div class="flex gap-1">
              <button @click="$inertia.visit(`/products/${product.id}/edit`)" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Modifier">
                <PencilIcon class="w-4 h-4" />
              </button>
              <button @click="toggleStatus(product)" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" :class="product.status === 'available' ? 'text-red-500' : 'text-emerald-500'" :title="product.status === 'available' ? 'Désactiver' : 'Activer'">
                <EyeSlashIcon v-if="product.status === 'available'" class="w-4 h-4" />
                <EyeIcon v-else class="w-4 h-4" />
              </button>
              <button @click="confirmDelete(product)" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Supprimer">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-if="products.data.length === 0" title="Aucun produit" description="Commencez par ajouter vos produits au menu" action-label="Ajouter un produit" @action="$inertia.visit('/products/create')" />

    <div class="mt-6" v-if="products.data.length > 0">
      <Pagination :current-page="products.current_page" :total-pages="products.last_page" :total="products.total" :per-page="products.per_page" @page-change="goToPage" />
    </div>

    <ConfirmDialog v-model="showDeleteModal" title="Supprimer le produit" :message="`Êtes-vous sûr de vouloir supprimer ${productToDelete?.name} ?`" confirm-text="Supprimer" variant="danger" @confirm="deleteProduct" />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Pagination, SearchInput, Select, EmptyState, ConfirmDialog } from '@/Components';
import { PlusIcon, PhotoIcon, PencilIcon, EyeIcon, EyeSlashIcon, TrashIcon } from '@heroicons/vue/24/outline';

const storageUrl = window.__STORAGE_URL__ || '/storage';

const props = defineProps({
  products: Object,
  categories: Array,
  currencies: Array,
  exchangeRates: Array,
  filters: Object,
});

const showDeleteModal = ref(false);
const productToDelete = ref(null);

const filters = reactive({
  search: props.filters?.search || '',
  category: props.filters?.category || '',
  status: props.filters?.status || '',
});

const categoryOptions = props.categories?.map(c => ({ value: c.id, label: c.name })) || [];
const statusOptions = [
  { value: 'available', label: 'Disponible' },
  { value: 'unavailable', label: 'Indisponible' },
];

const formatPrice = (price, currency = 'USD') => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currency,
  }).format(price);
};

const getEquivalent = (product) => {
  if (!props.exchangeRates || props.exchangeRates.length === 0) return null;
  const productCurrencyCode = product.currency?.code || 'USD';
  const rate = props.exchangeRates.find(r => r.currency?.code === productCurrencyCode);
  if (productCurrencyCode === 'USD') {
    const otherRate = props.exchangeRates.find(r => r.currency?.code !== 'USD');
    if (!otherRate) return null;
    return { price: product.selling_price * otherRate.rate, currency: otherRate.currency?.code };
  } else if (rate) {
    return { price: product.selling_price / rate.rate, currency: 'USD' };
  }
  return null;
};

const applyFilters = () => { router.get('/products', filters, { preserveState: true }); };
const goToPage = (page) => { router.get('/products', { ...filters, page }, { preserveState: true }); };
const toggleStatus = (product) => { router.patch(`/products/${product.id}/toggle-status`); };
const confirmDelete = (product) => { productToDelete.value = product; showDeleteModal.value = true; };
const deleteProduct = () => {
  router.delete(`/products/${productToDelete.value.id}`, {
    onSuccess: () => { showDeleteModal.value = false; productToDelete.value = null; },
    onError: () => { showDeleteModal.value = false; },
  });
};
</script>
