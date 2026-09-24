<template>
  <div>
    <label
      v-if="label"
      class="block text-sm font-medium text-gray-700 mb-2"
    >
      {{ label }}
    </label>
    
    <div
      :class="[
        isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300',
        error ? 'border-red-500' : '',
      ]"
      class="border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer hover:border-primary-400"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      @click="openFilePicker"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="hidden"
        @change="handleFileSelect"
      />

      <!-- Preview -->
      <div v-if="preview && modelValue" class="mb-4">
        <img
          :src="typeof modelValue === 'string' ? modelValue : previewUrl"
          class="mx-auto max-h-32 rounded-lg object-cover"
          alt="Preview"
        />
      </div>

      <!-- Upload Icon -->
      <div v-else class="mb-4">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>

      <!-- Text -->
      <p class="text-sm text-gray-600">
        <span class="text-primary-600 font-medium">Cliquez pour télécharger</span>
        ou glissez-déposez
      </p>
      <p class="text-xs text-gray-500 mt-1">{{ hint }}</p>
    </div>

    <!-- Error -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>

    <!-- Remove button -->
    <button
      v-if="modelValue && removable"
      type="button"
      class="mt-2 text-sm text-red-600 hover:text-red-700"
      @click="remove"
    >
      Supprimer
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [File, String],
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  accept: {
    type: String,
    default: 'image/*',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  preview: {
    type: Boolean,
    default: true,
  },
  hint: {
    type: String,
    default: 'PNG, JPG, GIF jusqu\'à 10MB',
  },
  error: {
    type: String,
    default: '',
  },
  removable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const fileInput = ref(null);
const isDragging = ref(false);

const previewUrl = computed(() => {
  if (props.modelValue instanceof File) {
    return URL.createObjectURL(props.modelValue);
  }
  return props.modelValue;
});

const openFilePicker = () => {
  fileInput.value?.click();
};

const handleFileSelect = (e) => {
  const files = e.target.files;
  if (files.length) {
    const value = props.multiple ? files : files[0];
    emit('update:modelValue', value);
    emit('change', value);
  }
};

const handleDrop = (e) => {
  isDragging.value = false;
  const files = e.dataTransfer.files;
  if (files.length) {
    const value = props.multiple ? files : files[0];
    emit('update:modelValue', value);
    emit('change', value);
  }
};

const remove = () => {
  emit('update:modelValue', null);
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};
</script>
