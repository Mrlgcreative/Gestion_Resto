<template>
    <div class="receipt-wrapper">
        <div
            ref="receiptRef"
            class="receipt bg-white text-black font-sans"
            style="width: 165px; font-size: 9px"
        >
            <!-- Header -->
            <div class="text-center pb-2 mb-2">
                <div v-if="settings?.logo" class="flex justify-center mb-1">
                    <img
                        :src="`${storageUrl}/${settings.logo}`"
                        class="h-6 w-6 rounded"
                    />
                </div>
                <p class="font-bold text-[10px] uppercase">
                    {{ settings?.restaurant_name || "Restaurant" }}
                </p>
                <p v-if="settings?.address" class="text-[8px] text-gray-600">
                    {{ settings.address }}
                </p>
                <p v-if="fullLocation" class="text-[7px] text-gray-500">
                    {{ fullLocation }}
                </p>
                <p v-if="settings?.phone" class="text-[8px] text-gray-600">
                    {{ settings.phone }}
                </p>
                <p v-if="settings?.rccm" class="text-[7px] text-gray-500">
                    RCCM: {{ settings.rccm }}
                </p>
            </div>

            <!-- Order Info -->
            <div class="bg-gray-50 rounded px-1 py-1 mb-2 text-[8px]">
                <div class="flex justify-between">
                    <span>Fact:</span>
                    <span class="font-bold"
                        >#{{ String(order.id).padStart(5, "0") }}</span
                    >
                </div>
                <div class="flex justify-between">
                    <span>{{ formatDate(order.created_at) }}</span>
                    <span>{{ formatTime(order.created_at) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Caiss:</span>
                    <span>{{
                        (
                            order.session?.user?.name ||
                            order.user?.name ||
                            "-"
                        ).substring(0, 10)
                    }}</span>
                </div>
            </div>

            <!-- Items Header -->
            <div class="bg-gray-800 text-white rounded-sm px-1 py-0.5 mb-1">
                <div class="flex text-[7px] font-semibold">
                    <span class="w-[20px] text-center">QTE</span>
                    <span class="flex-1 text-center">DESIGNATION</span>
                    <span class="w-[35px] text-right">P.UNIT</span>
                </div>
            </div>

            <!-- Items -->
            <div class="pb-2 mb-2">
                <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="mb-0.5 text-[8px]"
                >
                    <div class="flex">
                        <span class="w-[20px] text-center">{{
                            item.quantity
                        }}</span>
                        <span class="flex-1 truncate px-0.5">{{
                            item.product?.name?.substring(0, 12)
                        }}</span>
                        <span class="w-[35px] text-right font-bold">{{
                            formatItemPrice(item.unit_price, item.product)
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="mb-2 text-[9px]">
                <div class="flex justify-between font-bold">
                    <span>TOTAL FC:</span>
                    <span>{{ formatPriceCDF(calculatedSubtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total $:</span>
                    <span>{{ formatPriceUSD(calculatedSubtotal) }}</span>
                </div>
                <div class="text-[7px] text-gray-500 text-center mt-1">
                    1$ = {{ currentExchangeRate }} FC
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center pt-2 mt-2">
                <p class="font-semibold text-[9px]">Merci de votre visite !</p>
                <p class="text-[7px] text-gray-400">
                    {{ formatDateTime(new Date()) }}
                </p>
            </div>
        </div>

        <!-- Actions (hidden in print) -->
        <div class="mt-4 flex justify-center gap-3 print:hidden">
            <button
                @click="printReceipt"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                Imprimer
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

// URL de base pour les images storage
const storageUrl = window.__STORAGE_URL__ || "/storage";

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
        default: "USD",
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
    return parts.length > 0 ? parts.join(", ") : "";
});

// Devise de la commande
const orderCurrency = computed(
    () => props.order.currency || props.defaultCurrency
);

// Obtenir la devise d'un produit
const getProductCurrency = (product) => {
    return product?.currency?.code || "USD";
};

// Obtenir le taux de change pour une devise (par rapport à USD)
const getExchangeRate = (currencyCode) => {
    if (currencyCode === "USD") return 1;
    const rate = props.exchangeRates?.find(
        (r) => r.currency?.code === currencyCode
    );
    return rate ? parseFloat(rate.rate) : 1;
};

// Convertir un montant de la devise du produit vers la devise du panier
const convertToCartCurrency = (amount, product) => {
    const fromCurrency = getProductCurrency(product);
    const toCurrency = orderCurrency.value;

    if (fromCurrency === toCurrency) return parseFloat(amount);

    // Conversion via USD comme pivot
    const fromRate = getExchangeRate(fromCurrency);
    const toRate = getExchangeRate(toCurrency);
    const amountInUSD = parseFloat(amount) / fromRate;
    return amountInUSD * toRate;
};

// Calculer le sous-total en convertissant tous les articles dans la devise du panier
const calculatedSubtotal = computed(() => {
    if (!props.order.items) return 0;
    return props.order.items.reduce((sum, item) => {
        const itemTotal = item.unit_price * item.quantity;
        return sum + convertToCartCurrency(itemTotal, item.product);
    }, 0);
});

// Calculs (ancien subtotal gardé pour compatibilité)
const subtotal = computed(() => {
    return (
        props.order.items?.reduce(
            (sum, item) => sum + parseFloat(item.total_price),
            0
        ) || 0
    );
});

const taxAmount = computed(() => {
    if (!props.settings?.tax_rate) return 0;
    return calculatedSubtotal.value * (props.settings.tax_rate / 100);
});

const serviceAmount = computed(() => {
    if (!props.settings?.service_charge) return 0;
    return calculatedSubtotal.value * (props.settings.service_charge / 100);
});

// Mode de paiement
const paymentMethod = computed(() => {
    const payment = props.order.payment || props.order.payments?.[0];
    if (!payment) return "-";

    const methods = {
        cash: "Espèces",
        card: "Carte",
        mobile: "Mobile",
    };
    return methods[payment.method] || payment.method;
});

// Formater le prix d'un article dans la devise de son produit
const formatItemPrice = (price, product) => {
    const currency = getProductCurrency(product);
    try {
        if (currency === "CDF") {
            return (
                new Intl.NumberFormat("fr-FR").format(
                    Math.round(parseFloat(price) || 0)
                ) + " FC"
            );
        }
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: currency,
        }).format(parseFloat(price) || 0);
    } catch (e) {
        return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
    }
};

// Formatters - prix dans la devise de la commande
const formatPrice = (amount) => {
    const currency = orderCurrency.value;
    try {
        if (currency === "CDF") {
            return (
                new Intl.NumberFormat("fr-FR").format(
                    Math.round(parseFloat(amount) || 0)
                ) + " FC"
            );
        }
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch (e) {
        return `${parseFloat(amount || 0).toFixed(2)} ${currency}`;
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
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
    const convertedAmount = convertCurrency(
        amount,
        props.defaultCurrency,
        currencyCode
    );

    // Pour CDF, pas de décimales
    if (currencyCode === "CDF") {
        return (
            new Intl.NumberFormat("fr-FR").format(Math.round(convertedAmount)) +
            " FC"
        );
    }

    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: currencyCode,
    }).format(convertedAmount);
};

// Équivalent à afficher (si CDF -> montrer USD, si USD -> montrer CDF)
const equivalentDisplay = computed(() => {
    const currencyCode = orderCurrency.value;
    let targetCode;

    if (currencyCode === "CDF") {
        // Commande en CDF -> afficher équivalent USD
        targetCode = "USD";
    } else {
        // Commande en USD (ou autre) -> afficher équivalent CDF
        targetCode = "CDF";
    }

    // Utiliser le sous-total calculé (avec conversions)
    const amount = convertCurrency(
        calculatedSubtotal.value,
        currencyCode,
        targetCode
    );

    let formatted;
    if (targetCode === "CDF") {
        formatted =
            new Intl.NumberFormat("fr-FR").format(Math.round(amount)) + " FC";
    } else {
        try {
            formatted = new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: targetCode,
            }).format(amount);
        } catch (e) {
            formatted = amount.toFixed(2) + " " + targetCode;
        }
    }

    return { code: targetCode, amount, formatted };
});

// Format rate display (ex: "2800 CDF")
const formatRate = (rate) => {
    const currencyCode = rate.currency?.code || rate.code;
    const rateValue = parseFloat(rate.rate);

    if (currencyCode === "CDF") {
        return (
            new Intl.NumberFormat("fr-FR").format(Math.round(rateValue)) + " FC"
        );
    }

    return (
        new Intl.NumberFormat("fr-FR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4,
        }).format(rateValue) +
        " " +
        currencyCode
    );
};

