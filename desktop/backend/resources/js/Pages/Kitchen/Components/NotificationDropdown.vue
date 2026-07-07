<template>
    <div class="relative">
        <button @click="isOpen = !isOpen" class="relative p-2 bg-white rounded-lg border border-gray-200 shadow-sm hover:bg-gray-50 transition">
            <BellIcon class="w-5 h-5 text-gray-600" />
            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse">
                {{ unreadCount > 99 ? "99+" : unreadCount }}
            </span>
        </button>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
            <div v-if="isOpen" class="absolute right-0 z-50 mt-2 w-96 bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200">
                <div class="flex items-center justify-between p-4 bg-gray-50 border-b">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-800">Notifications</span>
                        <span v-if="unreadCount > 0" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ unreadCount }} nouvelles</span>
                    </div>
                    <button v-if="unreadCount > 0" @click="$emit('mark-all-read')" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">Tout marquer lu</button>
                </div>

                <div class="max-h-80 overflow-y-auto">
                    <div v-for="notification in notifications" :key="notification.id" @click="$emit('mark-read', notification.id)"
                        class="p-4 border-b cursor-pointer hover:bg-gray-50 transition" :class="{ 'bg-blue-50': !notification.is_read }">
                        <div class="flex items-start gap-3">
                            <div :class="getIconBg(notification.type)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                                <component :is="getIconComponent(notification.type)" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800" :class="{ 'font-medium': !notification.is_read }">{{ notification.message }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">{{ formatTime(notification.created_at) }}</span>
                                    <span v-if="notification.order_id" class="text-xs text-gray-400">Commande #{{ notification.order_id }}</span>
                                </div>
                            </div>
                            <div v-if="!notification.is_read" class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>

                    <div v-if="notifications.length === 0" class="p-8 text-center">
                        <BellSlashIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                        <p class="text-gray-500">Aucune notification</p>
                        <p class="text-sm text-gray-400 mt-1">Les nouvelles commandes apparaitront ici</p>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40"></div>
</template>

<script setup>
import { ref, computed } from "vue";
import { BellIcon, BellSlashIcon, PlusIcon, CheckIcon, XMarkIcon, ExclamationTriangleIcon, MegaphoneIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    notifications: { type: Array, default: () => [] },
});

defineEmits(["mark-read", "mark-all-read"]);

const isOpen = ref(false);

const unreadCount = computed(() => props.notifications.filter((n) => !n.is_read).length);

const getIconComponent = (type) => {
    const icons = {
        new_order: PlusIcon,
        item_ready: CheckIcon,
        order_ready: CheckIcon,
        order_canceled: XMarkIcon,
        urgent: ExclamationTriangleIcon,
    };
    return icons[type] || MegaphoneIcon;
};

const getIconBg = (type) => {
    const bgs = {
        new_order: "bg-blue-100",
        item_ready: "bg-emerald-100",
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
    if (diffMins < 1) return "A l'instant";
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
};
</script>
