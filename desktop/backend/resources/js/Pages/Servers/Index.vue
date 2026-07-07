<template>
  <MainLayout page-title="Serveurs" page-description="Gérer les serveurs du restaurant">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <SearchInput v-model="filters.search" placeholder="Rechercher un serveur..." @update:modelValue="applyFilters" />
        </div>
        <div class="w-48">
          <Select v-model="filters.status" :options="statusOptions" placeholder="Tous les statuts" @update:modelValue="applyFilters" />
        </div>
        <div>
          <Button variant="primary" @click="$inertia.visit('/servers/create')">
            <PlusIcon class="w-5 h-5 mr-1.5" />
            Nouveau serveur
          </Button>
        </div>
      </div>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="server in servers.data" :key="server.id" class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all p-5">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
              <span class="text-xl font-bold text-blue-600">{{ server.name.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">{{ server.name }}</h3>
              <p class="text-sm text-gray-500">{{ server.phone || 'Pas de téléphone' }}</p>
            </div>
          </div>
          <Badge :variant="server.is_active ? 'success' : 'danger'">{{ server.is_active ? 'Actif' : 'Inactif' }}</Badge>
        </div>
        <div class="mt-4 pt-4 border-t flex justify-end gap-2">
          <Button variant="ghost" size="sm" @click="$inertia.visit(`/servers/${server.id}/edit`)">
            <PencilIcon class="w-4 h-4 mr-1" />
            Modifier
          </Button>
          <Button variant="ghost" size="sm" @click="toggleStatus(server)">
            {{ server.is_active ? 'Désactiver' : 'Activer' }}
          </Button>
          <button @click="confirmDelete(server)" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Supprimer">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <EmptyState v-if="servers.data.length === 0" title="Aucun serveur" description="Commencez par ajouter votre premier serveur" action-label="Ajouter un serveur" @action="$inertia.visit('/servers/create')" />

    <div class="mt-6" v-if="servers.data.length > 0">
      <Pagination :current-page="servers.current_page" :total-pages="servers.last_page" :total="servers.total" :per-page="servers.per_page" @page-change="goToPage" />
    </div>

    <ConfirmDialog v-model="showDeleteModal" title="Supprimer le serveur" :message="`Êtes-vous sûr de vouloir supprimer ${serverToDelete?.name} ?`" confirm-text="Supprimer" variant="danger" @confirm="deleteServer" />
  </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Pagination, SearchInput, Select, EmptyState, ConfirmDialog } from '@/Components';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

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

const applyFilters = () => { router.get('/servers', filters, { preserveState: true }); };
const goToPage = (page) => { router.get('/servers', { ...filters, page }, { preserveState: true }); };
const toggleStatus = (server) => { router.patch(`/servers/${server.id}/toggle-status`); };
const confirmDelete = (server) => { serverToDelete.value = server; showDeleteModal.value = true; };
const deleteServer = () => { router.delete(`/servers/${serverToDelete.value.id}`); showDeleteModal.value = false; };
</script>
