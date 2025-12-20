<template>
  <MainLayout page-title="Nouvelle commande" page-description="Créer une nouvelle commande">
    <!-- Exchange Rate Banner -->
    <div v-if="exchangeRates && exchangeRates.length > 0" class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 rounded-full p-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-700">Taux de change du jour</p>
            <p class="text-xs text-gray-500">{{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div 
            v-for="rate in exchangeRates" 
            :key="rate.id"
            class="bg-white rounded-lg px-4 py-2 shadow-sm border border-blue-100"
          >
            <p class="text-xs text-gray-500">1 USD =</p>
            <p class="text-lg font-bold text-blue-600">{{ formatRate(rate) }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Products Selection -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Categories Tabs -->
        <Card>
          <div class="flex gap-2 overflow-x-auto pb-2">
            <Button
              v-for="category in categories"
              :key="category.id"
              :variant="selectedCategory === category.id ? 'primary' : 'secondary'"
              size="sm"
              @click="selectedCategory = category.id"
            >
              {{ category.name }}
            </Button>
          </div>
        </Card>

        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden cursor-pointer hover:shadow-md transition-shadow"
            :class="{ 'opacity-50 cursor-not-allowed': product.status !== 'available' }"
            @click="product.status === 'available' && addToCart(product)"
          >
            <div class="aspect-square bg-gray-100 relative">
              <img
                v-if="product.image"
                :src="`${storageUrl}/${product.image}`"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <Badge
                v-if="product.status !== 'available'"
                variant="warning"
                class="absolute top-2 right-2"
              >
                {{ product.status === 'out_of_stock' ? 'Rupture' : 'Indispo.' }}
              </Badge>
            </div>
            <div class="p-3">
              <h3 class="font-medium text-gray-900 truncate">{{ product.name }}</h3>
              <p class="text-primary-600 font-semibold">
                {{ formatProductPrice(product) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Cart -->
      <div class="lg:col-span-1">
        <Card title="Commande" class="sticky top-4">
          <div class="space-y-4">
            <!-- Server Selection -->
            <Select
              v-model="form.server_id"
              label="Serveur"
              :options="serverOptions"
              required
              :error="form.errors.server_id"
            />

            <!-- Cart Items -->
            <div class="divide-y max-h-96 overflow-y-auto">
              <div
                v-for="(item, index) in cart"
                :key="index"
                class="py-3 flex items-center justify-between"
              >
                <div class="flex-1">
                  <p class="font-medium">{{ item.product.name }}</p>
                  <p class="text-sm text-gray-500">
                    {{ formatProductPrice(item.product) }} × {{ item.quantity }}
                  </p>
                  <!-- Afficher la conversion si la devise du produit != devise du panier -->
                  <p v-if="getProductCurrency(item.product) !== cartCurrency" class="text-xs text-primary-600 font-medium">
                    = {{ formatPrice(getItemTotalInCartCurrency(item), cartCurrency) }}
                  </p>
                  <p v-else class="text-xs text-primary-600 font-medium">
                    = {{ formatPrice(item.product.selling_price * item.quantity, cartCurrency) }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <Button variant="ghost" size="sm" @click="decrementItem(index)">
                    -
                  </Button>
                  <span class="w-8 text-center">{{ item.quantity }}</span>
                  <Button variant="ghost" size="sm" @click="incrementItem(index)">
                    +
                  </Button>
                  <Button variant="ghost" size="sm" @click="removeItem(index)">
                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </Button>
                </div>
              </div>
            </div>

            <EmptyState
              v-if="cart.length === 0"
              title="Panier vide"
              description="Ajoutez des produits"
              class="py-8"
            />

            <!-- Total -->
            <div v-if="cart.length > 0" class="border-t pt-4 space-y-2">
              <!-- Total dans la devise du premier produit -->
              <div class="flex justify-between text-lg font-semibold">
                <span>Total</span>
                <span class="text-primary-600">{{ formatPrice(total, cartCurrency) }}</span>
              </div>
              
              <!-- Équivalent en autres devises -->
              <div v-if="equivalentCurrencies.length > 0" class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-2">Équivalent :</p>
                <div 
                  v-for="curr in equivalentCurrencies" 
                  :key="'equiv-' + curr.code"
                  class="flex justify-between text-sm"
                >
                  <span class="text-gray-600">{{ curr.code }}</span>
                  <span class="font-semibold text-gray-800">{{ formatPrice(getTotalInCurrency(curr.code), curr.code) }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <Button 
                variant="secondary" 
                class="flex-1"
                :disabled="cart.length === 0"
                @click="clearCart"
              >
                Vider
              </Button>
              <Button 
                variant="primary" 
                class="flex-1"
                :disabled="cart.length === 0 || !form.server_id"
                :loading="form.processing"
                @click="submitOrder"
              >
                Commander
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Select, EmptyState } from '@/Components';

// URL de base pour les images storage
const storageUrl = window.__STORAGE_URL__ || '/storage';

const props = defineProps({
  categories: Array,
  products: Array,
  servers: Array,
  currentSession: Object,
  exchangeRates: Array,
  settings: Object,
});

const selectedCategory = ref(props.categories?.[0]?.id || null);
const cart = ref([]);

const form = useForm({
  server_id: '',
  items: [],
  cart_currency: '',
  total_amount: 0,
});

const serverOptions = props.servers?.map(s => ({
  value: s.id,
  label: s.name,
})) || [];

// Obtenir la devise d'un produit
const getProductCurrency = (product) => {
  // Vérifier si le produit a une devise explicite via relation
  if (product.currency?.code) {
    return product.currency.code;
  }
  // Vérifier si currency_id existe et chercher dans les devises disponibles
  if (product.currency_id) {
    const rate = props.exchangeRates?.find(r => r.currency_id === product.currency_id);
    if (rate?.currency?.code) {
      return rate.currency.code;
    }
  }
  // Fallback: USD
  return 'USD';
};

// Devise du panier = devise du PREMIER produit ajouté
const cartCurrency = computed(() => {
  if (cart.value.length === 0) return null;
  return getProductCurrency(cart.value[0].product);
});

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return props.products || [];
  return props.products?.filter(p => p.category_id === selectedCategory.value) || [];
});

// Obtenir le taux de change entre deux devises
// Les taux sont stockés comme: 1 USD = X [devise] (ex: 1 USD = 2800 CDF)
const getExchangeRate = (currencyCode) => {
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

// Convertir un montant d'une devise vers une autre
const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  
  const fromRate = getExchangeRate(fromCurrency); // Ex: CDF = 2800
  const toRate = getExchangeRate(toCurrency);     // Ex: USD = 1
  
  // Convertir via USD comme devise pivot
  // Si fromCurrency est CDF (rate=2800): montant / 2800 = montant en USD
  // Si toCurrency est USD (rate=1): pas besoin de multiplier
  // Si toCurrency est CDF (rate=2800): montant * 2800
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

// Obtenir le total d'un item converti dans la devise du panier
const getItemTotalInCartCurrency = (item) => {
  const productCurrency = getProductCurrency(item.product);
  const itemTotal = item.product.selling_price * item.quantity;
  
  if (!cartCurrency.value || productCurrency === cartCurrency.value) {
    return itemTotal;
  }
  
  return convertCurrency(itemTotal, productCurrency, cartCurrency.value);
};

// Total du panier dans la devise du premier produit
const total = computed(() => {
  return cart.value.reduce((sum, item) => {
    return sum + getItemTotalInCartCurrency(item);
  }, 0);
});

// Calculer l'équivalent du total dans une autre devise
const getTotalInCurrency = (targetCurrency) => {
  if (!cartCurrency.value || cartCurrency.value === targetCurrency) {
    return total.value;
  }
  return convertCurrency(total.value, cartCurrency.value, targetCurrency);
};

// Liste des devises disponibles (pour afficher les équivalents)
const availableCurrencies = computed(() => {
  const currencies = [];
  // Ajouter USD comme base
  currencies.push({ code: 'USD', rate: 1 });
  // Ajouter les devises des taux de change
  props.exchangeRates?.forEach(rate => {
    if (rate.currency?.code && rate.currency.code !== 'USD') {
      currencies.push({ code: rate.currency.code, rate: parseFloat(rate.rate) });
    }
  });
  return currencies;
});

// Devises pour l'équivalent (exclure la devise du panier)
const equivalentCurrencies = computed(() => {
  if (!cartCurrency.value) return [];
  return availableCurrencies.value.filter(c => c.code !== cartCurrency.value);
});

// Formater un prix avec une devise spécifique
const formatPrice = (price, currencyCode = 'USD') => {
  const currency = currencyCode || 'USD';
  try {
    if (currency === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(price) || 0)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(parseFloat(price) || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
  }
};

// Formater le prix d'un produit avec SA propre devise
const formatProductPrice = (product) => {
  const currencyCode = getProductCurrency(product);
  const price = parseFloat(product.selling_price) || 0;
  return formatPrice(price, currencyCode);
};

// Formater le taux de change
const formatRate = (rate) => {
  const currencyCode = rate.currency?.code || rate.code || 'CDF';
  const rateValue = parseFloat(rate.rate);
  
  if (currencyCode === 'CDF') {
    return new Intl.NumberFormat('fr-FR').format(Math.round(rateValue)) + ' FC';
  }
  
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(rateValue) + ' ' + currencyCode;
};

const addToCart = (product) => {
  const existingIndex = cart.value.findIndex(item => item.product.id === product.id);
  if (existingIndex !== -1) {
    cart.value[existingIndex].quantity++;
  } else {
    cart.value.push({
      product,
      quantity: 1,
    });
  }
};

const incrementItem = (index) => {
  cart.value[index].quantity++;
};

const decrementItem = (index) => {
  if (cart.value[index].quantity > 1) {
    cart.value[index].quantity--;
  } else {
    removeItem(index);
  }
};

const removeItem = (index) => {
  cart.value.splice(index, 1);
};

const clearCart = () => {
  cart.value = [];
};

const submitOrder = () => {
  form.items = cart.value.map(item => ({
    product_id: item.product.id,
    quantity: item.quantity,
    unit_price: item.product.selling_price,
    currency: getProductCurrency(item.product),
  }));
  
  // Ajouter la devise du panier et le total
  form.cart_currency = cartCurrency.value;
  form.total_amount = total.value;
  
  form.post('/orders', {
    onSuccess: () => {
      clearCart();
    },
  });
};
</script>
