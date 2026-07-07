<template>
  <MainLayout page-title="Nouveau produit" page-description="Ajouter un produit au menu">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
      <form @submit.prevent="submit" class="space-y-6">
        <Input v-model="form.name" label="Nom du produit" required :error="form.errors.name" />
        <Textarea v-model="form.description" label="Description" rows="3" :error="form.errors.description" />
        <Select v-model="form.category_id" label="Catégorie" :options="categoryOptions" required :error="form.errors.category_id" />

        <div class="grid grid-cols-2 gap-4">
          <Input v-model="form.base_price" type="number" step="0.01" min="0" label="Prix de base" required :error="form.errors.base_price" />
          <Input v-model="form.selling_price" type="number" step="0.01" min="0" label="Prix de vente" required :error="form.errors.selling_price" />
        </div>

        <Select v-model="form.currency_id" label="Devise" :options="currencyOptions" required :error="form.errors.currency_id" />

        <div v-if="form.base_price && form.selling_price" class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">
            Marge: <span class="font-semibold" :class="profit >= 0 ? 'text-emerald-600' : 'text-red-600'">{{ formatPrice(profit) }} ({{ profitPercent.toFixed(1) }}%)</span>
          </p>
        </div>

        <FileUpload v-model="form.image" label="Image du produit" accept="image/*" :error="form.errors.image" />

        <Select v-model="form.status" label="Statut" :options="statusOptions" required :error="form.errors.status" />

        <div class="border-t pt-6">
          <h3 class="font-semibold text-gray-900 mb-4">Ingrédients</h3>
          <div v-for="(item, index) in form.ingredients" :key="index" class="flex items-end gap-4 mb-4">
            <div class="flex-1">
              <Select v-model="item.id" :options="ingredientOptions" placeholder="Choisir un ingrédient" />
            </div>
            <div class="w-32">
              <Input v-model="item.quantity" type="number" step="0.01" min="0" placeholder="Quantité" />
            </div>
            <button type="button" @click="removeIngredient(index)" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <Button variant="secondary" size="sm" @click="addIngredient">
            <PlusIcon class="w-4 h-4 mr-1" />
            Ajouter un ingrédient
          </Button>
        </div>

        <div class="flex justify-end gap-4 border-t pt-6">
          <Button variant="secondary" @click="$inertia.visit('/products')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Créer le produit</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Textarea, Select, FileUpload } from '@/Components';
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  categories: Array,
  ingredients: Array,
  currencies: Array,
});

const form = useForm({
  name: '',
  description: '',
  category_id: '',
  base_price: '',
  selling_price: '',
  currency_id: '',
  status: 'available',
  image: null,
  ingredients: [],
});

const categoryOptions = props.categories?.map(c => ({ value: c.id, label: c.name })) || [];
const ingredientOptions = props.ingredients?.map(i => ({ value: i.id, label: `${i.name} (${i.unit})` })) || [];
const currencyOptions = props.currencies?.map(c => ({ value: c.id, label: `${c.name} (${c.code})` })) || [];

const statusOptions = [
  { value: 'available', label: 'Disponible' },
  { value: 'unavailable', label: 'Indisponible' },
];

const profit = computed(() => parseFloat(form.selling_price || 0) - parseFloat(form.base_price || 0));
const profitPercent = computed(() => {
  const base = parseFloat(form.base_price || 0);
  if (base === 0) return 0;
  return (profit.value / base) * 100;
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(price);
};

const addIngredient = () => { form.ingredients.push({ id: '', quantity: '' }); };
const removeIngredient = (index) => { form.ingredients.splice(index, 1); };
const submit = () => { form.post('/products', { forceFormData: true }); };
</script>
