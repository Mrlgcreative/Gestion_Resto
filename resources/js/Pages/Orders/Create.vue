<template>
  <MainLayout page-title="Nouvelle commande" page-description="Créer une nouvelle commande">
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
                :src="`/storage/${product.image}`"
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
              <p class="text-primary-600 font-semibold">{{ formatPrice(product.selling_price) }}</p>
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
                  <p class="text-sm text-gray-500">{{ formatPrice(item.product.selling_price) }} × {{ item.quantity }}</p>
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
            <div v-if="cart.length > 0" class="border-t pt-4">
              <div class="flex justify-between text-lg font-semibold">
                <span>Total</span>
                <span class="text-primary-600">{{ formatPrice(total) }}</span>
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

const props = defineProps({
  categories: Array,
  products: Array,
  servers: Array,
  currentSession: Object,
});

const selectedCategory = ref(props.categories?.[0]?.id || null);
const cart = ref([]);

const form = useForm({
  server_id: '',
  items: [],
});

const serverOptions = props.servers?.map(s => ({
  value: s.id,
  label: s.name,
})) || [];

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return props.products || [];
  return props.products?.filter(p => p.category_id === selectedCategory.value) || [];
});

const total = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.product.selling_price * item.quantity), 0);
});

const formatPrice = (price) => {
  const currency = props.currentSession?.currency || 'USD';
  try {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(parseFloat(price) || 0);
  } catch (e) {
    return `${parseFloat(price || 0).toFixed(2)} ${currency}`;
  }
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
  }));
  
  form.post('/orders', {
    onSuccess: () => {
      clearCart();
    },
  });
};
</script>
