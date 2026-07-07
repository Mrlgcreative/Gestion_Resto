<template>
  <div class="flex justify-center items-center" :class="containerClasses">
    <svg
      :class="spinnerClasses"
      class="animate-spin"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>
    <span v-if="text" class="ml-3 text-gray-600">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
  },
  text: {
    type: String,
    default: '',
  },
  fullScreen: {
    type: Boolean,
    default: false,
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'gray', 'white'].includes(value),
  },
});

const sizeClasses = {
  sm: 'h-4 w-4',
  md: 'h-8 w-8',
  lg: 'h-12 w-12',
  xl: 'h-16 w-16',
};

const colorClasses = {
  primary: 'text-primary-600',
  gray: 'text-gray-600',
  white: 'text-white',
};

const spinnerClasses = computed(() => [
  sizeClasses[props.size],
  colorClasses[props.color],
]);

const containerClasses = computed(() =>
  props.fullScreen ? 'fixed inset-0 bg-white/80 z-50' : ''
);
</script>
