<template>
    <MainLayout page-title="Dashboard" :page-description="getWelcomeMessage()">
        <template #header-actions>
            <Button variant="ghost" size="sm" @click="logout">
                <svg
                    class="h-5 w-5 mr-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                    />
                </svg>
                Déconnexion
            </Button>
        </template>

        <!-- Session Status Card (Caissier) -->
        <div v-if="permissions.canOpenSession" class="mb-6">
            <Card
                :class="
                    currentSession
                        ? 'border-green-500 bg-green-50'
                        : 'border-yellow-500 bg-yellow-50'
                "
            >
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center',
                                currentSession
                                    ? 'bg-green-100'
                                    : 'bg-yellow-100',
                            ]"
                        >
                            <svg
                                class="h-6 w-6"
                                :class="
                                    currentSession
                                        ? 'text-green-600'
                                        : 'text-yellow-600'
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="font-semibold"
                                :class="
                                    currentSession
                                        ? 'text-green-800'
                                        : 'text-yellow-800'
                                "
                            >
                                {{
                                    currentSession
                                        ? "Session de caisse active"
                                        : "Aucune session active"
                                }}
                            </p>
                            <p
                                class="text-sm"
                                :class="
                                    currentSession
                                        ? 'text-green-600'
                                        : 'text-yellow-600'
                                "
                            >
                                {{
                                    currentSession
                                        ? `Ouverte depuis ${formatTime(
                                              currentSession.opened_at
                                          )} - ${currentSession.currency}`
                                        : "Ouvrez une session pour commencer à encaisser"
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="currentSession"
                            variant="success"
                            @click="$inertia.visit('/orders/create')"
                        >
                            <svg
                                class="h-5 w-5 mr-2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Nouvelle commande
                        </Button>
                        <Button
                            v-if="currentSession"
                            variant="secondary"
                            @click="
                                $inertia.visit(`/sessions/${currentSession.id}`)
                            "
                        >
                            Voir la session
                        </Button>
                        <Button
                            v-else
                            variant="primary"
                            @click="$inertia.visit('/sessions/create')"
                        >
                            <svg
                                class="h-5 w-5 mr-2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Ouvrir une session
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Kitchen Dashboard Section (Cuisinier) -->
        <div v-if="kitchenStats" class="mb-6">
            <!-- Kitchen Session Status -->
            <Card
                :class="
                    kitchenStats.currentSession
                        ? 'border-green-500 bg-green-50'
                        : 'border-orange-500 bg-orange-50'
                "
                class="mb-6"
            >
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'w-14 h-14 rounded-full flex items-center justify-center',
                                kitchenStats.currentSession
                                    ? 'bg-green-100'
                                    : 'bg-orange-100',
                            ]"
                        >
                            <span class="text-2xl">{{
                                kitchenStats.currentSession ? "👨‍🍳" : "🔥"
                            }}</span>
                        </div>
                        <div>
                            <p
                                class="font-semibold text-lg"
                                :class="
                                    kitchenStats.currentSession
                                        ? 'text-green-800'
                                        : 'text-orange-800'
                                "
                            >
                                {{
                                    kitchenStats.currentSession
                                        ? "Session Cuisine Active"
                                        : "Aucune session de cuisine"
                                }}
                            </p>
                            <p
                                class="text-sm"
                                :class="
                                    kitchenStats.currentSession
                                        ? 'text-green-600'
                                        : 'text-orange-600'
                                "
                            >
                                {{
                                    kitchenStats.currentSession
                                        ? `En cours depuis ${formatDuration(
                                              kitchenStats.currentSession
                                                  .duration_minutes
                                          )}`
                                        : "Allez en cuisine pour ouvrir une session de travail"
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="primary"
                            @click="$inertia.visit('/kitchen')"
                        >
                            <span class="mr-2">🍳</span>
                            {{
                                kitchenStats.currentSession
                                    ? "Voir la cuisine"
                                    : "Aller en cuisine"
                            }}
                        </Button>
                        <Button
                            v-if="recentKitchenSessions.length > 0"
                            variant="secondary"
                            @click="$inertia.visit('/kitchen/sessions')"
                        >
                            Historique
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Kitchen Stats Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6"
            >
                <StatCard
                    label="Plats préparés aujourd'hui"
                    :value="kitchenStats.totalItemsToday"
                    format="number"
                    variant="success"
                >
                    <template #icon>
                        <span class="text-2xl">🍽️</span>
                    </template>
                </StatCard>

                <StatCard
                    label="Commandes complétées"
                    :value="kitchenStats.totalOrdersToday"
                    format="number"
                    variant="primary"
                >
                    <template #icon>
                        <span class="text-2xl">✅</span>
                    </template>
                </StatCard>

                <StatCard
                    label="Temps en cuisine"
                    :value="formatDuration(kitchenStats.totalMinutesToday)"
                    format="text"
                >
                    <template #icon>
                        <span class="text-2xl">⏱️</span>
                    </template>
                </StatCard>

                <StatCard
                    label="Sessions aujourd'hui"
                    :value="kitchenStats.sessionsToday"
                    format="number"
                >
                    <template #icon>
                        <span class="text-2xl">📋</span>
                    </template>
                </StatCard>
            </div>

            <!-- Current Session Stats (if active) -->
            <Card
                v-if="kitchenStats.currentSession"
                title="Session en cours"
                class="mb-6"
            >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200"
                    >
                        <div class="text-blue-600 text-sm font-medium mb-1">
                            Commandes complétées
                        </div>
                        <div class="text-3xl font-bold text-blue-900">
                            {{
                                kitchenStats.currentSession.stats
                                    .total_orders_completed
                            }}
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200"
                    >
                        <div class="text-green-600 text-sm font-medium mb-1">
                            Plats préparés
                        </div>
                        <div class="text-3xl font-bold text-green-900">
                            {{
                                kitchenStats.currentSession.stats
                                    .total_items_prepared
                            }}
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200"
                    >
                        <div class="text-purple-600 text-sm font-medium mb-1">
                            Temps moyen
                        </div>
                        <div class="text-3xl font-bold text-purple-900">
                            {{
                                Math.round(
                                    kitchenStats.currentSession.stats
                                        .average_preparation_time
                                )
                            }}
                            min
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Kitchen Sessions -->
            <Card
                v-if="recentKitchenSessions.length > 0"
                title="Sessions récentes"
            >
                <template #actions>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="$inertia.visit('/kitchen/sessions')"
                        >Voir tout</Button
                    >
                </template>
                <div class="space-y-3">
                    <div
                        v-for="session in recentKitchenSessions"
                        :key="session.id"
                        class="flex items-center justify-between p-3 rounded-lg"
                        :class="
                            session.is_open
                                ? 'bg-green-50 border border-green-200'
                                : 'bg-gray-50'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center',
                                    session.is_open
                                        ? 'bg-green-100'
                                        : 'bg-gray-200',
                                ]"
                            >
                                <span>{{ session.is_open ? "🟢" : "⏹️" }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ formatDate(session.opened_at) }}
                                    <span
                                        v-if="session.is_open"
                                        class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full"
                                        >En cours</span
                                    >
                                </p>
                                <p class="text-sm text-gray-500">
                                    Durée:
                                    {{
                                        formatDuration(session.duration_minutes)
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">
                                {{ session.stats.total_items_prepared }} plats
                            </p>
                            <p class="text-sm text-gray-500">
                                {{
                                    session.stats.total_orders_completed
                                }}
                                commandes
                            </p>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Ventes du jour USD -->
            <StatCard
                v-if="stats.todaySales?.USD !== undefined"
                label="Ventes du jour (USD)"
                :value="stats.todaySales.USD"
                format="currency"
                currency="USD"
                variant="primary"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6 text-primary-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>
            </StatCard>

            <!-- Ventes du jour CDF -->
            <StatCard
                v-if="
                    stats.todaySales?.CDF !== undefined &&
                    stats.todaySales.CDF > 0
                "
                label="Ventes du jour (FC)"
                :value="stats.todaySales.CDF"
                format="currency"
                currency="CDF"
                variant="primary"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6 text-primary-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>
            </StatCard>

            <!-- Commandes du jour -->
            <StatCard
                v-if="stats.todayOrders !== undefined"
                label="Commandes du jour"
                :value="stats.todayOrders"
                format="number"
                variant="success"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6 text-green-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                        />
                    </svg>
                </template>
            </StatCard>

            <!-- Commandes en attente -->
            <StatCard
                v-if="stats.pendingOrders !== undefined"
                label="En attente"
                :value="stats.pendingOrders"
                format="number"
                :variant="stats.pendingOrders > 0 ? 'warning' : 'default'"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6"
                        :class="
                            stats.pendingOrders > 0
                                ? 'text-yellow-600'
                                : 'text-gray-600'
                        "
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
                </template>
            </StatCard>

            <!-- Produits actifs - Admin/Gérant -->
            <StatCard
                v-if="stats.activeProducts !== undefined"
                label="Produits actifs"
                :value="stats.activeProducts"
                format="number"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6 text-gray-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>
                </template>
            </StatCard>

            <!-- Stocks bas - Admin/Gérant -->
            <StatCard
                v-if="stats.lowStock !== undefined"
                label="Stocks bas"
                :value="stats.lowStock"
                format="number"
                :variant="stats.lowStock > 0 ? 'danger' : 'default'"
            >
                <template #icon>
                    <svg
                        class="h-6 w-6"
                        :class="
                            stats.lowStock > 0
                                ? 'text-red-600'
                                : 'text-gray-600'
                        "
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </template>
            </StatCard>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <Card v-if="permissions.canViewOrders" title="Commandes récentes">
                <template #actions>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="$inertia.visit('/orders')"
                        >Voir tout</Button
                    >
                </template>

                <Table
                    v-if="recentOrders.length > 0"
                    :columns="orderColumns"
                    :data="recentOrders"
                >
                    <template #cell-server="{ row }">
                        {{ row.server?.name || "-" }}
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="getStatusVariant(value)">
                            {{ getStatusLabel(value) }}
                        </Badge>
                    </template>
                    <template #cell-total_amount="{ row }">
                        {{ formatOrderTotal(row) }}
                    </template>
                </Table>

                <EmptyState
                    v-else
                    title="Aucune commande"
                    description="Aucune commande récente à afficher"
                />
            </Card>

            <!-- Top Products - Admin/Gérant only -->
            <Card
                v-if="permissions.canViewProducts && topProducts.length > 0"
                title="Produits populaires"
            >
                <div class="space-y-4">
                    <div
                        v-for="product in topProducts"
                        :key="product.id"
                        class="flex items-center gap-4"
                    >
                        <div
                            class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                        >
                            <span class="text-lg">🍽️</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-medium text-gray-900 truncate"
                            >
                                {{ product.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ product.orders_count }} commandes
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ formatProductPrice(product) }}
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Quick Actions for Caissier -->
            <Card
                v-if="
                    permissions.canOpenSession && !permissions.canViewProducts
                "
                title="Actions rapides"
            >
                <div class="grid grid-cols-2 gap-4">
                    <Button
                        v-if="currentSession"
                        variant="primary"
                        class="h-24 flex-col"
                        @click="$inertia.visit('/orders/create')"
                    >
                        <svg
                            class="h-8 w-8 mb-2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        Nouvelle commande
                    </Button>
                    <Button
                        variant="secondary"
                        class="h-24 flex-col"
                        @click="$inertia.visit('/orders')"
                    >
                        <svg
                            class="h-8 w-8 mb-2"
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
                        Mes commandes
                    </Button>
                    <Button
                        variant="secondary"
                        class="h-24 flex-col"
                        @click="$inertia.visit('/sessions')"
                    >
                        <svg
                            class="h-8 w-8 mb-2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        Mes sessions
                    </Button>
                </div>
            </Card>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";
import { Button, StatCard, Card, Table, Badge, EmptyState } from "@/Components";

const page = usePage();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            currency: "USD",
        }),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    currentSession: {
        type: Object,
        default: null,
    },
    exchangeRates: {
        type: Array,
        default: () => [],
    },
    kitchenStats: {
        type: Object,
        default: null,
    },
    currentKitchenSession: {
        type: Object,
        default: null,
    },
    recentKitchenSessions: {
        type: Array,
        default: () => [],
    },
});

