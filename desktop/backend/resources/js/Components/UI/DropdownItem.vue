<template>
  <component
    :is="href ? 'a' : 'button'"
    :href="href"
    :type="href ? undefined : 'button'"
    :class="itemClasses"
    class="block w-full text-left px-4 py-2 text-sm transition-colors"
    @click="handleClick"
  >
    <div class="flex items-center gap-2">
      <span v-if="$slots.icon" class="flex-shrink-0 text-gray-400">
        <slot name="icon" />
      </span>
      <slot />
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  href: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  danger: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['click']);

const handleClick = (e) => {
  if (props.disabled) {
    e.preventDefault();
    return;
  }
  emit('click', e);
};

const itemClasses = computed(() => [
  props.disabled
    ? 'text-gray-400 cursor-not-allowed'
    : props.danger
    ? 'text-red-600 hover:bg-red-50'
    : 'text-gray-700 hover:bg-gray-100',
]);
</script>
