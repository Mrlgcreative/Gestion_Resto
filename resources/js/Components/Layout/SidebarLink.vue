<template>
  <Link
    :href="href"
    :class="[
      isActive
        ? 'bg-gray-800 text-white'
        : 'text-gray-300 hover:bg-gray-700 hover:text-white',
    ]"
    class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
  >
    <span v-if="$slots.icon" class="flex-shrink-0">
      <slot name="icon" />
    </span>
    <span class="flex-1">{{ label }}</span>
    <span v-if="badge" class="bg-primary-600 text-white text-xs px-2 py-0.5 rounded-full">
      {{ badge }}
    </span>
  </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  badge: {
    type: [String, Number],
    default: null,
  },
  activeMatch: {
    type: String,
    default: '',
  },
});

const page = usePage();

const isActive = computed(() => {
  const currentUrl = page.url;
  if (props.activeMatch) {
    return currentUrl.startsWith(props.activeMatch);
  }
  return currentUrl === props.href || currentUrl.startsWith(props.href + '/');
});
</script>
