<template>
  <label class="inline-flex items-center cursor-pointer">
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      :class="checkboxClasses"
      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 focus:ring-2 transition-colors"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span v-if="label" class="ml-2 text-sm text-gray-700">
      {{ label }}
    </span>
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

defineEmits(['update:modelValue']);

const sizeClasses = {
  sm: 'h-3 w-3',
  md: 'h-4 w-4',
  lg: 'h-5 w-5',
};

const checkboxClasses = computed(() => [
  sizeClasses[props.size],
  props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
]);
</script>
