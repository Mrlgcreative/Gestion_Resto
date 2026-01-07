<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import MainLayout from "@/Layouts/MainLayout.vue";

// Composants
import KitchenColumn from "./Components/KitchenColumn.vue";
import KitchenCard from "./Components/KitchenCard.vue";
import KitchenStats from "./Components/KitchenStats.vue";
import OrderCard from "./Components/OrderCard.vue";
import NotificationDropdown from "./Components/NotificationDropdown.vue";
import KitchenSessionControl from "@/Components/KitchenSessionControl.vue";

const props = defineProps({
    orders: { type: Array, default: () => [] },
    waitingItems: { type: Array, default: () => [] },
    preparingItems: { type: Array, default: () => [] },
    readyItems: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

// ==================== État ====================

const hasActiveSession = ref(false); // Session active du cuisinier
const viewMode = ref("kanban"); // 'kanban' ou 'orders'
const localWaiting = ref([...props.waitingItems]);
const localPreparing = ref([...props.preparingItems]);
const localReady = ref([...props.readyItems]);
const localOrders = ref([...props.orders]);
const localNotifications = ref([...props.notifications]);
const audioEnabled = ref(true);
const isRefreshing = ref(false);
const lastRefresh = ref(new Date());

// Obtenir l'URL de base
const getBaseUrl = () => {
    const path = window.location.pathname;
    const match = path.match(/^(.*?)\/kitchen/);
    return match ? match[1] : "";
};
const baseUrl = getBaseUrl();

// ==================== Computed ====================

const unreadCount = computed(
    () => localNotifications.value.filter((n) => !n.is_read).length
);

const stats = computed(() => ({
    waiting: localWaiting.value.length,
    preparing: localPreparing.value.length,
    ready: localReady.value.length,
    avgTime: calculateAvgTime(),
}));

// ==================== Audio & Polling ====================

let notificationSound = null;
let pollingInterval = null;

// Gérer le changement de session
const onSessionChanged = (hasSession) => {
    hasActiveSession.value = hasSession;
    if (hasSession) {
        // Session ouverte : démarrer le polling
        startPolling();
        refreshData(); // Charger les données immédiatement
    } else {
        // Session fermée : arrêter le polling
        stopPolling();
    }
};

onMounted(() => {
    // Initialiser le son (ignorer l'erreur si le fichier n'existe pas)
    try {
        notificationSound = new Audio("/sounds/notification.mp3");
        notificationSound.volume = 0.5;
        // Précharger le son
        notificationSound.load();
    } catch (e) {
        console.warn("Son de notification non disponible");
    }

    // Le polling sera démarré quand la session sera détectée comme active
});

onUnmounted(() => {
    stopPolling();
});

const startPolling = () => {
    pollingInterval = setInterval(refreshData, 5000);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// Flag pour bloquer le polling pendant une mise à jour
const isUpdating = ref(false);

const refreshData = async () => {
    // Ne pas rafraîchir si une mise à jour est en cours
    if (isRefreshing.value || isUpdating.value) return;

    isRefreshing.value = true;
    try {
        const response = await axios.get(`${baseUrl}/kitchen/refresh`);
        const data = response.data;

        // Vérifier nouvelles notifications
        const newNotifications = data.notifications.filter(
            (n) => !localNotifications.value.find((ln) => ln.id === n.id)
        );

        if (newNotifications.length > 0 && audioEnabled.value) {
            playNotificationSound();
        }

        // Mettre à jour les données
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
            const itemWithOrder = {
                ...item,
                order_id: order.id,
                table_number: order.table_number,
                server_name: order.server,
                elapsed_minutes: order.elapsed_minutes,
            };

            if (item.kitchen_status === "waiting") waiting.push(itemWithOrder);
            else if (item.kitchen_status === "preparing")
                preparing.push(itemWithOrder);
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

// ==================== Actions ====================

const updateStatus = async ({ itemId, status }) => {
    // Bloquer le polling pendant la mise à jour
    isUpdating.value = true;

    // Mise à jour optimiste locale
    moveItem(itemId, status);

    // Envoyer au serveur avec axios (gère automatiquement le CSRF)
    try {
        await axios.patch(`${baseUrl}/kitchen/items/${itemId}/status`, {
            status,
        });
        // Succès : le polling reprendra normalement
    } catch (error) {
        console.error("Erreur mise à jour:", error);
        // En cas d'erreur, restaurer l'état depuis le serveur
        await refreshData();
    } finally {
        // Réactiver le polling après un petit délai
        setTimeout(() => {
            isUpdating.value = false;
        }, 500);
    }
};

const moveItem = (itemId, newStatus) => {
    let item = null;
    const lists = [
        { ref: localWaiting, status: "waiting" },
        { ref: localPreparing, status: "preparing" },
        { ref: localReady, status: "ready" },
    ];

    // Trouver et retirer l'item
    for (const list of lists) {
        const index = list.ref.value.findIndex((i) => i.id === itemId);
        if (index !== -1) {
            item = list.ref.value.splice(index, 1)[0];
            break;
        }
    }

    if (!item) return;

    // Ajouter à la nouvelle liste
    item.kitchen_status = newStatus;
    if (newStatus === "waiting") localWaiting.value.unshift(item);
    else if (newStatus === "preparing") localPreparing.value.unshift(item);
    else if (newStatus === "ready") localReady.value.unshift(item);
    // Si 'served', on ne l'ajoute nulle part
};

const markOrderReady = async (orderId) => {
    isUpdating.value = true;
    try {
        await axios.post(`${baseUrl}/kitchen/orders/${orderId}/ready`);
        await refreshData();
    } catch (error) {
        console.error("Erreur:", error);
    } finally {
        setTimeout(() => {
            isUpdating.value = false;
        }, 500);
    }
};

const markNotificationRead = async (notificationId) => {
    const notification = localNotifications.value.find(
        (n) => n.id === notificationId
    );
    if (notification) notification.is_read = true;

    try {
        await axios.patch(
            `${baseUrl}/kitchen/notifications/${notificationId}/read`
        );
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

// ==================== Helpers ====================

const calculateAvgTime = () => {
    const allItems = [...localWaiting.value, ...localPreparing.value];
    if (allItems.length === 0) return 0;

    const totalMinutes = allItems.reduce(
        (sum, item) => sum + (item.elapsed_minutes || 0),
        0
    );
    return Math.round(totalMinutes / allItems.length);
};

const formatLastRefresh = computed(() => {
    return lastRefresh.value.toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
});
</script>

<template>
    <Head title="Cuisine" />

    <MainLayout
        page-title="Cuisine"
        page-description="Gestion des commandes en cuisine"
    >
        <template #header-actions>
            <div v-if="hasActiveSession" class="flex items-center gap-3">
                <!-- Indicateur de rafraîchissement -->
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <div
                        class="w-2 h-2 rounded-full"
                        :class="
                            isRefreshing
                                ? 'bg-yellow-400 animate-pulse'
                                : 'bg-green-400'
                        "
                    ></div>
                    <span>{{ formatLastRefresh }}</span>
                </div>

                <!-- Toggle son -->
                <button
                    @click="audioEnabled = !audioEnabled"
                    class="p-2 rounded-lg transition"
                    :class="
                        audioEnabled
                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                    "
                    :title="audioEnabled ? 'Son activé' : 'Son désactivé'"
                >
                    <svg
                        v-if="audioEnabled"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                            clip-rule="evenodd"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
                        />
                    </svg>
                </button>

                <!-- Toggle vue -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button
                        @click="viewMode = 'kanban'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                        :class="
                            viewMode === 'kanban'
                                ? 'bg-white shadow text-gray-800'
                                : 'text-gray-500 hover:text-gray-700'
                        "
                    >
                        Kanban
                    </button>
                    <button
                        @click="viewMode = 'orders'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                        :class="
                            viewMode === 'orders'
                                ? 'bg-white shadow text-gray-800'
                                : 'text-gray-500 hover:text-gray-700'
                        "
                    >
                        Commandes
                    </button>
                </div>

                <!-- Notifications -->
                <NotificationDropdown
                    :notifications="localNotifications"
                    @mark-read="markNotificationRead"
                    @mark-all-read="markAllNotificationsRead"
                />

                <!-- Bouton refresh manuel -->
                <button
                    @click="refreshData"
                    :disabled="isRefreshing"
                    class="p-2 bg-white rounded-lg shadow hover:bg-gray-50 transition disabled:opacity-50"
                >
                    <svg
                        class="w-5 h-5 text-gray-600"
                        :class="{ 'animate-spin': isRefreshing }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <!-- Contrôle de Session -->
                <KitchenSessionControl @session-changed="onSessionChanged" />

                <!-- Contenu visible uniquement si session active -->
                <template v-if="hasActiveSession">
                    <!-- Statistiques -->
                    <KitchenStats
                        :waiting="stats.waiting"
                        :preparing="stats.preparing"
                        :ready="stats.ready"
                        :avg-time="stats.avgTime"
                    />

                    <!-- Vue Kanban -->
                    <div
                        v-if="viewMode === 'kanban'"
                        class="grid grid-cols-1 md:grid-cols-3 gap-6"
                    >
                        <!-- Colonne En attente -->
                        <KitchenColumn
                            title="En attente"
                            icon="⏳"
                            status="waiting"
                            color="yellow"
                            :items="localWaiting"
                            empty-text="Aucun plat en attente"
                            @update-status="updateStatus"
                        />

                        <!-- Colonne En préparation -->
                        <KitchenColumn
                            title="En préparation"
                            icon="🔥"
                            status="preparing"
                            color="orange"
                            :items="localPreparing"
                            empty-text="Aucun plat en préparation"
                            @update-status="updateStatus"
                        />

                        <!-- Colonne Prêt -->
                        <KitchenColumn
                            title="Prêt à servir"
                            icon="✅"
                            status="ready"
                            color="green"
                            :items="localReady"
                            empty-text="Aucun plat prêt"
                            @update-status="updateStatus"
                        />
                    </div>

                    <!-- Vue par commandes -->
                    <div v-else class="space-y-6">
                        <OrderCard
                            v-for="order in localOrders"
                            :key="order.id"
                            :order="order"
                            @update-status="updateStatus"
                            @mark-ready="markOrderReady"
                        />

                        <div
                            v-if="localOrders.length === 0"
                            class="text-center py-16 bg-white rounded-xl shadow"
                        >
                            <div class="text-6xl mb-4">🍳</div>
                            <h3
                                class="text-xl font-semibold text-gray-700 mb-2"
                            >
                                Aucune commande en cours
                            </h3>
                            <p class="text-gray-500">
                                Les nouvelles commandes apparaîtront ici
                                automatiquement
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </MainLayout>
</template>
