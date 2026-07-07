<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :disabled="disabled"
    :class="[
      modelValue ? 'bg-primary-600' : 'bg-gray-200',
      disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
      sizeClasses.button,
    ]"
    class="relative inline-flex flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    @click="toggle"
  >
    <span
      :class="[
        modelValue ? translateClasses : 'translate-x-0',
        sizeClasses.toggle,
      ]"
      class="pointer-events-none inline-block transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
    />
  </button>
  <span v-if="label" class="ml-3 text-sm text-gray-700" @click="toggle">
    {{ label }}
  </span>
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

const emit = defineEmits(['update:modelValue']);

const sizeOptions = {
  sm: { button: 'h-5 w-9', toggle: 'h-4 w-4', translate: 'translate-x-4' },
  md: { button: 'h-6 w-11', toggle: 'h-5 w-5', translate: 'translate-x-5' },
  lg: { button: 'h-7 w-14', toggle: 'h-6 w-6', translate: 'translate-x-7' },
};

const sizeClasses = computed(() => sizeOptions[props.size]);
const translateClasses = computed(() => sizeOptions[props.size].translate);

const toggle = () => {
  if (!props.disabled) {
    emit('update:modelValue', !props.modelValue);
  }
};
</script>
