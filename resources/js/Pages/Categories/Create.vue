<template>
  <MainLayout page-title="Nouvelle catégorie" page-description="Créer une catégorie de produits">
    <Card class="max-w-lg">
      <form @submit.prevent="submit" class="space-y-6">
        <Input
          v-model="form.name"
          label="Nom de la catégorie"
          required
          :error="form.errors.name"
        />

        <Textarea
          v-model="form.description"
          label="Description"
          rows="3"
          :error="form.errors.description"
        />

        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/categories')">
            Annuler
          </Button>
          <Button type="submit" variant="primary" :loading="form.processing">
            Créer la catégorie
          </Button>
        </div>
      </form>
    </Card>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Textarea } from '@/Components';

const form = useForm({
  name: '',
  description: '',
});

const submit = () => {
  form.post('/categories');
};
</script>
