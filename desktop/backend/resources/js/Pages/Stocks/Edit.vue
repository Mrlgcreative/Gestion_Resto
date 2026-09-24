<template>
  <MainLayout page-title="Modifier l'ingrédient" :page-description="ingredient.name">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
      <form @submit.prevent="submit" class="space-y-6">
        <Input v-model="form.name" label="Nom de l'ingrédient" required :error="form.errors.name" />
        <div class="bg-gray-50 p-4 rounded-lg border">
          <p class="text-sm text-gray-600">Stock actuel: <span class="font-semibold">{{ ingredient.quantity }} {{ ingredient.unit }}</span></p>
          <p class="text-xs text-gray-500 mt-1">Pour modifier le stock, utilisez les boutons + et - sur la page de liste.</p>
        </div>
        <Input v-model="form.unit" label="Unité" required :error="form.errors.unit" />
        <Input v-model="form.alert_level" type="number" step="0.01" min="0" label="Seuil d'alerte" helper="Vous serez alerté quand le stock descend sous ce niveau" required :error="form.errors.alert_level" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/stocks')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Enregistrer</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input } from '@/Components';

const props = defineProps({ ingredient: Object });
const form = useForm({ name: props.ingredient.name, unit: props.ingredient.unit, alert_level: props.ingredient.alert_level });
const submit = () => { form.put(`/stocks/${props.ingredient.id}`); };
</script>
