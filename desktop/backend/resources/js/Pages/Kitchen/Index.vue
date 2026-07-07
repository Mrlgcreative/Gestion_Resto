<template>
    <Head title="Cuisine" />

    <MainLayout page-title="Cuisine" page-description="Gestion des commandes en cuisine">
        <div class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <KitchenSessionControl @session-changed="onSessionChanged" />

                <template v-if="hasActiveSession">
                    <!-- Stats + Controls -->
                    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                        <KitchenStats
                            :waiting="stats.waiting"
                            :preparing="stats.preparing"
                            :ready="stats.ready"
                            :avg-time="stats.avgTime"
                        />
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <div class="w-2 h-2 rounded-full" :class="isRefreshing ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></div>
                                <span>{{ formatLastRefresh }}</span>
                            </div>
                            <button @click="audioEnabled = !audioEnabled"
                                class="p-2 rounded-lg transition"
                                :class="audioEnabled ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                :title="audioEnabled ? 'Son activé' : 'Son désactivé'">
                                <SpeakerWaveIcon v-if="audioEnabled" class="w-5 h-5" />
                                <SpeakerXMarkIcon v-else class="w-5 h-5" />
                            </button>
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button @click="viewMode = 'kanban'"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                                    :class="viewMode === 'kanban' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'">
                                    Kanban
                                </button>
                                <button @click="viewMode = 'orders'"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                                    :class="viewMode === 'orders' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'">
                                    Commandes
                                </button>
                            </div>
                            <NotificationDropdown
                                :notifications="localNotifications"
                                @mark-read="markNotificationRead"
                                @mark-all-read="markAllNotificationsRead"
                            />
                            <button @click="refreshData" :disabled="isRefreshing"
                                class="p-2 bg-white rounded-lg border border-gray-200 shadow-sm hover:bg-gray-50 transition disabled:opacity-50">
                                <ArrowPathIcon class="w-5 h-5 text-gray-600" :class="{ 'animate-spin': isRefreshing }" />
                            </button>
                        </div>
                    </div>

                    <!-- Kanban View -->
                    <div v-if="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <KitchenColumn
                            title="En attente"
                            status="waiting"
                            color="yellow"
                            :items="localWaiting"
                            empty-text="Aucun plat en attente"
                            @update-status="updateStatus"
                        />
                        <KitchenColumn
                            title="En préparation"
                            status="preparing"
                            color="orange"
                            :items="localPreparing"
                            empty-text="Aucun plat en préparation"
                            @update-status="updateStatus"
                        />
                        <KitchenColumn
                            title="Prêt à servir"
                            status="ready"
                            color="green"
                            :items="localReady"
                            empty-text="Aucun plat prêt"
                            @update-status="updateStatus"
                        />
                    </div>

                    <!-- Orders View -->
                    <div v-else class="space-y-6">
                        <OrderCard v-for="order in localOrders" :key="order.id" :order="order" @update-status="updateStatus" @mark-ready="markOrderReady" />
                        <div v-if="localOrders.length === 0" class="text-center py-16 bg-white rounded-xl border border-gray-200 shadow-sm">
                            <FireIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucune commande en cours</h3>
                            <p class="text-gray-500">Les nouvelles commandes apparaîtront ici automatiquement</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import MainLayout from "@/Layouts/MainLayout.vue";
import KitchenColumn from "./Components/KitchenColumn.vue";
import KitchenCard from "./Components/KitchenCard.vue";
import KitchenStats from "./Components/KitchenStats.vue";
import OrderCard from "./Components/OrderCard.vue";
import NotificationDropdown from "./Components/NotificationDropdown.vue";
import KitchenSessionControl from "@/Components/KitchenSessionControl.vue";
import { SpeakerWaveIcon, SpeakerXMarkIcon, ArrowPathIcon, FireIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    orders: { type: Array, default: () => [] },
    waitingItems: { type: Array, default: () => [] },
    preparingItems: { type: Array, default: () => [] },
    readyItems: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

const hasActiveSession = ref(false);
const viewMode = ref("kanban");
const localWaiting = ref([...props.waitingItems]);
const localPreparing = ref([...props.preparingItems]);
const localReady = ref([...props.readyItems]);
const localOrders = ref([...props.orders]);
const localNotifications = ref([...props.notifications]);
const audioEnabled = ref(true);
const isRefreshing = ref(false);
const lastRefresh = ref(new Date());

const getBaseUrl = () => {
    const path = window.location.pathname;
    const match = path.match(/^(.*?)\/kitchen/);
    return match ? match[1] : "";
};
const baseUrl = getBaseUrl();

const unreadCount = computed(() => localNotifications.value.filter((n) => !n.is_read).length);

const stats = computed(() => ({
    waiting: localWaiting.value.length,
    preparing: localPreparing.value.length,
    ready: localReady.value.length,
    avgTime: calculateAvgTime(),
}));

let notificationSound = null;
let pollingInterval = null;

const onSessionChanged = (hasSession) => {
    hasActiveSession.value = hasSession;
    if (hasSession) {
        startPolling();
        refreshData();
    } else {
        stopPolling();
    }
};

onMounted(() => {
    try {
        notificationSound = new Audio("/sounds/notification.mp3");
        notificationSound.volume = 0.5;
        notificationSound.load();
    } catch (e) {
        console.warn("Son de notification non disponible");
    }
});

onUnmounted(() => { stopPolling(); });

const startPolling = () => { pollingInterval = setInterval(refreshData, 5000); };
const stopPolling = () => { if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; } };

