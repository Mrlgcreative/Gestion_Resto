<template>
  <div class="w-full">
    <!-- Label -->
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-medium text-gray-700 mb-1"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Textarea -->
    <textarea
      :id="id"
      ref="textareaRef"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :rows="rows"
      :maxlength="maxlength"
      :class="textareaClasses"
      class="block w-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />

    <!-- Footer -->
    <div class="flex justify-between mt-1">
      <!-- Helper Text / Error -->
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-else-if="helper" class="text-sm text-gray-500">{{ helper }}</p>
      <span v-else />

      <!-- Character Count -->
      <span v-if="showCount && maxlength" class="text-sm text-gray-400">
        {{ modelValue?.length || 0 }} / {{ maxlength }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: () => `textarea-${Math.random().toString(36).substr(2, 9)}`,
  },
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  helper: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: [String, Number],
    default: 4,
  },
  maxlength: {
    type: [String, Number],
    default: undefined,
  },
  showCount: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const textareaRef = ref(null);

const textareaClasses = computed(() => [
  'px-4 py-2 text-base rounded-lg border',
  props.error
    ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
    : 'border-gray-300 text-gray-900 placeholder-gray-400',
  props.disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
]);

defineExpose({
  focus: () => textareaRef.value?.focus(),
});
</script>
