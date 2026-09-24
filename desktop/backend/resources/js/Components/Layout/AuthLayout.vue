<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo -->
      <div class="text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center mb-4">
          <span class="text-2xl font-bold text-white">{{ appName.charAt(0) }}</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">{{ appName }}</h1>
        <h2 class="mt-2 text-xl font-semibold text-gray-700">
          <slot name="title">Connexion</slot>
        </h2>
        <p v-if="$slots.subtitle" class="mt-2 text-sm text-gray-500">
          <slot name="subtitle" />
        </p>
      </div>

      <!-- Content -->
      <div class="bg-white py-8 px-6 shadow-lg rounded-xl border border-gray-200">
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