// Taux de change actuel CDF
const currentExchangeRate = computed(() => {
    const cdfRate = props.exchangeRates?.find(
        (r) => r.currency?.code === "CDF"
    );
    if (cdfRate) {
        return new Intl.NumberFormat("fr-FR").format(
            Math.round(parseFloat(cdfRate.rate))
        );
    }
    return "2800"; // Valeur par défaut
});

// Formater le prix en CDF (Francs Congolais)
const formatPriceCDF = (amount) => {
    const currencyCode = orderCurrency.value;
    let amountInCDF;

    if (currencyCode === "CDF") {
        amountInCDF = parseFloat(amount);
    } else {
        // Convertir vers CDF
        amountInCDF = convertCurrency(amount, currencyCode, "CDF");
    }

    return (
        new Intl.NumberFormat("fr-FR").format(Math.round(amountInCDF)) + " FC"
    );
};

// Formater le prix en USD
const formatPriceUSD = (amount) => {
    const currencyCode = orderCurrency.value;
    let amountInUSD;

    if (currencyCode === "USD") {
        amountInUSD = parseFloat(amount);
    } else {
        // Convertir vers USD
        amountInUSD = convertCurrency(amount, currencyCode, "USD");
    }

    try {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amountInUSD);
    } catch (e) {
        return amountInUSD.toFixed(2) + " $";
    }
};