const isUpdating = ref(false);

const refreshData = async () => {
    if (isRefreshing.value || isUpdating.value) return;
    isRefreshing.value = true;
    try {
        const response = await axios.get(`${baseUrl}/kitchen/refresh`);
        const data = response.data;
        const newNotifications = data.notifications.filter(
            (n) => !localNotifications.value.find((ln) => ln.id === n.id)
        );
        if (newNotifications.length > 0 && audioEnabled.value) {
            playNotificationSound();
        }
        localNotifications.value = data.notifications;
        localOrders.value = data.orders;
        updateItemsFromOrders(data.orders);
        lastRefresh.value = new Date();
    } catch (error) {
        console.error("Erreur de rafraîchissement:", error);
    } finally {
        isRefreshing.value = false;
    }
};

const updateItemsFromOrders = (orders) => {
    const waiting = [];
    const preparing = [];
    const ready = [];
    orders.forEach((order) => {
        order.items.forEach((item) => {
            const itemWithOrder = { ...item, order_id: order.id, table_number: order.table_number, server_name: order.server, elapsed_minutes: order.elapsed_minutes };
            if (item.kitchen_status === "waiting") waiting.push(itemWithOrder);
            else if (item.kitchen_status === "preparing") preparing.push(itemWithOrder);
            else if (item.kitchen_status === "ready") ready.push(itemWithOrder);
        });
    });
    localWaiting.value = waiting;
    localPreparing.value = preparing;
    localReady.value = ready;
};

const playNotificationSound = () => {
    if (notificationSound) {
        notificationSound.currentTime = 0;
        notificationSound.play().catch(() => {});
    }
};

const updateStatus = async ({ itemId, status }) => {
    isUpdating.value = true;
    moveItem(itemId, status);
    try {
        await axios.patch(`${baseUrl}/kitchen/items/${itemId}/status`, { status });
    } catch (error) {
        console.error("Erreur mise à jour:", error);
        await refreshData();
    } finally {
        setTimeout(() => { isUpdating.value = false; }, 500);
    }
};

const moveItem = (itemId, newStatus) => {
    let item = null;
    const lists = [
        { ref: localWaiting, status: "waiting" },
        { ref: localPreparing, status: "preparing" },
        { ref: localReady, status: "ready" },
    ];
    for (const list of lists) {
        const index = list.ref.value.findIndex((i) => i.id === itemId);
        if (index !== -1) {
            item = list.ref.value.splice(index, 1)[0];
            break;
        }
    }
    if (!item) return;
    item.kitchen_status = newStatus;
    if (newStatus === "waiting") localWaiting.value.unshift(item);
    else if (newStatus === "preparing") localPreparing.value.unshift(item);
    else if (newStatus === "ready") localReady.value.unshift(item);
};

const markOrderReady = async (orderId) => {
    isUpdating.value = true;
    try {
        await axios.post(`${baseUrl}/kitchen/orders/${orderId}/ready`);
        await refreshData();
    } catch (error) {
        console.error("Erreur:", error);
    } finally {
        setTimeout(() => { isUpdating.value = false; }, 500);
    }
};

const markNotificationRead = async (notificationId) => {
    const notification = localNotifications.value.find((n) => n.id === notificationId);
    if (notification) notification.is_read = true;
    try {
        await axios.patch(`${baseUrl}/kitchen/notifications/${notificationId}/read`);
    } catch (error) {
        console.error("Erreur notification:", error);
    }
};

const markAllNotificationsRead = async () => {
    localNotifications.value.forEach((n) => (n.is_read = true));
    try {
        await axios.post(`${baseUrl}/kitchen/notifications/read-all`);
    } catch (error) {
        console.error("Erreur notifications:", error);
    }
};

const calculateAvgTime = () => {
    const allItems = [...localWaiting.value, ...localPreparing.value];
    if (allItems.length === 0) return 0;
    const totalMinutes = allItems.reduce((sum, item) => sum + (item.elapsed_minutes || 0), 0);
    return Math.round(totalMinutes / allItems.length);
};

const formatLastRefresh = computed(() => {
    return lastRefresh.value.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
});
</script>
