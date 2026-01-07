<template>
    <div class="mb-3 lg:mb-4">
        <h3
            class="px-2 lg:px-3 text-[10px] lg:text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5 lg:mb-2"
        >
            {{ title }}
        </h3>
        <div class="space-y-0.5 lg:space-y-1">
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
