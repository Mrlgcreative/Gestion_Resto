<template>
  <MainLayout page-title="Modifier l'utilisateur" :page-description="user.name">
    <Card class="max-w-2xl">
      <form @submit.prevent="submit" class="space-y-6">
        <Input
          v-model="form.name"
          label="Nom complet"
          required
          :error="form.errors.name"
        />

        <Input
          v-model="form.email"
          type="email"
          label="Email"
          required
          :error="form.errors.email"
        />

        <Input
          v-model="form.phone"
          label="Téléphone"
          :error="form.errors.phone"
        />

        <Select
          v-model="form.role_id"
          label="Rôle"
          :options="roleOptions"
          required
          :error="form.errors.role_id"
        />

        <div class="border-t pt-4">
          <p class="text-sm text-gray-500 mb-4">Laissez vide pour conserver le mot de passe actuel</p>
          
          <Input
            v-model="form.password"
            type="password"
            label="Nouveau mot de passe"
            :error="form.errors.password"
          />

          <Input
            v-model="form.password_confirmation"
            type="password"
            label="Confirmer le mot de passe"
            class="mt-4"
          />
        </div>

        <Select
          v-model="form.status"
          label="Statut"
          :options="statusOptions"
          required
          :error="form.errors.status"
        />

        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/users')">
            Annuler
          </Button>
          <Button type="submit" variant="primary" :loading="form.processing">
            Enregistrer
          </Button>
        </div>
      </form>
    </Card>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Select } from '@/Components';

const props = defineProps({
  user: Object,
  roles: Array,
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
  password: '',
  password_confirmation: '',
  role_id: props.user.role_id,
  status: props.user.status,
});

const roleOptions = props.roles?.map(r => ({ value: r.id, label: r.name })) || [];

const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
];

const submit = () => {
  form.put(`/users/${props.user.id}`);
};
</script>
