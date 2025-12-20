          <template>
  <div class="receipt-wrapper">
    <div ref="receiptRef" class="receipt bg-white text-black font-mono text-xs font-bold" style="width: 200px; font-size: 11px;">
      <!-- Header -->
      <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
        <div v-if="settings?.logo" class="flex justify-center mb-1">
          <img :src="`${storageUrl}/${settings.logo}`" class="h-8 w-8 rounded" />
        </div>
        <p class="font-bold text-sm uppercase">{{ settings?.restaurant_name || 'Restaurant' }}</p>
        <p v-if="settings?.description" class="text-[10px] text-gray-600 mt-1">{{ settings.description }}</p>
        <p v-if="fullLocation" class="text-[10px] text-gray-600">{{ fullLocation }}</p>
        <p v-if="settings?.phone" class="text-[10px] text-gray-600">Tél: {{ settings.phone }}</p>
        <div v-if="settings?.rccm || settings?.id_nat" class="mt-1 text-[9px] text-gray-500">
          <p v-if="settings?.rccm">RCCM: {{ settings.rccm }}</p>
          <p v-if="settings?.id_nat">ID.NAT: {{ settings.id_nat }}</p>
           <p v-if="settings?.address" class="text-[10px] text-gray-600 mt-1">{{ settings.address }}</p>
        </div>
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
          <span>Caissier:</span>
          <span>{{ order.session?.user?.name || order.user?.name || '-' }}</span>
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
            <span class="font-bold">{{ formatPrice(item.total_price) }}</span>
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
          
          <!-- Currency Equivalent (autre devise) -->
          <div v-if="equivalentDisplay" class="mt-2 pt-2 border-t border-dashed border-gray-300">
            <div class="flex justify-between text-[11px]">
              <span>Équivalent {{ equivalentDisplay.code }}:</span>
              <span class="font-bold">{{ equivalentDisplay.formatted }}</span>
            </div>
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
        <p class="font-bold">Merci de votre visite !</p>
        <p v-if="settings?.email" class="text-[10px] text-gray-500 mt-1">{{ settings.email }}</p>
        <div class="mt-2">
          <p class="text-[10px] text-gray-400">{{ formatDateTime(new Date()) }}</p>
        </div>
        
        <!-- Barcode simulation -->
        <div class="mt-3 flex justify-center">
          <div class="flex gap-px">
            <div v-for="(width, i) in barcodeWidths" :key="i" 
              class="bg-black" 
              :style="{ width: width + 'px', height: '20px' }"
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
});

const receiptRef = ref(null);

// Générer un code-barres fixe basé sur l'ID de la commande
const barcodeWidths = computed(() => {
  const seed = props.order.id || 1;
  const widths = [];
  for (let i = 0; i < 30; i++) {
    // Utiliser l'ID pour générer un pattern cohérent
    widths.push(((seed * (i + 1) * 7) % 2) + 1);
  }
  return widths;
});

// Location complète (ville, province, pays)
const fullLocation = computed(() => {
  const parts = [
    props.settings?.city,
    props.settings?.province,
    props.settings?.country,
  ].filter(Boolean);
  return parts.length > 0 ? parts.join(', ') : '';
});

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

// Obtenir le taux de change pour une devise
const getExchangeRate = (currencyCode) => {
  if (currencyCode === 'USD') return 1;
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

// Convertir un montant d'une devise vers une autre (USD comme pivot)
const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

// Format equivalent in other currency
const formatEquivalent = (amount, rate) => {
  const currencyCode = rate.currency?.code || rate.code;
  // Convertir depuis la devise de la commande vers la devise cible
  const convertedAmount = convertCurrency(amount, props.defaultCurrency, currencyCode);
  
  // Pour CDF, pas de décimales
  if (currencyCode === 'CDF') {
    return new Intl.NumberFormat('fr-FR').format(Math.round(convertedAmount)) + ' FC';
  }
  
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currencyCode,
  }).format(convertedAmount);
};

// Équivalent à afficher (si CDF -> montrer USD, si USD -> montrer CDF)
const equivalentDisplay = computed(() => {
  // Utiliser order.currency (le champ string) au lieu de defaultCurrency
  const orderCurrency = props.order.currency || props.defaultCurrency;
  let targetCode;
  
  if (orderCurrency === 'CDF') {
    // Commande en CDF -> afficher équivalent USD
    targetCode = 'USD';
  } else {
    // Commande en USD (ou autre) -> afficher équivalent CDF
    targetCode = 'CDF';
  }
  
  const amount = convertCurrency(props.order.total_amount, orderCurrency, targetCode);
  
  let formatted;
  if (targetCode === 'CDF') {
    formatted = new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FC';
  } else {
    try {
      formatted = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: targetCode,
      }).format(amount);
    } catch (e) {
      formatted = amount.toFixed(2) + ' ' + targetCode;
    }
  }
  
  return { code: targetCode, amount, formatted };
});

