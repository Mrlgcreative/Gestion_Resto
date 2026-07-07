<template>
  <MainLayout page-title="Nouvel ingrédient" page-description="Ajouter un ingrédient au stock">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
      <form @submit.prevent="submit" class="space-y-6">
        <Input v-model="form.name" label="Nom de l'ingrédient" required :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <Input v-model="form.quantity" type="number" step="0.01" min="0" label="Quantité initiale" required :error="form.errors.quantity" />
          <Input v-model="form.unit" label="Unité" placeholder="kg, L, pcs..." required :error="form.errors.unit" />
        </div>
        <Input v-model="form.alert_level" type="number" step="0.01" min="0" label="Seuil d'alerte" helper="Vous serez alerté quand le stock descend sous ce niveau" required :error="form.errors.alert_level" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/stocks')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Créer l'ingrédient</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input } from '@/Components';

const form = useForm({ name: '', quantity: '', unit: '', alert_level: '' });
const submit = () => { form.post('/stocks'); };
</script>
