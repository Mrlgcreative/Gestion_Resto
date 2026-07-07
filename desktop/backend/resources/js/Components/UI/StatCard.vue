<template>
  <div :class="containerClasses" class="rounded-lg p-6">
    <div class="flex items-center">
      <!-- Icon -->
      <div v-if="$slots.icon" :class="iconContainerClasses" class="flex-shrink-0 p-3 rounded-lg">
        <slot name="icon" />
      </div>

      <!-- Content -->
      <div :class="$slots.icon ? 'ml-4' : ''" class="flex-1">
        <p class="text-sm font-medium text-gray-500">{{ label }}</p>
        <p :class="valueClasses" class="mt-1 font-semibold">
          {{ formattedValue }}
        </p>
        
        <!-- Trend -->
        <div v-if="trend !== null" class="flex items-center mt-2">
          <span :class="trendClasses" class="text-sm font-medium flex items-center">
            <svg v-if="trend > 0" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
            <svg v-else-if="trend < 0" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            {{ Math.abs(trend) }}%
          </span>
          <span class="text-xs text-gray-500 ml-2">{{ trendLabel }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  format: {
    type: String,
    default: 'number', // number, currency, percentage
  },
  currency: {
    type: String,
    default: 'USD',
  },
  suffix: {
    type: String,
    default: '',
  },
  trend: {
    type: Number,
    default: null,
  },
  trendLabel: {
    type: String,
    default: 'vs période précédente',
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const containerClasses = computed(() => ({
  'bg-white border border-gray-200': props.variant === 'default',
  'bg-primary-50 border border-primary-200': props.variant === 'primary',
  'bg-green-50 border border-green-200': props.variant === 'success',
  'bg-yellow-50 border border-yellow-200': props.variant === 'warning',
  'bg-red-50 border border-red-200': props.variant === 'danger',
}));

const iconContainerClasses = computed(() => ({
  'bg-gray-100': props.variant === 'default',
  'bg-primary-100': props.variant === 'primary',
  'bg-green-100': props.variant === 'success',
  'bg-yellow-100': props.variant === 'warning',
  'bg-red-100': props.variant === 'danger',
}));

const valueSizeClasses = {
  sm: 'text-xl',
  md: 'text-2xl',
  lg: 'text-3xl',
};

const valueClasses = computed(() => [
  valueSizeClasses[props.size],
  'text-gray-900',
]);

const trendClasses = computed(() => ({
  'text-green-600': props.trend > 0,
  'text-red-600': props.trend < 0,
  'text-gray-600': props.trend === 0,
}));

const formattedValue = computed(() => {
  if (props.format === 'text') {
    return props.value + props.suffix;
  }
  if (props.format === 'currency') {
    // Gestion spéciale pour CDF
    if (props.currency === 'CDF') {
      return new Intl.NumberFormat('fr-FR').format(Math.round(props.value)) + ' FC';
    }
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: props.currency,
    }).format(props.value);
  }
  if (props.format === 'percentage') {
    return `${props.value}%`;
  }
  if (typeof props.value === 'number') {
    return new Intl.NumberFormat('fr-FR').format(props.value) + props.suffix;
  }
  return props.value + props.suffix;
});
</script>
