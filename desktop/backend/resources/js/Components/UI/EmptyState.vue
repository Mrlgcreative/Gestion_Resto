<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <!-- Icon -->
    <div v-if="$slots.icon" class="mb-4 text-gray-300">
      <slot name="icon" />
    </div>
    <div v-else class="mb-4">
      <svg class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
        />
      </svg>
    </div>

    <!-- Title -->
    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ title }}</h3>

    <!-- Description -->
    <p class="text-sm text-gray-500 max-w-sm mb-6">{{ description }}</p>

    <!-- Action -->
    <slot name="action" />
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Aucun résultat',
  },
  description: {
    type: String,
    default: "Aucune donnée n'a été trouvée.",
  },
});
</script>
