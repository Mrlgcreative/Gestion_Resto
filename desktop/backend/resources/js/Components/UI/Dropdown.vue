<template>
  <div class="relative" v-click-outside="close">
    <!-- Trigger -->
    <div @click="toggle">
      <slot name="trigger" />
    </div>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :class="[positionClasses, widthClasses]"
        class="absolute z-50 mt-2 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <div class="py-1">
          <slot />
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  position: {
    type: String,
    default: 'bottom-right',
    validator: (value) =>
      ['bottom-left', 'bottom-right', 'top-left', 'top-right'].includes(value),
  },
  width: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'auto'].includes(value),
  },
});

const isOpen = ref(false);

const toggle = () => {
  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const positionClasses = computed(() => {
  const classes = {
    'bottom-left': 'left-0 origin-top-left',
    'bottom-right': 'right-0 origin-top-right',
    'top-left': 'left-0 bottom-full mb-2 origin-bottom-left',
    'top-right': 'right-0 bottom-full mb-2 origin-bottom-right',
  };
  return classes[props.position];
});

const widthClasses = computed(() => {
  const classes = {
    sm: 'w-40',
    md: 'w-48',
    lg: 'w-56',
    auto: 'w-auto min-w-max',
  };
  return classes[props.width];
});

// Custom directive for click outside
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el._clickOutside);
  },
  unmounted(el) {
    document.removeEventListener('click', el._clickOutside);
  },
};

defineExpose({ close });
</script>
