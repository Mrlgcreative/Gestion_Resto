<template>
    <div :class="cardClass" class="rounded-xl p-4 border flex items-center gap-4">
        <div :class="iconBgClass" class="w-12 h-12 rounded-lg flex items-center justify-center">
            <component :is="iconComponent" class="w-6 h-6" />
        </div>
        <div>
            <p class="text-sm text-gray-600">{{ title }}</p>
            <p class="text-2xl font-bold" :class="valueClass">{{ value }}<span v-if="suffix" class="text-sm ml-1">{{ suffix }}</span></p>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { ClockIcon, FireIcon, CheckCircleIcon, ExclamationCircleIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    color: { type: String, default: "gray" },
    suffix: { type: String, default: "" },
});

const iconComponent = computed(() => {
    if (props.color === "yellow") return ClockIcon;
    if (props.color === "orange") return FireIcon;
    if (props.color === "green") return CheckCircleIcon;
    return ExclamationCircleIcon;
});

const colorMap = {
    yellow: { card: "bg-yellow-50 border-yellow-200", iconBg: "bg-yellow-100", value: "text-yellow-700" },
    orange: { card: "bg-orange-50 border-orange-200", iconBg: "bg-orange-100", value: "text-orange-700" },
    green: { card: "bg-green-50 border-green-200", iconBg: "bg-green-100", value: "text-green-700" },
    blue: { card: "bg-blue-50 border-blue-200", iconBg: "bg-blue-100", value: "text-blue-700" },
    red: { card: "bg-red-50 border-red-200", iconBg: "bg-red-100", value: "text-red-700" },
    gray: { card: "bg-gray-50 border-gray-200", iconBg: "bg-gray-100", value: "text-gray-700" },
};

const cardClass = computed(() => colorMap[props.color]?.card || colorMap.gray.card);
const iconBgClass = computed(() => colorMap[props.color]?.iconBg || colorMap.gray.iconBg);
const valueClass = computed(() => colorMap[props.color]?.value || colorMap.gray.value);
</script>
