<template>
  <MainLayout page-title="Nouveau serveur" page-description="Ajouter un serveur au restaurant">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-lg">
      <form @submit.prevent="submit" class="space-y-6">
        <Input v-model="form.name" label="Nom du serveur" required :error="form.errors.name" />
        <Input v-model="form.phone" label="Téléphone" :error="form.errors.phone" />
        <Toggle v-model="form.is_active" label="Serveur actif" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/servers')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Créer le serveur</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Toggle } from '@/Components';

const form = useForm({ name: '', phone: '', is_active: true });
const submit = () => { form.post('/servers'); };
</script>
