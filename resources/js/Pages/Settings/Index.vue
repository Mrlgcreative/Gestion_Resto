<template>
  <MainLayout page-title="Paramètres" page-description="Configuration du système">
    <!-- Tabs -->
    <div class="mb-6">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-4 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="py-2 px-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap"
            :class="activeTab === tab.id 
              ? 'border-primary-500 text-primary-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            @click="activeTab = tab.id"
          >
            <span class="flex items-center gap-2">
              <component :is="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </span>
          </button>
        </nav>
      </div>
    </div>

    <!-- Tab: Établissement -->
    <div v-if="activeTab === 'establishment'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Logo -->
        <Card title="Logo">
          <div class="flex flex-col items-center gap-4">
            <div class="w-32 h-32 rounded-xl bg-gray-100 overflow-hidden border-2 border-dashed border-gray-300">
              <img 
                v-if="settings.logo" 
                :src="`/storage/${settings.logo}`" 
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <BuildingStorefrontIcon class="w-12 h-12" />
              </div>
            </div>
            <FileUpload
              accept="image/*"
              :preview="false"
              hint="PNG, JPG jusqu'à 2MB"
              @change="uploadLogo"
            />
          </div>
        </Card>

        <!-- Info Restaurant -->
        <Card title="Informations" class="lg:col-span-2">
          <form @submit.prevent="submitEstablishment" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Input
                v-model="establishmentForm.restaurant_name"
                label="Nom du restaurant"
                required
                :error="establishmentForm.errors.restaurant_name"
              />
              <Input
                v-model="establishmentForm.phone"
                label="Téléphone"
                :error="establishmentForm.errors.phone"
              />
            </div>
            <Input
              v-model="establishmentForm.email"
              type="email"
              label="Email"
              :error="establishmentForm.errors.email"
            />
            <Textarea
              v-model="establishmentForm.address"
              label="Adresse complète"
              rows="3"
              :error="establishmentForm.errors.address"
            />
            <div class="flex justify-end">
              <Button type="submit" variant="primary" :loading="establishmentForm.processing">
                Enregistrer
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </div>

    <!-- Tab: Commandes -->
    <div v-if="activeTab === 'orders'" class="space-y-6">
      <Card title="Options des commandes">
        <form @submit.prevent="submitOrderOptions" class="space-y-6">
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <h4 class="font-medium text-gray-900">Activation des commandes</h4>
              <p class="text-sm text-gray-500">Activer ou désactiver la prise de commandes</p>
            </div>
            <Toggle v-model="orderOptionsForm.orders_enabled" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              v-model="orderOptionsForm.tax_rate"
              type="number"
              step="0.01"
              min="0"
              max="100"
              label="Taux de taxe (%)"
              :error="orderOptionsForm.errors.tax_rate"
            />
            <Input
              v-model="orderOptionsForm.service_charge"
              type="number"
              step="0.01"
              min="0"
              max="100"
              label="Frais de service (%)"
              :error="orderOptionsForm.errors.service_charge"
            />
          </div>

          <Alert variant="info">
            Ces options seront appliquées aux nouvelles commandes. Les commandes existantes ne seront pas affectées.
          </Alert>

          <div class="flex justify-end">
            <Button type="submit" variant="primary" :loading="orderOptionsForm.processing">
              Enregistrer
            </Button>
          </div>
        </form>
      </Card>
    </div>

    <!-- Tab: Utilisateurs -->
    <div v-if="activeTab === 'users'" class="space-y-6">
      <Card title="Comptes utilisateurs">
        <template #action>
          <Button variant="primary" size="sm" @click="openUserModal()">
            Nouvel utilisateur
          </Button>
        </template>

        <Table :columns="userColumns" :data="users">
          <template #cell-role="{ row }">
            <Badge :variant="getRoleBadgeVariant(row.role?.name)">
              {{ row.role?.name || 'Aucun' }}
            </Badge>
          </template>
          <template #cell-created_at="{ value }">
            {{ formatDate(value) }}
          </template>
          <template #cell-actions="{ row }">
            <div class="flex gap-2">
              <Button variant="ghost" size="sm" @click="editUser(row)">
                Modifier
              </Button>
              <Button 
                v-if="row.id !== currentUserId"
                variant="ghost" 
                size="sm" 
                class="text-red-600"
                @click="deleteUser(row)"
              >
                Supprimer
              </Button>
            </div>
          </template>
        </Table>
      </Card>

      <Card title="Rôles et permissions">
        <div class="space-y-4">
          <div 
            v-for="role in roles" 
            :key="role.id"
            class="p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-medium text-gray-900">{{ role.name }}</h4>
              <Badge variant="default">{{ role.permissions?.length || 0 }} permissions</Badge>
            </div>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="permission in role.permissions?.slice(0, 5)" 
                :key="permission.id"
                class="px-2 py-1 bg-white rounded text-xs text-gray-600 border"
              >
                {{ permission.name }}
              </span>
              <span 
                v-if="role.permissions?.length > 5"
                class="px-2 py-1 bg-primary-100 text-primary-700 rounded text-xs"
              >
                +{{ role.permissions.length - 5 }} autres
              </span>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Tab: Devises -->
    <div v-if="activeTab === 'currencies'" class="space-y-6">
      <Card title="Devise par défaut">
        <form @submit.prevent="submitDefaultCurrency" class="flex items-end gap-4">
          <div class="flex-1">
            <Select
              v-model="defaultCurrencyForm.currency_id"
              label="Sélectionner la devise principale"
              :options="currencyOptions"
            />
          </div>
          <Button type="submit" variant="primary" :loading="defaultCurrencyForm.processing">
            Définir
          </Button>
        </form>
      </Card>

      <Card title="Liste des devises">
        <template #action>
          <Button variant="primary" size="sm" @click="openCurrencyModal()">
            Ajouter une devise
          </Button>
        </template>

        <Table :columns="currencyColumns" :data="currencies">
          <template #cell-display="{ row }">
            <span class="font-mono">{{ row.symbol }} ({{ row.code }})</span>
          </template>
          <template #cell-is_default="{ value }">
            <Badge :variant="value ? 'success' : 'default'">
              {{ value ? 'Par défaut' : '-' }}
            </Badge>
          </template>
          <template #cell-actions="{ row }">
            <div class="flex gap-2">
              <Button variant="ghost" size="sm" @click="editCurrency(row)">
                Modifier
              </Button>
              <Button 
                v-if="!row.is_default"
                variant="ghost" 
                size="sm" 
                class="text-red-600"
                @click="deleteCurrency(row)"
              >
                Supprimer
              </Button>
            </div>
          </template>
        </Table>
      </Card>
    </div>

    <!-- Tab: Taux de change -->
    <div v-if="activeTab === 'exchange'" class="space-y-6">
      <Card title="Options du taux de change">
        <form @submit.prevent="submitExchangeOptions" class="space-y-6">
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <h4 class="font-medium text-gray-900">Activer le taux de change</h4>
              <p class="text-sm text-gray-500">Permettre la conversion entre devises</p>
            </div>
            <Toggle v-model="exchangeOptionsForm.exchange_rate_enabled" />
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <h4 class="font-medium text-gray-900">Mise à jour automatique des prix</h4>
              <p class="text-sm text-gray-500">Recalculer les prix de vente lors du changement de taux</p>
            </div>
            <Toggle v-model="exchangeOptionsForm.auto_update_prices" />
          </div>

          <div class="flex justify-end">
            <Button type="submit" variant="primary" :loading="exchangeOptionsForm.processing">
              Enregistrer
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Taux de change actifs">
        <template #action>
          <Button variant="primary" size="sm" @click="openRateModal()">
            Nouveau taux
          </Button>
        </template>

        <Table :columns="rateColumns" :data="exchangeRates">
          <template #cell-currency="{ row }">
            <span class="font-medium">{{ row.currency?.name }}</span>
            <span class="text-gray-500 ml-1">({{ row.currency?.code }})</span>
          </template>
          <template #cell-rate="{ value }">
            <span class="font-mono text-lg">{{ parseFloat(value).toFixed(4) }}</span>
          </template>
          <template #cell-actions="{ row }">
            <div class="flex gap-2">
              <Button variant="ghost" size="sm" @click="editRate(row)">
                Modifier
              </Button>
              <Button variant="ghost" size="sm" class="text-red-600" @click="deleteRate(row)">
                Supprimer
              </Button>
            </div>
          </template>
        </Table>

        <EmptyState
          v-if="exchangeRates.length === 0"
          title="Aucun taux de change"
          description="Ajoutez des taux de change pour les devises"
        />
      </Card>

      <Card title="Prix de vente">
        <div class="flex items-center justify-between p-4 bg-amber-50 border border-amber-200 rounded-lg">
          <div>
            <h4 class="font-medium text-amber-900">Recalculer tous les prix</h4>
            <p class="text-sm text-amber-700">
              Cette action mettra à jour les prix de vente de tous les produits selon le taux actuel.
            </p>
          </div>
          <Button variant="warning" @click="updateAllPrices">
            Mettre à jour les prix
          </Button>
        </div>
      </Card>
    </div>

    <!-- User Modal -->
    <Modal :show="showUserModal" @close="closeUserModal" :title="editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'">
      <form @submit.prevent="submitUser" class="space-y-4">
        <Input
          v-model="userForm.name"
          label="Nom complet"
          required
          :error="userForm.errors.name"
        />
        <Input
          v-model="userForm.email"
          type="email"
          label="Email"
          required
          :error="userForm.errors.email"
        />
        <Input
          v-model="userForm.password"
          type="password"
          :label="editingUser ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'"
          :required="!editingUser"
          :error="userForm.errors.password"
        />
        <Select
          v-model="userForm.role_id"
          label="Rôle"
          :options="roleOptions"
          required
          :error="userForm.errors.role_id"
        />
        <div class="flex justify-end gap-4 pt-4">
          <Button variant="secondary" @click="closeUserModal">Annuler</Button>
          <Button type="submit" variant="primary" :loading="userForm.processing">
            {{ editingUser ? 'Modifier' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>

    <!-- Currency Modal -->
    <Modal :show="showCurrencyModal" @close="closeCurrencyModal" :title="editingCurrency ? 'Modifier la devise' : 'Nouvelle devise'">
      <form @submit.prevent="submitCurrency" class="space-y-4">
        <Input
          v-model="currencyForm.code"
          label="Code (3 lettres)"
          placeholder="USD"
          maxlength="3"
          :disabled="!!editingCurrency"
          required
          :error="currencyForm.errors.code"
        />
        <Input
          v-model="currencyForm.name"
          label="Nom"
          placeholder="Dollar américain"
          required
          :error="currencyForm.errors.name"
        />
        <Input
          v-model="currencyForm.symbol"
          label="Symbole"
          placeholder="$"
          required
          :error="currencyForm.errors.symbol"
        />
        <div class="flex justify-end gap-4 pt-4">
          <Button variant="secondary" @click="closeCurrencyModal">Annuler</Button>
          <Button type="submit" variant="primary" :loading="currencyForm.processing">
            {{ editingCurrency ? 'Modifier' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>

    <!-- Rate Modal -->
    <Modal :show="showRateModal" @close="closeRateModal" :title="editingRate ? 'Modifier le taux' : 'Nouveau taux de change'">
      <form @submit.prevent="submitRate" class="space-y-4">
        <Select
          v-model="rateForm.currency_id"
          label="Devise"
          :options="currencyOptions"
          :disabled="!!editingRate"
          required
          :error="rateForm.errors.currency_id"
        />
        <Input
          v-model="rateForm.rate"
          type="number"
          step="0.0001"
          min="0.0001"
          label="Taux (par rapport à la devise de base)"
          required
          :error="rateForm.errors.rate"
        />
        <Alert variant="info">
          Exemple : Si 1 USD = 2500 CDF, entrez 2500 pour le CDF.
        </Alert>
        <div class="flex justify-end gap-4 pt-4">
          <Button variant="secondary" @click="closeRateModal">Annuler</Button>
          <Button type="submit" variant="primary" :loading="rateForm.processing">
            {{ editingRate ? 'Modifier' : 'Créer' }}
          </Button>
        </div>
      </form>
    </Modal>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { 
  Button, Card, Input, Select, Textarea, Table, Badge, Modal, 
  EmptyState, Toggle, Alert, FileUpload 
} from '@/Components';
import { 
  BuildingStorefrontIcon, 
  ShoppingCartIcon, 
  UsersIcon, 
  CurrencyDollarIcon,
  ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  settings: Object,
  currencies: Array,
  exchangeRates: Array,
  users: Array,
  roles: Array,
});

const currentUserId = usePage().props.auth?.user?.id;
const activeTab = ref('establishment');

const tabs = [
  { id: 'establishment', label: 'Établissement', icon: BuildingStorefrontIcon },
  { id: 'orders', label: 'Commandes', icon: ShoppingCartIcon },
  { id: 'users', label: 'Utilisateurs', icon: UsersIcon },
  { id: 'currencies', label: 'Devises', icon: CurrencyDollarIcon },
  { id: 'exchange', label: 'Taux de change', icon: ArrowsRightLeftIcon },
];

// Forms
const establishmentForm = useForm({
  restaurant_name: props.settings?.restaurant_name || '',
  address: props.settings?.address || '',
  phone: props.settings?.phone || '',
  email: props.settings?.email || '',
});

const orderOptionsForm = useForm({
  orders_enabled: props.settings?.orders_enabled ?? true,
  tax_rate: props.settings?.tax_rate || 0,
  service_charge: props.settings?.service_charge || 0,
});

const exchangeOptionsForm = useForm({
  exchange_rate_enabled: props.settings?.exchange_rate_enabled ?? true,
  auto_update_prices: props.settings?.auto_update_prices ?? false,
});

const defaultCurrencyForm = useForm({
  currency_id: props.settings?.default_currency_id || '',
});

const userForm = useForm({
  name: '',
  email: '',
  password: '',
  role_id: '',
});

const currencyForm = useForm({
  code: '',
  name: '',
  symbol: '',
});

const rateForm = useForm({
  currency_id: '',
  rate: '',
});

// Modals
const showUserModal = ref(false);
const showCurrencyModal = ref(false);
const showRateModal = ref(false);
const editingUser = ref(null);
const editingCurrency = ref(null);
const editingRate = ref(null);

// Options
const currencyOptions = computed(() => 
  props.currencies?.map(c => ({ value: c.id, label: `${c.name} (${c.code})` })) || []
);

const roleOptions = computed(() => 
  props.roles?.map(r => ({ value: r.id, label: r.name })) || []
);

// Columns
const userColumns = [
  { key: 'name', label: 'Nom' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Rôle' },
  { key: 'created_at', label: 'Créé le' },
  { key: 'actions', label: '' },
];

const currencyColumns = [
  { key: 'name', label: 'Nom' },
  { key: 'display', label: 'Affichage' },
  { key: 'is_default', label: 'Statut' },
  { key: 'actions', label: '' },
];

const rateColumns = [
  { key: 'currency', label: 'Devise' },
  { key: 'rate', label: 'Taux' },
  { key: 'actions', label: '' },
];

// Helpers
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getRoleBadgeVariant = (roleName) => {
  const variants = {
    'Admin': 'danger',
    'Gérant': 'warning',
    'Caissier': 'info',
  };
  return variants[roleName] || 'default';
};

// Submit handlers
const submitEstablishment = () => {
  establishmentForm.put('/settings', {
    preserveScroll: true,
  });
};

const uploadLogo = (file) => {
  const formData = new FormData();
  formData.append('logo', file);
  router.post('/settings/logo', formData, {
    preserveScroll: true,
  });
};

const submitOrderOptions = () => {
  orderOptionsForm.put('/settings/order-options', {
    preserveScroll: true,
  });
};

const submitExchangeOptions = () => {
  exchangeOptionsForm.put('/settings/exchange-options', {
    preserveScroll: true,
  });
};

const submitDefaultCurrency = () => {
  defaultCurrencyForm.put('/settings/default-currency', {
    preserveScroll: true,
  });
};

// User handlers
const openUserModal = (user = null) => {
  editingUser.value = user;
  if (user) {
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.password = '';
    userForm.role_id = user.role_id;
  } else {
    userForm.reset();
  }
  showUserModal.value = true;
};

const editUser = (user) => openUserModal(user);

const closeUserModal = () => {
  showUserModal.value = false;
  editingUser.value = null;
  userForm.reset();
};

const submitUser = () => {
  if (editingUser.value) {
    userForm.put(`/settings/users/${editingUser.value.id}`, {
      onSuccess: closeUserModal,
      preserveScroll: true,
    });
  } else {
    userForm.post('/settings/users', {
      onSuccess: closeUserModal,
      preserveScroll: true,
    });
  }
};

const deleteUser = (user) => {
  if (confirm(`Supprimer l'utilisateur "${user.name}" ?`)) {
    router.delete(`/settings/users/${user.id}`, {
      preserveScroll: true,
    });
  }
};

// Currency handlers
const openCurrencyModal = (currency = null) => {
  editingCurrency.value = currency;
  if (currency) {
    currencyForm.code = currency.code;
    currencyForm.name = currency.name;
    currencyForm.symbol = currency.symbol;
  } else {
    currencyForm.reset();
  }
  showCurrencyModal.value = true;
};

const editCurrency = (currency) => openCurrencyModal(currency);

const closeCurrencyModal = () => {
  showCurrencyModal.value = false;
  editingCurrency.value = null;
  currencyForm.reset();
};

const submitCurrency = () => {
  if (editingCurrency.value) {
    currencyForm.put(`/settings/currencies/${editingCurrency.value.id}`, {
      onSuccess: closeCurrencyModal,
      preserveScroll: true,
    });
  } else {
    currencyForm.post('/settings/currencies', {
      onSuccess: closeCurrencyModal,
      preserveScroll: true,
    });
  }
};

const deleteCurrency = (currency) => {
  if (confirm(`Supprimer la devise "${currency.name}" ?`)) {
    router.delete(`/settings/currencies/${currency.id}`, {
      preserveScroll: true,
    });
  }
};

// Rate handlers
const openRateModal = (rate = null) => {
  editingRate.value = rate;
  if (rate) {
    rateForm.currency_id = rate.currency_id;
    rateForm.rate = rate.rate;
  } else {
    rateForm.reset();
  }
  showRateModal.value = true;
};

const editRate = (rate) => openRateModal(rate);

const closeRateModal = () => {
  showRateModal.value = false;
  editingRate.value = null;
  rateForm.reset();
};

const submitRate = () => {
  if (editingRate.value) {
    rateForm.put(`/settings/exchange-rates/${editingRate.value.id}`, {
      onSuccess: closeRateModal,
      preserveScroll: true,
    });
  } else {
    rateForm.post('/settings/exchange-rates', {
      onSuccess: closeRateModal,
      preserveScroll: true,
    });
  }
};

const deleteRate = (rate) => {
  if (confirm('Supprimer ce taux de change ?')) {
    router.delete(`/settings/exchange-rates/${rate.id}`, {
      preserveScroll: true,
    });
  }
};

const updateAllPrices = () => {
  if (confirm('Cette action va recalculer tous les prix de vente. Continuer ?')) {
    router.post('/settings/update-prices', {}, {
      preserveScroll: true,
    });
  }
};
</script>
