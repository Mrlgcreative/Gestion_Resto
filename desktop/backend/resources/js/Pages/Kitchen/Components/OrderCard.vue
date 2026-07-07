<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b" :class="headerBg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-white rounded-lg shadow">
                        <span class="text-xl font-bold text-gray-800">#{{ order.id }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span v-if="order.table_number" class="font-semibold text-lg">Table {{ order.table_number }}</span>
                            <span v-else class="font-semibold text-lg text-gray-600">A emporter</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <span v-if="order.server">{{ order.server }}</span>
                            <span :class="timeColorClass" class="font-medium">{{ formattedTime }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-sm font-medium">{{ statusLabel }}</span>
                    <button v-if="canMarkAllReady" @click="$emit('mark-ready', order.id)"
                        class="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition text-sm font-medium flex items-center gap-1">
                        <CheckIcon class="w-4 h-4" />
                        Tout pret
                    </button>
                </div>
            </div>
        </div>

        <div class="divide-y">
            <div v-for="item in order.items" :key="item.id" class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div :class="getItemStatusClass(item.kitchen_status)" class="w-3 h-3 rounded-full"></div>
                    <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="w-12 h-12 object-cover rounded-lg" />
                    <div v-else class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                        <CubeIcon class="w-6 h-6 text-gray-400" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold">{{ item.quantity }}x</span>
                            <span class="font-medium">{{ item.product_name }}</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ item.category }}</span>
                        <div v-if="item.kitchen_note" class="text-sm text-orange-600 mt-1">Note: {{ item.kitchen_note }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="item.kitchen_status === 'waiting'"
                        @click="$emit('update-status', { itemId: item.id, status: 'preparing' })"
                        class="px-3 py-1 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition text-sm">
                        Demarrer
                    </button>
                    <button v-else-if="item.kitchen_status === 'preparing'"
                        @click="$emit('update-status', { itemId: item.id, status: 'ready' })"
                        class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition text-sm flex items-center gap-1">
                        <CheckIcon class="w-3.5 h-3.5" />
                        Pret
                    </button>
                    <span v-else-if="item.kitchen_status === 'ready'" class="px-3 py-1 bg-emerald-500 text-white rounded-lg text-sm flex items-center gap-1">
                        <CheckIcon class="w-3.5 h-3.5" />
                        Pret
                    </span>
                    <span v-else-if="item.kitchen_status === 'served'" class="px-3 py-1 bg-blue-500 text-white rounded-lg text-sm flex items-center gap-1">
                        <CheckBadgeIcon class="w-3.5 h-3.5" />
                        Servi
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { CubeIcon, CheckIcon, CheckBadgeIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    order: { type: Object, required: true },
});

defineEmits(["update-status", "mark-ready"]);

const formattedTime = computed(() => {
    const minutes = props.order.elapsed_minutes || 0;
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
});

const timeColorClass = computed(() => {
    const minutes = props.order.elapsed_minutes || 0;
    if (minutes < 10) return "text-emerald-600";
    if (minutes < 20) return "text-amber-600";
    return "text-red-600";
});

const orderStatus = computed(() => {
    const items = props.order.items || [];
    const allReady = items.every((i) => i.kitchen_status === "ready" || i.kitchen_status === "served");
    const allServed = items.every((i) => i.kitchen_status === "served");
    const anyPreparing = items.some((i) => i.kitchen_status === "preparing");
    if (allServed) return "served";
    if (allReady) return "ready";
    if (anyPreparing) return "preparing";
    return "waiting";
});

const headerBg = computed(() => {
    const bgs = { waiting: "bg-amber-50", preparing: "bg-orange-50", ready: "bg-emerald-50", served: "bg-blue-50" };
    return bgs[orderStatus.value] || "bg-gray-50";
});

const statusBadgeClass = computed(() => {
    const classes = { waiting: "bg-amber-100 text-amber-700", preparing: "bg-orange-100 text-orange-700", ready: "bg-emerald-100 text-emerald-700", served: "bg-blue-100 text-blue-700" };
    return classes[orderStatus.value] || "bg-gray-100 text-gray-700";
});

const statusLabel = computed(() => {
    const labels = { waiting: "En attente", preparing: "En cours", ready: "Pret", served: "Servi" };
    return labels[orderStatus.value] || "Inconnu";
});

const canMarkAllReady = computed(() => {
    const items = props.order.items || [];
    return items.some((i) => i.kitchen_status === "waiting" || i.kitchen_status === "preparing");
});

const getItemStatusClass = (status) => {
    const classes = { waiting: "bg-amber-400", preparing: "bg-orange-400 animate-pulse", ready: "bg-emerald-400", served: "bg-blue-400" };
    return classes[status] || "bg-gray-400";
};
</script>
