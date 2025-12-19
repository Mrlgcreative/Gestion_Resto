<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo -->
      <div class="text-center">
        <h1 class="text-4xl font-bold text-primary-600">🍽️ {{ appName }}</h1>
        <h2 class="mt-4 text-2xl font-bold text-gray-900">
          <slot name="title">Connexion</slot>
        </h2>
        <p v-if="$slots.subtitle" class="mt-2 text-sm text-gray-600">
          <slot name="subtitle" />
        </p>
      </div>

      <!-- Content -->
      <div class="bg-white py-8 px-6 shadow-lg rounded-xl">
        <slot />
      </div>

      <!-- Footer -->
      <div v-if="$slots.footer" class="text-center">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const appName = computed(() => usePage().props.app?.name || 'RestoApp');
</script>
