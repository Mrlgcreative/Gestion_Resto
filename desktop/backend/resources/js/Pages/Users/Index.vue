<template>
  <MainLayout page-title="Utilisateurs" page-description="Gérer les utilisateurs du système">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <SearchInput v-model="filters.search" placeholder="Rechercher un utilisateur..." @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Select v-model="filters.role" :options="roleOptions" placeholder="Tous les rôles" @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Select v-model="filters.status" :options="statusOptions" placeholder="Tous les statuts" @update:modelValue="applyFilters" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/users/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouvel utilisateur
          </Button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <Table :columns="columns" :data="users.data" :loading="loading">
        <template #cell-email="{ value }">
          <span class="text-sm">{{ value }}</span>
        </template>
        <template #cell-role="{ row }">
          <Badge :variant="getRoleBadgeVariant(row.role?.name)">{{ row.role?.name || 'N/A' }}</Badge>
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="row.status === 'active' ? 'success' : 'danger'">{{ row.status === 'active' ? 'Actif' : 'Inactif' }}</Badge>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <button @click="$inertia.visit(`/users/${row.id}/edit`)" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Modifier">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="toggleStatus(row)" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" :class="row.status === 'active' ? 'text-red-500' : 'text-emerald-500'" :title="row.status === 'active' ? 'Désactiver' : 'Activer'">
              <EyeSlashIcon v-if="row.status === 'active'" class="w-4 h-4" />
              <EyeIcon v-else class="w-4 h-4" />
            </button>
            <button @click="confirmDelete(row)" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Supprimer">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </template>
      </Table>
      <div class="p-4 border-t">
        <Pagination :current-page="users.current_page" :total-pages="users.last_page" :total="users.total" :per-page="users.per_page" @page-change="goToPage" />
      </div>
    </div>

    <ConfirmDialog v-model="showDeleteModal" title="Supprimer l'utilisateur" :message="`Êtes-vous sûr de vouloir supprimer ${userToDelete?.name} ?`" confirm-text="Supprimer" variant="danger" @confirm="deleteUser" />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, Select, ConfirmDialog } from '@/Components';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object,
});

const loading = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const filters = reactive({
  search: props.filters?.search || '',
  role: props.filters?.role || '',
  status: props.filters?.status || '',
});

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Téléphone' },
  { key: 'role', label: 'Rôle' },
  { key: 'status', label: 'Statut' },
  { key: 'actions', label: 'Actions' },
];

const roleOptions = props.roles?.map(r => ({ value: r.id, label: r.name })) || [];
const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
];

const getRoleBadgeVariant = (role) => {
  const variants = { admin: 'danger', gerant: 'warning', caissier: 'primary' };
  return variants[role] || 'default';
};

const applyFilters = () => {
  loading.value = true;
  router.get('/users', filters, { preserveState: true, onFinish: () => loading.value = false });
};
const goToPage = (page) => {
  loading.value = true;
  router.get('/users', { ...filters, page }, { preserveState: true, onFinish: () => loading.value = false });
};
const toggleStatus = (user) => { router.patch(`/users/${user.id}/toggle-status`); };
const confirmDelete = (user) => { userToDelete.value = user; showDeleteModal.value = true; };
const deleteUser = () => { router.delete(`/users/${userToDelete.value.id}`); showDeleteModal.value = false; };
</script>
