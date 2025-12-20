<template>
  <MainLayout page-title="Produits" page-description="Gérer les produits du menu">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/products/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau produit
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <SearchInput
            v-model="filters.search"
            placeholder="Rechercher un produit..."
            @update:modelValue="applyFilters"
          />
        </div>
        <div class="w-48">
          <Select
            v-model="filters.category"
            :options="categoryOptions"
            placeholder="Toutes les catégories"
            @update:modelValue="applyFilters"
          />
        </div>
        <div class="w-48">
          <Select
            v-model="filters.status"
            :options="statusOptions"
            placeholder="Tous les statuts"
            @update:modelValue="applyFilters"
          />
        </div>
      </div>
    </Card>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <Card v-for="product in products.data" :key="product.id" class="hover:shadow-lg transition-shadow">
        <div class="aspect-square bg-gray-100 rounded-lg mb-4 flex items-center justify-center overflow-hidden">
          <img
            v-if="product.image"
            :src="`${storageUrl}/${product.image}`"
            :alt="product.name"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-4xl">🍽️</span>
        </div>

        <div class="flex items-start justify-between mb-2">
          <div>
            <h3 class="font-semibold text-gray-900">{{ product.name }}</h3>
            <p class="text-sm text-gray-500">{{ product.category?.name }}</p>
          </div>
          <Badge :variant="product.status === 'available' ? 'success' : 'danger'">
            {{ product.status === 'available' ? 'Dispo' : 'Indispo' }}
          </Badge>
        </div>

        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ product.description || 'Aucune description' }}</p>

        <div class="flex items-center justify-between border-t pt-4">
          <div>
            <p class="text-xs text-gray-500">Prix</p>
            <p class="font-bold text-primary-600">{{ formatPrice(product.selling_price, product.currency?.code || 'USD') }}</p>
            <p v-if="getEquivalent(product)" class="text-xs text-gray-500">
              ≈ {{ formatPrice(getEquivalent(product).price, getEquivalent(product).currency) }}
            </p>
          </div>
          <div class="flex gap-1">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/products/${product.id}/edit`)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="toggleStatus(product)">
              <svg class="h-4 w-4" :class="product.status === 'available' ? 'text-red-500' : 'text-green-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="confirmDelete(product)">
              <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </div>
        </div>
      </Card>
    </div>

    <EmptyState
      v-if="products.data.length === 0"
      title="Aucun produit"
      description="Commencez par ajouter vos produits au menu"
      action-label="Ajouter un produit"
      @action="$inertia.visit('/products/create')"
    />

    <!-- Pagination -->
    <div class="mt-6" v-if="products.data.length > 0">
      <Pagination
        :current-page="products.current_page"
        :total-pages="products.last_page"
        :total="products.total"
        :per-page="products.per_page"
        @page-change="goToPage"
      />
    </div>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-model="showDeleteModal"
      title="Supprimer le produit"
      :message="`Êtes-vous sûr de vouloir supprimer ${productToDelete?.name} ?`"
      confirm-text="Supprimer"
      variant="danger"
      @confirm="deleteProduct"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Pagination, SearchInput, Select, EmptyState, ConfirmDialog } from '@/Components';

// URL de base pour les images storage
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

// Calculer l'équivalent dans l'autre devise
// Le système: USD est la devise de base, exchangeRate.rate = combien de devise locale pour 1 USD
const getEquivalent = (product) => {
  if (!props.exchangeRates || props.exchangeRates.length === 0) return null;
  
  const productCurrencyCode = product.currency?.code || 'USD';
  
  // Trouver le taux de change pour la devise du produit
  const rate = props.exchangeRates.find(r => r.currency?.code === productCurrencyCode);
  
  if (productCurrencyCode === 'USD') {
    // Produit en USD - chercher un taux pour convertir vers une autre devise
    const otherRate = props.exchangeRates.find(r => r.currency?.code !== 'USD');
    if (!otherRate) return null;
    
    return {
      price: product.selling_price * otherRate.rate,
      currency: otherRate.currency?.code
    };
  } else if (rate) {
    // Produit dans une autre devise - convertir vers USD
    return {
      price: product.selling_price / rate.rate,
      currency: 'USD'
    };
  }
  
  return null;
};

const applyFilters = () => {
  router.get('/products', filters, { preserveState: true });
};

const goToPage = (page) => {
  router.get('/products', { ...filters, page }, { preserveState: true });
};

const toggleStatus = (product) => {
  router.patch(`/products/${product.id}/toggle-status`);
};

const confirmDelete = (product) => {
  productToDelete.value = product;
  showDeleteModal.value = true;
};

const deleteProduct = () => {
  router.delete(`/products/${productToDelete.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
      productToDelete.value = null;
    },
    onError: () => {
      showDeleteModal.value = false;
    },
  });
};
</script>
