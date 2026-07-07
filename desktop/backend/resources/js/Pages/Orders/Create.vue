<template>
  <MainLayout page-title="Nouvelle commande" page-description="Créer une nouvelle commande">
    <!-- Exchange Rate Banner -->
    <div v-if="exchangeRates && exchangeRates.length > 0" class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 rounded-full p-2">
            <CurrencyDollarIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-700">Taux de change du jour</p>
            <p class="text-xs text-gray-500">{{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div v-for="rate in exchangeRates" :key="rate.id" class="bg-white rounded-lg px-4 py-2 shadow-sm border border-blue-100">
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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <div class="flex gap-2 overflow-x-auto pb-1">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="selectedCategory = category.id"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap"
              :class="selectedCategory === category.id
                ? 'bg-blue-600 text-white shadow-sm'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            @click="product.status === 'available' && addToCart(product)"
            class="bg-white rounded-xl border border-gray-200 overflow-hidden cursor-pointer hover:shadow-md hover:border-blue-300 transition-all"
            :class="{ 'opacity-50 cursor-not-allowed': product.status !== 'available' }"
          >
            <div class="aspect-square bg-gray-100 relative flex items-center justify-center">
              <img v-if="product.image" :src="`${storageUrl}/${product.image}`" :alt="product.name" class="w-full h-full object-cover" />
              <PhotoIcon v-else class="w-12 h-12 text-gray-300" />
              <Badge v-if="product.status !== 'available'" variant="warning" class="absolute top-2 right-2">
                {{ product.status === 'out_of_stock' ? 'Rupture' : 'Indispo.' }}
              </Badge>
            </div>
            <div class="p-3">
              <h3 class="font-medium text-gray-900 truncate">{{ product.name }}</h3>
              <p class="text-blue-600 font-semibold">{{ formatProductPrice(product) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Cart -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sticky top-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Commande</h3>
          <div class="space-y-4">
            <Select
              v-model="form.server_id"
              label="Serveur"
              :options="serverOptions"
              required
              :error="form.errors.server_id"
            />

            <div class="divide-y max-h-96 overflow-y-auto -mx-1">
              <div v-for="(item, index) in cart" :key="index" class="py-3 flex items-center justify-between gap-2">
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm truncate">{{ item.product.name }}</p>
                  <p class="text-xs text-gray-500">{{ formatProductPrice(item.product) }} x {{ item.quantity }}</p>
                  <p v-if="getProductCurrency(item.product) !== cartCurrency" class="text-xs text-blue-600 font-medium">
                    = {{ formatPrice(getItemTotalInCartCurrency(item), cartCurrency) }}
                  </p>
                  <p v-else class="text-xs text-blue-600 font-medium">
                    = {{ formatPrice(item.product.selling_price * item.quantity, cartCurrency) }}
                  </p>
                </div>
                <div class="flex items-center gap-1">
                  <button @click="decrementItem(index)" class="w-7 h-7 rounded-md bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors">
                    <MinusIcon class="w-3.5 h-3.5" />
                  </button>
                  <span class="w-6 text-center text-sm font-medium">{{ item.quantity }}</span>
                  <button @click="incrementItem(index)" class="w-7 h-7 rounded-md bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors">
                    <PlusIcon class="w-3.5 h-3.5" />
                  </button>
                  <button @click="removeItem(index)" class="w-7 h-7 rounded-md hover:bg-red-50 flex items-center justify-center text-red-500 transition-colors">
                    <TrashIcon class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            <EmptyState v-if="cart.length === 0" title="Panier vide" description="Ajoutez des produits" class="py-8" />

            <div v-if="cart.length > 0" class="border-t pt-4 space-y-2">
              <div class="flex justify-between text-lg font-semibold">
                <span>Total</span>
                <span class="text-blue-600">{{ formatPrice(total, cartCurrency) }}</span>
              </div>
              <div v-if="equivalentCurrencies.length > 0" class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-2">Équivalent :</p>
                <div v-for="curr in equivalentCurrencies" :key="'equiv-' + curr.code" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ curr.code }}</span>
                  <span class="font-semibold text-gray-800">{{ formatPrice(getTotalInCurrency(curr.code), curr.code) }}</span>
                </div>
              </div>
            </div>

            <div class="flex gap-2 pt-2">
              <Button variant="secondary" class="flex-1" :disabled="cart.length === 0" @click="clearCart">Vider</Button>
              <Button variant="primary" class="flex-1" :disabled="cart.length === 0 || !form.server_id" :loading="form.processing" @click="submitOrder">Commander</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Button, Card, Badge, Select, EmptyState } from '@/Components';
import { CurrencyDollarIcon, PhotoIcon, PlusIcon, MinusIcon, TrashIcon } from '@heroicons/vue/24/outline';

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

const getProductCurrency = (product) => {
  if (product.currency?.code) return product.currency.code;
  if (product.currency_id) {
    const rate = props.exchangeRates?.find(r => r.currency_id === product.currency_id);
    if (rate?.currency?.code) return rate.currency.code;
  }
  return 'USD';
};

const cartCurrency = computed(() => {
  if (cart.value.length === 0) return null;
  return getProductCurrency(cart.value[0].product);
});

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return props.products || [];
  return props.products?.filter(p => p.category_id === selectedCategory.value) || [];
});

const getExchangeRate = (currencyCode) => {
  const rate = props.exchangeRates?.find(r => r.currency?.code === currencyCode);
  return rate ? parseFloat(rate.rate) : 1;
};

const convertCurrency = (amount, fromCurrency, toCurrency) => {
  if (fromCurrency === toCurrency) return parseFloat(amount);
  const fromRate = getExchangeRate(fromCurrency);
  const toRate = getExchangeRate(toCurrency);
  const amountInUSD = parseFloat(amount) / fromRate;
  return amountInUSD * toRate;
};

const getItemTotalInCartCurrency = (item) => {
  const productCurrency = getProductCurrency(item.product);
  const itemTotal = item.product.selling_price * item.quantity;
  if (!cartCurrency.value || productCurrency === cartCurrency.value) return itemTotal;
  return convertCurrency(itemTotal, productCurrency, cartCurrency.value);
};

const total = computed(() => {
  return cart.value.reduce((sum, item) => sum + getItemTotalInCartCurrency(item), 0);
});

const getTotalInCurrency = (targetCurrency) => {
  if (!cartCurrency.value || cartCurrency.value === targetCurrency) return total.value;
  return convertCurrency(total.value, cartCurrency.value, targetCurrency);
};

const availableCurrencies = computed(() => {
  const currencies = [{ code: 'USD', rate: 1 }];
  props.exchangeRates?.forEach(rate => {
    if (rate.currency?.code && rate.currency.code !== 'USD') {
      currencies.push({ code: rate.currency.code, rate: parseFloat(rate.rate) });
    }
  });
  return currencies;
});

const equivalentCurrencies = computed(() => {
  if (!cartCurrency.value) return [];
  return availableCurrencies.value.filter(c => c.code !== cartCurrency.value);
});

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

const formatProductPrice = (product) => {
  const currencyCode = getProductCurrency(product);
  const price = parseFloat(product.selling_price) || 0;
  return formatPrice(price, currencyCode);
};

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
    cart.value.push({ product, quantity: 1 });
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
  form.cart_currency = cartCurrency.value;
  form.total_amount = total.value;
  form.post('/orders', {
    onSuccess: () => {
      clearCart();
    },
  });
};
</script>
