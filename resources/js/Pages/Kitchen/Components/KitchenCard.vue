<template>
    <div
        class="bg-white rounded-lg shadow p-4 transition-all hover:shadow-md"
        :class="borderClass"
    >
        <!-- En-tête: Image + Quantité + Produit + Catégorie -->
        <div class="flex items-start gap-3 mb-2">
            <!-- Image du produit -->
            <div class="flex-shrink-0">
                <img
                    v-if="item.product_image"
                    :src="item.product_image"
                    :alt="item.product_name"
                    class="w-16 h-16 object-cover rounded-lg shadow"
                />
                <div
                    v-else
                    class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center text-2xl"
                >
                    🍽️
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="bg-gray-800 text-white font-bold px-2 py-1 rounded text-lg"
                        >
                            {{ item.quantity }}x
                        </span>
                        <span class="font-semibold text-lg truncate">{{
                            item.product_name
                        }}</span>
                    </div>
                    <span
                        class="text-xs bg-gray-100 px-2 py-1 rounded flex-shrink-0"
                    >
                        {{ item.category }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Infos: Commande, Table, Temps -->
        <div
            class="flex items-center flex-wrap gap-2 text-sm text-gray-600 mb-3"
        >
            <span class="flex items-center gap-1">
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </svg>
                #{{ item.order_id }}
            </span>

            <span v-if="item.table_number" class="flex items-center gap-1">
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                </svg>
                Table {{ item.table_number }}
            </span>

            <span v-if="item.server_name" class="flex items-center gap-1">
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                </svg>
                {{ item.server_name }}
            </span>

            <span
                :class="timeColorClass"
                class="flex items-center gap-1 font-medium"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                {{ formattedTime }}
            </span>
        </div>

        <!-- Note cuisine -->
        <div
            v-if="item.kitchen_note"
            class="text-sm text-orange-700 mb-3 p-2 bg-orange-50 rounded-lg border border-orange-200"
        >
            <span class="font-medium">📝 Note:</span> {{ item.kitchen_note }}
        </div>

        <!-- Boutons d'action selon le statut -->
        <div class="flex gap-2">
            <!-- En attente -->
            <template v-if="status === 'waiting'">
                <button
                    @click="
                        $emit('update-status', {
                            itemId: item.id,
                            status: 'preparing',
                        })
                    "
                    class="flex-1 py-2 px-4 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-medium flex items-center justify-center gap-2"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Commencer
                </button>
            </template>

            <!-- En préparation -->
            <template v-else-if="status === 'preparing'">
                <button
                    @click="
                        $emit('update-status', {
                            itemId: item.id,
                            status: 'waiting',
                        })
                    "
                    class="py-2 px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                >
                    ⏪
                </button>
                <button
                    @click="
                        $emit('update-status', {
                            itemId: item.id,
                            status: 'ready',
                        })
                    "
                    class="flex-1 py-2 px-4 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium flex items-center justify-center gap-2"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Prêt !
                </button>
            </template>

            <!-- Prêt -->
            <template v-else-if="status === 'ready'">
                <button
                    @click="
                        $emit('update-status', {
                            itemId: item.id,
                            status: 'preparing',
                        })
                    "
                    class="py-2 px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                >
                    ⏪
                </button>
                <button
                    @click="
                        $emit('update-status', {
                            itemId: item.id,
                            status: 'served',
                        })
                    "
                    class="flex-1 py-2 px-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium flex items-center justify-center gap-2"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                        />
                    </svg>
                    Servi
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    item: { type: Object, required: true },
    status: { type: String, required: true },
});

defineEmits(["update-status"]);

// Calcul du temps écoulé
const formattedTime = computed(() => {
    const minutes = props.item.elapsed_minutes || 0;
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
});

// Couleur selon le temps (vert < 10min, jaune < 20min, rouge >= 20min)
const timeColorClass = computed(() => {
    const minutes = props.item.elapsed_minutes || 0;
    if (minutes < 10) return "text-green-600";
    if (minutes < 20) return "text-yellow-600";
    return "text-red-600";
});

// Bordure selon le statut
const borderClass = computed(() => {
    const borders = {
        waiting: "",
        preparing: "border-l-4 border-orange-500",
        ready: "border-l-4 border-green-500",
    };
    return borders[props.status] || "";
});
</script>
