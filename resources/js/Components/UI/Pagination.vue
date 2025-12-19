<template>
  <nav class="flex items-center justify-between">
    <!-- Info -->
    <div class="text-sm text-gray-700">
      Affichage de
      <span class="font-medium">{{ from }}</span>
      à
      <span class="font-medium">{{ to }}</span>
      sur
      <span class="font-medium">{{ total }}</span>
      résultats
    </div>

    <!-- Links -->
    <div class="flex items-center gap-1">
      <!-- Previous -->
      <button
        type="button"
        :disabled="currentPage === 1"
        class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="changePage(currentPage - 1)"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Pages -->
      <template v-for="page in visiblePages" :key="page">
        <span v-if="page === '...'" class="px-3 py-2 text-sm text-gray-700">...</span>
        <button
          v-else
          type="button"
          :class="[
            page === currentPage
              ? 'bg-primary-600 text-white border-primary-600'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
          ]"
          class="relative inline-flex items-center px-4 py-2 text-sm font-medium border"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
      </template>

      <!-- Next -->
      <button
        type="button"
        :disabled="currentPage === lastPage"
        class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="changePage(currentPage + 1)"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
  perPage: {
    type: Number,
    default: 15,
  },
  total: {
    type: Number,
    required: true,
  },
  maxVisible: {
    type: Number,
    default: 5,
  },
});

const emit = defineEmits(['page-change']);

const from = computed(() => {
  if (props.total === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total);
});

const visiblePages = computed(() => {
  const pages = [];
  const half = Math.floor(props.maxVisible / 2);

  let start = Math.max(1, props.currentPage - half);
  let end = Math.min(props.lastPage, props.currentPage + half);

  if (props.currentPage - half < 1) {
    end = Math.min(props.lastPage, end + (half - props.currentPage + 1));
  }

  if (props.currentPage + half > props.lastPage) {
    start = Math.max(1, start - (props.currentPage + half - props.lastPage));
  }

  if (start > 1) {
    pages.push(1);
    if (start > 2) pages.push('...');
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  if (end < props.lastPage) {
    if (end < props.lastPage - 1) pages.push('...');
    pages.push(props.lastPage);
  }

  return pages;
});

const changePage = (page) => {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('page-change', page);
  }
};
</script>
