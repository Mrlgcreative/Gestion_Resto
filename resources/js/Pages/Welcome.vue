<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
    <h1 class="text-6xl font-bold text-primary-600 mb-4">🍽️ {{ appName }}</h1>
    <p class="text-xl text-gray-600 mb-8">Application de gestion de restaurant</p>
    
    <div class="flex gap-4">
      <Button variant="primary" size="lg" @click="goToLogin">
        Se connecter
      </Button>
    </div>
    
    <div class="mt-12 text-sm text-gray-500">
   
    </div>
  </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/Components';

const appName = computed(() => usePage().props.app?.name || 'RestoApp');

const goToLogin = () => {
  router.visit('/login');
};
</script>
