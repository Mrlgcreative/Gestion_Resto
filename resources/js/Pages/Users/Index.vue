<template>
  <MainLayout page-title="Utilisateurs" page-description="Gérer les utilisateurs du système">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/users/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvel utilisateur
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <SearchInput
            v-model="filters.search"
            placeholder="Rechercher un utilisateur..."
            @update:modelValue="applyFilters"
          />
        </div>
        <div class="w-48">
          <Select
            v-model="filters.role"
            :options="roleOptions"
            placeholder="Tous les rôles"
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

    <!-- Table -->
    <Card>
      <Table
        :columns="columns"
        :data="users.data"
        :loading="loading"
      >
        <template #cell-role="{ row }">
          <Badge :variant="getRoleBadgeVariant(row.role?.name)">
            {{ row.role?.name || 'N/A' }}
          </Badge>
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="row.status === 'active' ? 'success' : 'danger'">
            {{ row.status === 'active' ? 'Actif' : 'Inactif' }}
          </Badge>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <Button variant="ghost" size="sm" @click="$inertia.visit(`/users/${row.id}/edit`)">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="toggleStatus(row)">
              <svg class="h-4 w-4" :class="row.status === 'active' ? 'text-red-500' : 'text-green-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </Button>
            <Button variant="ghost" size="sm" @click="confirmDelete(row)">
              <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </div>
        </template>
      </Table>

      <!-- Pagination -->
      <div class="mt-4">
        <Pagination
          :current-page="users.current_page"
          :total-pages="users.last_page"
          :total="users.total"
          :per-page="users.per_page"
          @page-change="goToPage"
        />
      </div>
    </Card>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-model="showDeleteModal"
      title="Supprimer l'utilisateur"
      :message="`Êtes-vous sûr de vouloir supprimer ${userToDelete?.name} ?`"
      confirm-text="Supprimer"
      variant="danger"
      @confirm="deleteUser"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Table, Badge, Pagination, SearchInput, Select, ConfirmDialog } from '@/Components';

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
  const variants = {
    admin: 'danger',
    gerant: 'warning',
    caissier: 'primary',
  };
  return variants[role] || 'default';
};

const applyFilters = () => {
  loading.value = true;
  router.get('/users', filters, {
    preserveState: true,
    onFinish: () => loading.value = false,
  });
};

const goToPage = (page) => {
  loading.value = true;
  router.get('/users', { ...filters, page }, {
    preserveState: true,
    onFinish: () => loading.value = false,
  });
};

const toggleStatus = (user) => {
  router.patch(`/users/${user.id}/toggle-status`);
};

const confirmDelete = (user) => {
  userToDelete.value = user;
  showDeleteModal.value = true;
};

const deleteUser = () => {
  router.delete(`/users/${userToDelete.value.id}`);
  showDeleteModal.value = false;
};
</script>
