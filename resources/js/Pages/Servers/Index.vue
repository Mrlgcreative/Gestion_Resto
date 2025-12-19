<template>
  <MainLayout page-title="Serveurs" page-description="Gérer les serveurs du restaurant">
    <template #header-actions>
      <Button variant="primary" @click="$inertia.visit('/servers/create')">
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau serveur
      </Button>
    </template>

    <!-- Filters -->
    <Card class="mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <SearchInput
            v-model="filters.search"
            placeholder="Rechercher un serveur..."
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

    <!-- Grid of servers -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card v-for="server in servers.data" :key="server.id" class="hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
              <span class="text-xl font-bold text-primary-600">{{ server.name.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">{{ server.name }}</h3>
              <p class="text-sm text-gray-500">{{ server.phone || 'Pas de téléphone' }}</p>
            </div>
          </div>
          <Badge :variant="server.is_active ? 'success' : 'danger'">
            {{ server.is_active ? 'Actif' : 'Inactif' }}
          </Badge>
        </div>

        <div class="mt-4 pt-4 border-t flex justify-end gap-2">
          <Button variant="ghost" size="sm" @click="$inertia.visit(`/servers/${server.id}/edit`)">
            Modifier
          </Button>
          <Button variant="ghost" size="sm" @click="toggleStatus(server)">
            {{ server.is_active ? 'Désactiver' : 'Activer' }}
          </Button>
          <Button variant="ghost" size="sm" @click="confirmDelete(server)">
            <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </Button>
        </div>
      </Card>
    </div>

    <EmptyState
      v-if="servers.data.length === 0"
      title="Aucun serveur"
      description="Commencez par ajouter votre premier serveur"
      action-label="Ajouter un serveur"
      @action="$inertia.visit('/servers/create')"
    />

    <!-- Pagination -->
    <div class="mt-6" v-if="servers.data.length > 0">
      <Pagination
        :current-page="servers.current_page"
        :total-pages="servers.last_page"
        :total="servers.total"
        :per-page="servers.per_page"
        @page-change="goToPage"
      />
    </div>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-model="showDeleteModal"
      title="Supprimer le serveur"
      :message="`Êtes-vous sûr de vouloir supprimer ${serverToDelete?.name} ?`"
      confirm-text="Supprimer"
      variant="danger"
      @confirm="deleteServer"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Pagination, SearchInput, Select, EmptyState, ConfirmDialog } from '@/Components';

const props = defineProps({
  servers: Object,
  filters: Object,
});

const showDeleteModal = ref(false);
const serverToDelete = ref(null);

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
});

const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
];

const applyFilters = () => {
  router.get('/servers', filters, { preserveState: true });
};

const goToPage = (page) => {
  router.get('/servers', { ...filters, page }, { preserveState: true });
};

const toggleStatus = (server) => {
  router.patch(`/servers/${server.id}/toggle-status`);
};

const confirmDelete = (server) => {
  serverToDelete.value = server;
  showDeleteModal.value = true;
};

const deleteServer = () => {
  router.delete(`/servers/${serverToDelete.value.id}`);
  showDeleteModal.value = false;
};
</script>
