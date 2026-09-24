<template>
  <MainLayout page-title="Nouvel utilisateur" page-description="Créer un nouveau compte utilisateur">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
      <form @submit.prevent="submit" class="space-y-6">
        <Input v-model="form.name" label="Nom complet" required :error="form.errors.name" />
        <Input v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
        <Input v-model="form.phone" label="Téléphone" :error="form.errors.phone" />
        <Select v-model="form.role_id" label="Rôle" :options="roleOptions" required :error="form.errors.role_id" />
        <Input v-model="form.password" type="password" label="Mot de passe" required :error="form.errors.password" />
        <Input v-model="form.password_confirmation" type="password" label="Confirmer le mot de passe" required />
        <Select v-model="form.status" label="Statut" :options="statusOptions" required :error="form.errors.status" />
        <div class="flex justify-end gap-4">
          <Button variant="secondary" @click="$inertia.visit('/users')">Annuler</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Créer l'utilisateur</Button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Input, Select } from '@/Components';

const props = defineProps({ roles: Array });

const form = useForm({
  name: '', email: '', phone: '', password: '', password_confirmation: '', role_id: '', status: 'active',
});

const roleOptions = props.roles?.map(r => ({ value: r.id, label: r.name })) || [];
const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
];

const submit = () => { form.post('/users'); };
</script>
