<template>
  <MainLayout page-title="Ouvrir une session" page-description="Démarrer une nouvelle session de caisse">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
      <form @submit.prevent="submit" class="space-y-6">
        <Select v-model="form.currency_id" label="Devise" :options="currencyOptions" required :error="form.errors.currency_id" />
        <Input v-model="form.opening_amount" type="number" step="0.01" min="0" label="Montant d'ouverture" helper="Le montant en caisse au début de la session" required :error="form.errors.opening_amount" />
        <Textarea v-model="form.notes" label="Notes (optionnel)" rows="3" :error="form.errors.notes" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/sessions')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Ouvrir la session</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Select, Textarea } from '@/Components';

const props = defineProps({ currencies: Array });

const form = useForm({
  currency_id: props.currencies?.[0]?.id || '',
  opening_amount: '',
  notes: '',
});

const currencyOptions = props.currencies?.map(c => ({ value: c.id, label: `${c.name} (${c.code})` })) || [];

const submit = () => { form.post('/sessions'); };
</script>
