<template>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 transition-all hover:shadow-md" :class="borderClass">
        <div class="flex items-start gap-3 mb-2">
            <div class="flex-shrink-0">
                <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="w-16 h-16 object-cover rounded-lg shadow" />
                <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <CubeIcon class="w-8 h-8 text-gray-400" />
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <span class="bg-gray-800 text-white font-bold px-2 py-1 rounded text-lg">{{ item.quantity }}x</span>
                        <span class="font-semibold text-lg truncate">{{ item.product_name }}</span>
                    </div>
                    <span class="text-xs bg-gray-100 px-2 py-1 rounded flex-shrink-0">{{ item.category }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2 text-sm text-gray-600 mb-3">
            <span class="flex items-center gap-1">
                <ClipboardDocumentListIcon class="w-4 h-4" />
                #{{ item.order_id }}
            </span>
            <span v-if="item.table_number" class="flex items-center gap-1">
                <Squares2X2Icon class="w-4 h-4" />
                Table {{ item.table_number }}
            </span>
            <span v-if="item.server_name" class="flex items-center gap-1">
                <UserIcon class="w-4 h-4" />
                {{ item.server_name }}
            </span>
            <span :class="timeColorClass" class="flex items-center gap-1 font-medium">
                <ClockIcon class="w-4 h-4" />
                {{ formattedTime }}
            </span>
        </div>

        <div v-if="item.kitchen_note" class="text-sm text-orange-700 mb-3 p-2 bg-orange-50 rounded-lg border border-orange-200">
            <span class="font-medium">Note:</span> {{ item.kitchen_note }}
        </div>

        <div class="flex gap-2">
            <template v-if="status === 'waiting'">
                <button @click="$emit('update-status', { itemId: item.id, status: 'preparing' })"
                    class="flex-1 py-2 px-4 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition font-medium flex items-center justify-center gap-2">
                    <PlayIcon class="w-5 h-5" />
                    Commencer
                </button>
            </template>
            <template v-else-if="status === 'preparing'">
                <button @click="$emit('update-status', { itemId: item.id, status: 'waiting' })"
                    class="py-2 px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <ArrowUturnLeftIcon class="w-5 h-5" />
                </button>
                <button @click="$emit('update-status', { itemId: item.id, status: 'ready' })"
                    class="flex-1 py-2 px-4 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition font-medium flex items-center justify-center gap-2">
                    <CheckIcon class="w-5 h-5" />
                    Pret !
                </button>
            </template>
            <template v-else-if="status === 'ready'">
                <button @click="$emit('update-status', { itemId: item.id, status: 'preparing' })"
                    class="py-2 px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <ArrowUturnLeftIcon class="w-5 h-5" />
                </button>
                <button @click="$emit('update-status', { itemId: item.id, status: 'served' })"
                    class="flex-1 py-2 px-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium flex items-center justify-center gap-2">
                    <CheckBadgeIcon class="w-5 h-5" />
                    Servi
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { CubeIcon, ClipboardDocumentListIcon, Squares2X2Icon, UserIcon, ClockIcon, PlayIcon, ArrowUturnLeftIcon, CheckIcon, CheckBadgeIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    item: { type: Object, required: true },
    status: { type: String, required: true },
});

defineEmits(["update-status"]);

const formattedTime = computed(() => {
    const minutes = props.item.elapsed_minutes || 0;
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
});

const timeColorClass = computed(() => {
    const minutes = props.item.elapsed_minutes || 0;
    if (minutes < 10) return "text-emerald-600";
    if (minutes < 20) return "text-amber-600";
    return "text-red-600";
});

const borderClass = computed(() => {
    const borders = { waiting: "", preparing: "border-l-4 border-orange-500", ready: "border-l-4 border-emerald-500" };
    return borders[props.status] || "";
});
</script>
