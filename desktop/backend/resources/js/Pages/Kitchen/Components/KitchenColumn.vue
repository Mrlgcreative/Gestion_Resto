<template>
    <div :class="columnClass" class="rounded-xl p-4 min-h-[500px] border">
        <h3 class="flex items-center gap-2 text-lg font-bold mb-4" :class="titleClass">
            <span>{{ title }}</span>
            <span :class="badgeClass" class="px-2 py-1 rounded-full text-sm ml-auto">{{ items.length }}</span>
        </h3>
        <div class="space-y-3">
            <slot :items="items">
                <KitchenCard v-for="item in items" :key="item.id" :item="item" :status="status" @update-status="$emit('update-status', $event)" />
            </slot>
            <div v-if="items.length === 0" class="text-center py-8 text-gray-500">{{ emptyText }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import KitchenCard from "./KitchenCard.vue";

const props = defineProps({
    title: { type: String, required: true },
    status: { type: String, required: true },
    items: { type: Array, default: () => [] },
    emptyText: { type: String, default: "Aucun élément" },
    color: { type: String, default: "gray" },
});

defineEmits(["update-status"]);

const colorMap = {
    yellow: { column: "bg-yellow-50 border-yellow-200", title: "text-yellow-800", badge: "bg-yellow-200 text-yellow-800" },
    orange: { column: "bg-orange-50 border-orange-200", title: "text-orange-800", badge: "bg-orange-200 text-orange-800" },
    green: { column: "bg-green-50 border-green-200", title: "text-green-800", badge: "bg-green-200 text-green-800" },
    blue: { column: "bg-blue-50 border-blue-200", title: "text-blue-800", badge: "bg-blue-200 text-blue-800" },
    gray: { column: "bg-gray-50 border-gray-200", title: "text-gray-800", badge: "bg-gray-200 text-gray-800" },
};

const columnClass = computed(() => colorMap[props.color]?.column || colorMap.gray.column);
const titleClass = computed(() => colorMap[props.color]?.title || colorMap.gray.title);
const badgeClass = computed(() => colorMap[props.color]?.badge || colorMap.gray.badge);
</script>