// Utiliser les permissions partagées globalement
const permissions = computed(() => page.props.permissions || {});

const orderColumns = [
    { key: "id", label: "#" },
    { key: "server", label: "Serveur" },
    { key: "total_amount", label: "Total" },
    { key: "status", label: "Statut" },
];

const getWelcomeMessage = () => {
    const hour = new Date().getHours();
    if (hour < 12) return "Bonjour ! Bonne matinée";
    if (hour < 18) return "Bon après-midi !";
    return "Bonsoir !";
};

const logout = () => {
    router.post("/logout");
};

const formatTime = (date) => {
    return new Date(date).toLocaleString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleString("fr-FR", {
        day: "2-digit",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDuration = (minutes) => {
    if (!minutes || minutes < 1) return "0 min";
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
};

// Obtenir le taux de change pour une devise (par rapport à USD)
const getExchangeRate = (currencyCode) => {
    if (currencyCode === "USD") return 1;
    const rate = props.exchangeRates?.find(
        (r) => r.currency?.code === currencyCode
    );
    return rate ? parseFloat(rate.rate) : 1;
};

// Obtenir la devise d'une commande
const getOrderCurrency = (order) => {
    return order.currency_relation?.code || order.currency || "USD";
};

// Obtenir la devise d'un produit
const getProductCurrency = (product) => {
    return product?.currency?.code || "USD";
};

// Calculer le total d'une commande en convertissant les articles
const calculateOrderTotal = (order) => {
    if (!order.items || order.items.length === 0)
        return order.total_amount || 0;

    const orderCurrency = getOrderCurrency(order);

    return order.items.reduce((sum, item) => {
        const itemTotal = item.unit_price * item.quantity;
        const productCurrency = getProductCurrency(item.product);

        if (productCurrency === orderCurrency) {
            return sum + itemTotal;
        }

        // Conversion via USD comme pivot
        const fromRate = getExchangeRate(productCurrency);
        const toRate = getExchangeRate(orderCurrency);
        const amountInUSD = itemTotal / fromRate;
        return sum + amountInUSD * toRate;
    }, 0);
};

// Formater un prix avec sa devise
const formatPrice = (price, currency = "USD") => {
    const currencyCode = currency || "USD";
    try {
        if (currencyCode === "CDF") {
            return (
                new Intl.NumberFormat("fr-FR").format(
                    Math.round(parseFloat(price) || 0)
                ) + " FC"
            );
        }
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: currencyCode,
        }).format(price || 0);
    } catch (e) {
        return `${parseFloat(price || 0).toFixed(2)} ${currencyCode}`;
    }
};

// Formater le total d'une commande
const formatOrderTotal = (order) => {
    const total = calculateOrderTotal(order);
    const currency = getOrderCurrency(order);
    return formatPrice(total, currency);
};

// Formater le prix d'un produit
const formatProductPrice = (product) => {
    const currency = getProductCurrency(product);
    return formatPrice(product.selling_price, currency);
};

const formatCurrency = (value) => {
    const currency = props.stats.currency || "USD";
    return formatPrice(value, currency);
};

const getStatusVariant = (status) => {
    const variants = {
        pending: "warning",
        paid: "success",
        canceled: "danger",
    };
    return variants[status] || "default";
};

const getStatusLabel = (status) => {
    const labels = {
        pending: "En attente",
        paid: "Payée",
        canceled: "Annulée",
    };
    return labels[status] || status;
};
</script>
