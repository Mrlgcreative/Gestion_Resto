<template>
    <div class="relative">
        <!-- Bouton cloche -->
        <button
            @click="isOpen = !isOpen"
            class="relative p-2 bg-white rounded-lg shadow hover:bg-gray-50 transition"
        >
            <svg
                class="w-6 h-6 text-gray-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Badge compteur -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse"
            >
                {{ unreadCount > 99 ? "99+" : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 z-50 mt-2 w-96 bg-white rounded-xl shadow-xl overflow-hidden border"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between p-4 bg-gray-50 border-b"
                >
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-800"
                            >Notifications</span
                        >
                        <span
                            v-if="unreadCount > 0"
                            class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full"
                        >
                            {{ unreadCount }} nouvelles
                        </span>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        @click="$emit('mark-all-read')"
                        class="text-sm text-blue-600 hover:text-blue-800 hover:underline"
                    >
                        Tout marquer lu
                    </button>
                </div>

                <!-- Liste des notifications -->
                <div class="max-h-80 overflow-y-auto">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="$emit('mark-read', notification.id)"
                        class="p-4 border-b cursor-pointer hover:bg-gray-50 transition"
                        :class="{ 'bg-blue-50': !notification.is_read }"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Icône selon le type -->
                            <div
                                :class="getIconBg(notification.type)"
                                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                            >
                                <span class="text-lg">{{
                                    getIcon(notification.type)
                                }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm text-gray-800"
                                    :class="{
                                        'font-medium': !notification.is_read,
                                    }"
                                >
                                    {{ notification.message }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">
                                        {{
                                            formatTime(notification.created_at)
                                        }}
                                    </span>
                                    <span
                                        v-if="notification.order_id"
                                        class="text-xs text-gray-400"
                                    >
                                        • Commande #{{ notification.order_id }}
                                    </span>
                                </div>
                            </div>

                            <!-- Point indicateur non lu -->
                            <div
                                v-if="!notification.is_read"
                                class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full"
                            ></div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div
                        v-if="notifications.length === 0"
                        class="p-8 text-center"
                    >
                        <div class="text-4xl mb-2">🔔</div>
                        <p class="text-gray-500">Aucune notification</p>
                        <p class="text-sm text-gray-400 mt-1">
                            Les nouvelles commandes apparaîtront ici
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <!-- Overlay pour fermer -->
    <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40"></div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    notifications: { type: Array, default: () => [] },
});

defineEmits(["mark-read", "mark-all-read"]);

const isOpen = ref(false);

const unreadCount = computed(
    () => props.notifications.filter((n) => !n.is_read).length
);

const getIcon = (type) => {
    const icons = {
        new_order: "🆕",
        item_ready: "✅",
        order_ready: "🍽️",
        order_canceled: "❌",
        urgent: "🚨",
    };
    return icons[type] || "📢";
};

const getIconBg = (type) => {
    const bgs = {
        new_order: "bg-blue-100",
        item_ready: "bg-green-100",
        order_ready: "bg-emerald-100",
        order_canceled: "bg-red-100",
        urgent: "bg-orange-100",
    };
    return bgs[type] || "bg-gray-100";
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return "À l'instant";
    if (diffMins < 60) return `Il y a ${diffMins} min`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `Il y a ${diffHours}h`;

    return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
};
</script>
