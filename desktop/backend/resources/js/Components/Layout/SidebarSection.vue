<template>
    <div class="mb-4">
        <h3
            class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5"
        >
            {{ title }}
        </h3>
        <div class="space-y-0.5">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>
