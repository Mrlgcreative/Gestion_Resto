<template>
  <span :class="badgeClasses" class="inline-flex items-center font-medium">
    <!-- Dot indicator -->
    <span v-if="dot" :class="dotClasses" class="w-2 h-2 rounded-full mr-1.5" />
    
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) =>
      ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  rounded: {
    type: Boolean,
    default: true,
  },
  dot: {
    type: Boolean,
    default: false,
  },
});

const sizeClasses = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2.5 py-0.5 text-sm',
  lg: 'px-3 py-1 text-base',
};

const variantClasses = {
  default: 'bg-gray-100 text-gray-800',
  primary: 'bg-primary-100 text-primary-800',
  success: 'bg-green-100 text-green-800',
  warning: 'bg-yellow-100 text-yellow-800',
  danger: 'bg-red-100 text-red-800',
  info: 'bg-blue-100 text-blue-800',
};

const dotVariantClasses = {
  default: 'bg-gray-500',
  primary: 'bg-primary-500',
  success: 'bg-green-500',
  warning: 'bg-yellow-500',
  danger: 'bg-red-500',
  info: 'bg-blue-500',
};

const badgeClasses = computed(() => [
  sizeClasses[props.size],
  variantClasses[props.variant],
  props.rounded ? 'rounded-full' : 'rounded',
]);

const dotClasses = computed(() => dotVariantClasses[props.variant]);
</script>
