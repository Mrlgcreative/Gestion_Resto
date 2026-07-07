<template>
  <div class="invoice-container bg-white" ref="invoiceRef">
    <!-- Invoice Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white p-8 rounded-t-xl">
      <div class="flex justify-between items-start">
        <!-- Restaurant Info -->
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center overflow-hidden">
            <img 
              v-if="settings?.logo" 
              :src="`${storageUrl}/${settings.logo}`" 
              class="w-full h-full object-cover"
            />
            <span v-else class="text-3xl">🍽️</span>
          </div>
          <div>
            <h1 class="text-2xl font-bold">{{ settings?.restaurant_name || 'Restaurant' }}</h1>
            <p class="text-primary-100 text-sm mt-1">{{ settings?.address }}</p>
            <p class="text-primary-100 text-sm">{{ settings?.phone }}</p>
          </div>
        </div>

        <!-- Invoice Info -->
        <div class="text-right">
          <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2 inline-block">
            <p class="text-primary-100 text-xs uppercase tracking-wider">Facture</p>
            <p class="text-2xl font-bold">#{{ String(order.id).padStart(6, '0') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Invoice Details -->
    <div class="p-8">
      <!-- Meta Info -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8 pb-8 border-b border-gray-200">
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Date</p>
          <p class="font-semibold text-gray-900">{{ formatDate(order.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Heure</p>
          <p class="font-semibold text-gray-900">{{ formatTime(order.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Serveur</p>
          <p class="font-semibold text-gray-900">{{ order.server?.name || order.user?.name || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Statut</p>
          <span 
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="statusClasses"
          >
            {{ statusLabel }}
          </span>
        </div>
      </div>

      <!-- Items Table -->
      <div class="mb-8">
        <table class="w-full">
          <thead>
            <tr class="border-b-2 border-gray-200">
              <th class="text-left py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Article</th>
              <th class="text-center py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qté</th>
              <th class="text-right py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Prix unit.</th>
              <th class="text-right py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
              <td class="py-4">
                <p class="font-medium text-gray-900">{{ item.product?.name }}</p>
                <p v-if="item.product?.category" class="text-xs text-gray-500">{{ item.product.category.name }}</p>
              </td>
              <td class="py-4 text-center">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-semibold">
                  {{ item.quantity }}
                </span>
              </td>
              <td class="py-4 text-right text-gray-600">
                {{ formatCurrency(item.unit_price) }}
              </td>
              <td class="py-4 text-right font-semibold text-gray-900">
                {{ formatCurrency(item.total_price) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="flex justify-end">
        <div class="w-full md:w-80">
          <div class="space-y-3">
            <!-- Sous-total -->
            <div class="flex justify-between text-gray-600">
              <span>Sous-total</span>
              <span>{{ formatCurrency(subtotal) }}</span>
            </div>

            <!-- Taxe -->
            <div v-if="settings?.tax_rate > 0" class="flex justify-between text-gray-600">
              <span>TVA ({{ settings.tax_rate }}%)</span>
              <span>{{ formatCurrency(taxAmount) }}</span>
            </div>

            <!-- Service -->
            <div v-if="settings?.service_charge > 0" class="flex justify-between text-gray-600">
              <span>Service ({{ settings.service_charge }}%)</span>
              <span>{{ formatCurrency(serviceAmount) }}</span>
            </div>

            <!-- Divider -->
            <div class="border-t-2 border-gray-200 pt-3">
              <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-gray-900">Total</span>
                <span class="text-2xl font-bold text-primary-600">{{ formatCurrency(order.total_amount) }}</span>
              </div>
              
              <!-- Currency Equivalents -->
              <div v-if="exchangeRates && exchangeRates.length > 0" class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Équivalent</p>
                <div class="space-y-1">
                  <div 
                    v-for="rate in exchangeRates" 
                    :key="rate.currency_id"
                    class="flex justify-between text-sm text-gray-600"
                  >
                    <span>{{ rate.currency?.code || rate.code }}</span>
                    <span class="font-medium">{{ formatEquivalent(order.total_amount, rate) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div v-if="order.payment || order.payments?.length" class="mt-6 p-4 bg-green-50 rounded-xl border border-green-200">
            <div class="flex items-center gap-2 text-green-700 mb-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold">Payé</span>
            </div>
            <p class="text-sm text-green-600">
              Mode: {{ paymentMethod }}
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-12 pt-8 border-t border-gray-200 text-center">
        <p class="text-gray-500 text-sm">Merci de votre visite !</p>
        <p v-if="settings?.email" class="text-gray-400 text-xs mt-1">{{ settings.email }}</p>
        <div class="mt-4 flex justify-center gap-4 text-xs text-gray-400">
          <span>Facture générée le {{ formatDate(new Date()) }}</span>
        </div>
      </div>
    </div>

    <!-- Print Button (hidden in print) -->
    <div class="p-6 bg-gray-50 border-t border-gray-200 rounded-b-xl print:hidden">
      <div class="flex justify-center gap-4">
        <button
          @click="printInvoice"
          class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Imprimer
        </button>
        <button
          v-if="showDownload"
          @click="$emit('download')"
          class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Télécharger PDF
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

// URL de base pour les images storage
const storageUrl = window.__STORAGE_URL__ || '/storage';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  settings: {
    type: Object,
    default: null,
  },
  exchangeRates: {
    type: Array,
    default: () => [],
  },
  defaultCurrency: {
    type: String,
    default: 'USD',
  },
  showDownload: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['download']);

const invoiceRef = ref(null);

// Calculs
const subtotal = computed(() => {
  return props.order.items?.reduce((sum, item) => sum + parseFloat(item.total_price), 0) || 0;
});

const taxAmount = computed(() => {
  if (!props.settings?.tax_rate) return 0;
  return subtotal.value * (props.settings.tax_rate / 100);
});

const serviceAmount = computed(() => {
  if (!props.settings?.service_charge) return 0;
  return subtotal.value * (props.settings.service_charge / 100);
});

// Statut
const statusClasses = computed(() => {
  switch (props.order.status) {
    case 'paid':
      return 'bg-green-100 text-green-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'canceled':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
});

const statusLabel = computed(() => {
  switch (props.order.status) {
    case 'paid':
      return 'Payé';
    case 'pending':
      return 'En attente';
    case 'canceled':
      return 'Annulé';
    default:
      return props.order.status;
  }
});

// Mode de paiement
const paymentMethod = computed(() => {
  const payment = props.order.payment || props.order.payments?.[0];
  if (!payment) return '-';
  
  const methods = {
    cash: 'Espèces',
    card: 'Carte bancaire',
    mobile: 'Mobile Money',
  };
  return methods[payment.method] || payment.method;
});

// Formatters
const formatCurrency = (amount) => {
  const currency = props.order.currency || props.defaultCurrency;
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currency,
  }).format(amount);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Format equivalent in other currency
const formatEquivalent = (amount, rate) => {
  const convertedAmount = parseFloat(amount) * parseFloat(rate.rate);
  const currencyCode = rate.currency?.code || rate.code;
  
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currencyCode,
    minimumFractionDigits: currencyCode === 'CDF' ? 0 : 2,
    maximumFractionDigits: currencyCode === 'CDF' ? 0 : 2,
  }).format(convertedAmount);
};

// Print
const printInvoice = () => {
  window.print();
};
</script>

<style scoped>
@media print {
  .invoice-container {
    margin: 0;
    padding: 0;
    box-shadow: none !important;
    border-radius: 0 !important;
  }

  .invoice-container > div:first-child {
    border-radius: 0 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
