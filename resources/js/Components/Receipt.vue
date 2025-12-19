<template>
  <div class="receipt-wrapper">
    <div ref="receiptRef" class="receipt bg-white text-black font-mono text-xs" style="width: 280px;">
      <!-- Header -->
      <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
        <div v-if="settings?.logo" class="flex justify-center mb-2">
          <img :src="`/storage/${settings.logo}`" class="h-10 w-10 rounded" />
        </div>
        <p class="font-bold text-sm uppercase">{{ settings?.restaurant_name || 'Restaurant' }}</p>
        <p v-if="settings?.address" class="text-[10px] text-gray-600 mt-1">{{ settings.address }}</p>
        <p v-if="settings?.phone" class="text-[10px] text-gray-600">Tél: {{ settings.phone }}</p>
      </div>

      <!-- Order Info -->
      <div class="border-b border-dashed border-gray-400 pb-2 mb-2">
        <div class="flex justify-between">
          <span>Facture N°:</span>
          <span class="font-bold">#{{ String(order.id).padStart(6, '0') }}</span>
        </div>
        <div class="flex justify-between">
          <span>Date:</span>
          <span>{{ formatDate(order.created_at) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Heure:</span>
          <span>{{ formatTime(order.created_at) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Serveur:</span>
          <span>{{ order.server?.name || order.user?.name || '-' }}</span>
        </div>
      </div>

      <!-- Items -->
      <div class="border-b border-dashed border-gray-400 pb-2 mb-2">
        <div class="flex justify-between font-bold mb-1">
          <span>Article</span>
          <span>Total</span>
        </div>
        <div class="border-b border-gray-300 mb-1"></div>
        
        <div v-for="item in order.items" :key="item.id" class="mb-1">
          <div class="flex justify-between">
            <span class="flex-1 truncate pr-2">{{ item.product?.name }}</span>
            <span class="font-semibold">{{ formatPrice(item.total_price) }}</span>
          </div>
          <div class="text-[10px] text-gray-500 pl-2">
            {{ item.quantity }} x {{ formatPrice(item.unit_price) }}
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div class="mb-3">
        <div class="flex justify-between">
          <span>Sous-total:</span>
          <span>{{ formatPrice(subtotal) }}</span>
        </div>
        
        <div v-if="settings?.tax_rate > 0" class="flex justify-between text-gray-600">
          <span>TVA ({{ settings.tax_rate }}%):</span>
          <span>{{ formatPrice(taxAmount) }}</span>
        </div>
        
        <div v-if="settings?.service_charge > 0" class="flex justify-between text-gray-600">
          <span>Service ({{ settings.service_charge }}%):</span>
          <span>{{ formatPrice(serviceAmount) }}</span>
        </div>

        <div class="border-t border-double border-gray-400 mt-2 pt-2">
          <div class="flex justify-between font-bold text-sm">
            <span>TOTAL:</span>
            <span>{{ formatPrice(order.total_amount) }}</span>
          </div>
        </div>

        <!-- Payment -->
        <div v-if="order.payment || order.payments?.length" class="mt-2 pt-2 border-t border-dashed border-gray-400">
          <div class="flex justify-between">
            <span>Mode paiement:</span>
            <span>{{ paymentMethod }}</span>
          </div>
          <div v-if="order.payment?.amount_received" class="flex justify-between">
            <span>Reçu:</span>
            <span>{{ formatPrice(order.payment.amount_received) }}</span>
          </div>
          <div v-if="order.payment?.change" class="flex justify-between">
            <span>Rendu:</span>
            <span>{{ formatPrice(order.payment.change) }}</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center border-t border-dashed border-gray-400 pt-3">
        <p class="font-semibold">Merci de votre visite !</p>
        <p v-if="settings?.email" class="text-[10px] text-gray-500 mt-1">{{ settings.email }}</p>
        <div class="mt-2">
          <p class="text-[10px] text-gray-400">{{ formatDateTime(new Date()) }}</p>
        </div>
        
        <!-- Barcode simulation -->
        <div class="mt-3 flex justify-center">
          <div class="flex gap-px">
            <div v-for="i in 30" :key="i" 
              class="bg-black" 
              :style="{ width: Math.random() > 0.5 ? '2px' : '1px', height: '20px' }"
            ></div>
          </div>
        </div>
        <p class="text-[8px] text-gray-400 mt-1">{{ String(order.id).padStart(12, '0') }}</p>
      </div>
    </div>

    <!-- Actions (hidden in print) -->
    <div class="mt-4 flex justify-center gap-3 print:hidden">
      <button
        @click="printReceipt"
        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Imprimer
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  settings: {
    type: Object,
    default: null,
  },
  defaultCurrency: {
    type: String,
    default: 'USD',
  },
});

const receiptRef = ref(null);

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

// Mode de paiement
const paymentMethod = computed(() => {
  const payment = props.order.payment || props.order.payments?.[0];
  if (!payment) return '-';
  
  const methods = {
    cash: 'Espèces',
    card: 'Carte',
    mobile: 'Mobile',
  };
  return methods[payment.method] || payment.method;
});

// Formatters
const formatPrice = (amount) => {
  const currency = props.order.currency || props.defaultCurrency;
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

// Print
const printReceipt = () => {
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reçu #${props.order.id}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
          font-family: 'Courier New', monospace; 
          font-size: 12px; 
          width: 80mm; 
          padding: 5mm;
          background: white;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .border-dashed { border-style: dashed; }
        .border-b { border-bottom: 1px dashed #666; }
        .border-t { border-top: 1px dashed #666; }
        .border-double { border-style: double; border-width: 3px; }
        .py-2 { padding: 8px 0; }
        .mb-2 { margin-bottom: 8px; }
        .mt-2 { margin-top: 8px; }
        .text-sm { font-size: 14px; }
        .text-xs { font-size: 10px; }
        .uppercase { text-transform: uppercase; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .logo { width: 40px; height: 40px; border-radius: 4px; }
        img { max-width: 40px; }
        @media print {
          @page { margin: 0; size: 80mm auto; }
          body { width: 80mm; }
        }
      </style>
    </head>
    <body>
      ${receiptRef.value.innerHTML}
    </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
};
</script>

<style scoped>
.receipt-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.receipt {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  padding: 16px;
}

@media print {
  .receipt-wrapper {
    display: block;
  }
  
  .receipt {
    box-shadow: none;
    width: 80mm !important;
    padding: 0;
  }
}
</style>
