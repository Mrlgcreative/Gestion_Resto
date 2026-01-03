<template>
    <div :class="cardClass" class="rounded-xl p-4 flex items-center gap-4">
        <div
            :class="iconBgClass"
            class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
        >
            {{ icon }}
        </div>
        <div>
            <p class="text-sm text-gray-600">{{ title }}</p>
            <p class="text-2xl font-bold" :class="valueClass">
                {{ value
                }}<span v-if="suffix" class="text-sm ml-1">{{ suffix }}</span>
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    icon: { type: String, default: "📊" },
    color: { type: String, default: "gray" },
    suffix: { type: String, default: "" },
});

const colorMap = {
    yellow: {
        card: "bg-yellow-50 border border-yellow-200",
        iconBg: "bg-yellow-100",
        value: "text-yellow-700",
    },
    orange: {
        card: "bg-orange-50 border border-orange-200",
        iconBg: "bg-orange-100",
        value: "text-orange-700",
    },
    green: {
        card: "bg-green-50 border border-green-200",
        iconBg: "bg-green-100",
        value: "text-green-700",
    },
    blue: {
        card: "bg-blue-50 border border-blue-200",
        iconBg: "bg-blue-100",
        value: "text-blue-700",
    },
    red: {
        card: "bg-red-50 border border-red-200",
        iconBg: "bg-red-100",
        value: "text-red-700",
    },
    gray: {
        card: "bg-gray-50 border border-gray-200",
        iconBg: "bg-gray-100",
        value: "text-gray-700",
    },
};

const cardClass = computed(
    () => colorMap[props.color]?.card || colorMap.gray.card
);
const iconBgClass = computed(
    () => colorMap[props.color]?.iconBg || colorMap.gray.iconBg
);
const valueClass = computed(
    () => colorMap[props.color]?.value || colorMap.gray.value
);
</script>