// Générer le HTML d'une facture avec un label (Client ou Maison)
const generateReceiptHTML = (label) => {
    const receiptContent = receiptRef.value.innerHTML;
    // Insérer le label après le header
    const labelHTML = `
    <div style="text-align: center; border: 2px solid #000; padding: 4px; margin: 8px 0; background: ${
        label === "MAISON" ? "#f0f0f0" : "#fff"
    };">
      <span style="font-weight: bold; font-size: 12px; letter-spacing: 2px;">*** ${label} ***</span>
    </div>
  `;

    // Insérer le label juste après le premier border-b (après le header)
    const headerEndIndex = receiptContent.indexOf("<!-- Order Info -->");
    if (headerEndIndex !== -1) {
        return (
            receiptContent.slice(0, headerEndIndex) +
            labelHTML +
            receiptContent.slice(headerEndIndex)
        );
    }

    // Fallback: ajouter au début
    return labelHTML + receiptContent;
};

// CSS commun pour l'impression
const getPrintStyles = () => `
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif; 
    font-size: 10px; 
    line-height: 1.4;
    width: 48mm; 
    padding: 1mm;
    background: white;
    color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  /* Reset pour l'impression */
  .receipt {
    width: 100%;
    background: white;
    color: black;
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
    font-size: 12px;
    page-break-after: always;
  }
  
  .receipt:last-child {
    page-break-after: avoid;
  }
  
  /* Séparateur entre les deux factures */
  .receipt-separator {
    margin: 10px 0;
    padding-top: 10px;
    page-break-before: always;
  }
  
  /* Classes de texte */
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .font-bold { font-weight: bold; }
  .font-semibold { font-weight: 600; }
  .font-medium { font-weight: 500; }
  .uppercase { text-transform: uppercase; }
  
  /* Tailles de texte POS Giga 360 48mm */
  .text-xs { font-size: 10px; }
  .text-sm { font-size: 11px; }
  .text-\\[7px\\] { font-size: 7px; }
  .text-\\[8px\\] { font-size: 8px; }
  .text-\\[9px\\] { font-size: 9px; }
  .text-\\[10px\\] { font-size: 10px; }
  .text-\\[11px\\] { font-size: 11px; }
  
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
  .text-white { color: #fff !important; }
  .text-gray-400, .text-gray-500, .text-gray-600 { color: #333 !important; }
  
  /* Backgrounds */
  .bg-white { background: white; }
  .bg-gray-50 { background: #f9f9f9; }
  .bg-gray-800 { background: #1f2937; }
  .bg-gray-900 { background: #111827; }
  
  /* Pas de bordures pointillées */
  .border-b, .border-t, .border-dashed { border: none !important; }
  
  /* Espacements */
  .p-4 { padding: 16px; }
  .pb-2 { padding-bottom: 8px; }
  .pb-3 { padding-bottom: 12px; }
  .pt-2 { padding-top: 8px; }
  .pt-3 { padding-top: 12px; }
  .pl-2 { padding-left: 8px; }
  .pr-2 { padding-right: 8px; }
  .px-1 { padding-left: 4px; padding-right: 4px; }
  .py-1 { padding-top: 4px; padding-bottom: 4px; }
  .py-0\\.5 { padding-top: 2px; padding-bottom: 2px; }
  .mb-1 { margin-bottom: 4px; }
  .mb-2 { margin-bottom: 8px; }
  .mb-3 { margin-bottom: 12px; }
  .mt-1 { margin-top: 4px; }
  .mt-2 { margin-top: 8px; }
  .mt-3 { margin-top: 12px; }
  
  /* Largeurs pour le tableau */
  .w-6 { width: 20px; }
  .w-16 { width: 50px; }
  .w-\\[20px\\] { width: 20px; }
  .w-\\[35px\\] { width: 35px; }
  
  /* Dimensions */
  .h-10 { height: 32px; }
  .w-10 { width: 32px; }
  .h-8 { height: 28px; }
  .w-8 { width: 28px; }
  .h-6 { height: 20px; }
  .w-6 { width: 20px; }
  .h-4 { height: 14px; }
  .w-4 { width: 14px; }
  .rounded { border-radius: 4px; }
  .rounded-sm { border-radius: 2px; }
  .rounded-lg { border-radius: 8px; }
  
  /* Utilitaires */
  .truncate { 
    overflow: hidden; 
    text-overflow: ellipsis; 
    white-space: nowrap; 
    max-width: 70px; 
  }
  
  /* Image / Logo */
  img {
    width: 28px !important;
    height: 28px !important;
    max-width: 28px !important;
    max-height: 28px !important;
    object-fit: cover !important;
    border-radius: 4px !important;
    display: block;
    margin: 0 auto;
  }
  
  /* Cacher les boutons d'action */
  .print\\:hidden,
  button {
    display: none !important;
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
    .receipt {
      page-break-after: always;
    }
    .receipt:last-child {
      page-break-after: avoid;
    }
    /* Pas de bordures à l'impression */
    .border-b, .border-t, .border-dashed, .border-gray-400 {
      border: none !important;
    }
  }
`;

// Imprimer une seule facture avec un label
const printSingleReceipt = (label) => {
    return new Promise((resolve) => {
        const printWindow = window.open("", "_blank");
        const receiptContent = generateReceiptHTML(label);

        printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Reçu #${props.order.id} - ${label}</title>
        <style>${getPrintStyles()}</style>
      </head>
      <body>
        <div class="receipt">
          ${receiptContent}
        </div>
      </body>
      </html>
    `);
        printWindow.document.close();
        printWindow.focus();

        setTimeout(() => {
            printWindow.print();
            // Attendre que l'impression soit terminée ou annulée
            printWindow.onafterprint = () => {
                printWindow.close();
                resolve();
            };
            // Fallback si onafterprint n'est pas supporté
            setTimeout(() => {
                if (!printWindow.closed) {
                    printWindow.close();
                }
                resolve();
            }, 1000);
        }, 250);
    });
};

// Print - Imprimer deux factures une par une (Client puis Maison)
const printReceipt = async () => {
    // 1. Imprimer la facture CLIENT
    await printSingleReceipt("CLIENT");

    // 2. Petite pause puis imprimer la facture MAISON
    setTimeout(async () => {
        await printSingleReceipt("MAISON");
    }, 500);
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