// Format rate display (ex: "2800 CDF")
const formatRate = (rate) => {
  const currencyCode = rate.currency?.code || rate.code;
  const rateValue = parseFloat(rate.rate);
  
  if (currencyCode === 'CDF') {
    return new Intl.NumberFormat('fr-FR').format(Math.round(rateValue)) + ' FC';
  }
  
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(rateValue) + ' ' + currencyCode;
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
          font-family: 'Courier New', Consolas, monospace; 
          font-size: 10px; 
          line-height: 1.3;
          width: 48mm; 
          padding: 1mm;
          background: white;
          color: #000;
          font-weight: bold;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        
        /* Reset pour l'impression */
        .receipt {
          width: 100%;
          background: white;
          color: black;
          font-family: 'Courier New', Consolas, monospace;
          font-size: 12px;
        }
        
        /* Classes de texte */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-semibold { font-weight: 700; }
        .font-medium { font-weight: 500; }
        .uppercase { text-transform: uppercase; }
        
        /* Tailles de texte POS Giga 360 48mm */
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 11px; }
        .text-\\[8px\\] { font-size: 7px; }
        .text-\\[9px\\] { font-size: 8px; }
        .text-\\[10px\\] { font-size: 9px; }
        .text-\\[11px\\] { font-size: 10px; }
        
        /* Flexbox */
        .flex { display: flex; }
        .flex-1 { flex: 1; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .items-center { align-items: center; }
        .gap-px { gap: 1px; }
        .gap-3 { gap: 12px; }
        
        /* Couleurs de texte - TOUT EN NOIR pour l'impression */
        .text-black { color: #000 !important; }
        .text-gray-400 { color: #000 !important; }
        .text-gray-500 { color: #000 !important; }
        .text-gray-600 { color: #000 !important; }
        
        /* Forcer tout le texte en noir */
        body, p, span, div {
          color: #000 !important;
        }
        
        /* Bordures */
        .border-b { border-bottom-width: 1px; }
        .border-t { border-top-width: 1px; }
        .border-dashed { border-style: dashed; }
        .border-double { border-style: double; border-width: 3px 0 0 0; }
        .border-gray-300 { border-color: #d1d5db; }
        .border-gray-400 { border-color: #9ca3af; }
        
        /* Espacements */
        .p-4 { padding: 16px; }
        .pb-2 { padding-bottom: 8px; }
        .pb-3 { padding-bottom: 12px; }
        .pt-2 { padding-top: 8px; }
        .pt-3 { padding-top: 12px; }
        .pl-2 { padding-left: 8px; }
        .pr-2 { padding-right: 8px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        
        /* Dimensions POS Giga */
        .h-10 { height: 32px; }
        .w-10 { width: 32px; }
        .h-8 { height: 28px; }
        .w-8 { width: 28px; }
        .h-4 { height: 14px; }
        .w-4 { width: 14px; }
        .rounded { border-radius: 4px; }
        .rounded-lg { border-radius: 8px; }
        
        /* Utilitaires */
        .truncate { 
          overflow: hidden; 
          text-overflow: ellipsis; 
          white-space: nowrap; 
          max-width: 90px; 
        }
        .bg-white { background: white; }
        .bg-black { background: black; }
        .bg-gray-900 { background: #111827; }
        .text-white { color: white; }
        
        /* Image / Logo pour POS Giga 58mm */
        img {
          width: 32px !important;
          height: 32px !important;
          max-width: 32px !important;
          max-height: 32px !important;
          object-fit: cover !important;
          border-radius: 3px !important;
          display: block;
          margin: 0 auto;
        }
        
        img.h-8,
        img.w-8 {
          width: 32px !important;
          height: 32px !important;
        }
        
        img.rounded {
          border-radius: 4px !important;
        }
        
        /* Cacher les boutons d'action */
        .print\\:hidden,
        button {
          display: none !important;
        }
        
        /* Style pour le code-barres */
        .barcode-bar {
          background: black;
          height: 20px;
          display: inline-block;
        }
        
        @media print {
          @page { 
            margin: 0; 
            size: 48mm auto; 
          }
          body { 
            width: 48mm; 
            padding: 0.5mm;
            font-size: 10px;
          }
          .print\\:hidden {
            display: none !important;
          }
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
    width: 48mm !important;
    padding: 0;
    font-size: 10px;
  }
}
</style>
