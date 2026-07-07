<template>
    <MainLayout page-title="Dashboard" :page-description="getWelcomeMessage()">
        <!-- Session Status Card -->
        <div v-if="permissions.canOpenSession" class="mb-6">
            <div
                class="rounded-xl border shadow-sm p-5 transition-all"
                :class="currentSession
                    ? 'bg-emerald-50 border-emerald-200'
                    : 'bg-amber-50 border-amber-200'"
            >
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                            :class="currentSession ? 'bg-emerald-100' : 'bg-amber-100'">
                            <CurrencyDollarIcon class="w-6 h-6" :class="currentSession ? 'text-emerald-600' : 'text-amber-600'" />
                        </div>
                        <div>
                            <p class="font-semibold" :class="currentSession ? 'text-emerald-800' : 'text-amber-800'">
                                {{ currentSession ? "Session de caisse active" : "Aucune session active" }}
                            </p>
                            <p class="text-sm" :class="currentSession ? 'text-emerald-600' : 'text-amber-600'">
                                {{ currentSession
                                    ? `Ouverte depuis ${formatTime(currentSession.opened_at)} - ${currentSession.currency}`
                                    : "Ouvrez une session pour commencer à encaisser" }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="currentSession" variant="primary" @click="$inertia.visit('/orders/create')">
                            <PlusIcon class="w-5 h-5 mr-1.5" />
                            Nouvelle commande
                        </Button>
                        <Button v-if="currentSession" variant="secondary" @click="$inertia.visit(`/sessions/${currentSession.id}`)">
                            Voir la session
                        </Button>
                        <Button v-else variant="primary" @click="$inertia.visit('/sessions/create')">
                            <PlusIcon class="w-5 h-5 mr-1.5" />
                            Ouvrir une session
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kitchen Dashboard Section -->
        <div v-if="kitchenStats" class="mb-6">
            <div class="rounded-xl border shadow-sm p-5 transition-all mb-6"
                :class="kitchenStats.currentSession
                    ? 'bg-emerald-50 border-emerald-200'
                    : 'bg-orange-50 border-orange-200'">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                            :class="kitchenStats.currentSession ? 'bg-emerald-100' : 'bg-orange-100'">
                            <FireIcon class="w-6 h-6" :class="kitchenStats.currentSession ? 'text-emerald-600' : 'text-orange-600'" />
                        </div>
                        <div>
                            <p class="font-semibold text-lg" :class="kitchenStats.currentSession ? 'text-emerald-800' : 'text-orange-800'">
                                {{ kitchenStats.currentSession ? "Session Cuisine Active" : "Aucune session de cuisine" }}
                            </p>
                            <p class="text-sm" :class="kitchenStats.currentSession ? 'text-emerald-600' : 'text-orange-600'">
                                {{ kitchenStats.currentSession
                                    ? `En cours depuis ${formatDuration(kitchenStats.currentSession.duration_minutes)}`
                                    : "Allez en cuisine pour ouvrir une session de travail" }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="primary" @click="$inertia.visit('/kitchen')">
                            <FireIcon class="w-5 h-5 mr-1.5" />
                            {{ kitchenStats.currentSession ? "Voir la cuisine" : "Aller en cuisine" }}
                        </Button>
                        <Button v-if="recentKitchenSessions.length > 0" variant="secondary" @click="$inertia.visit('/kitchen/sessions')">
                            Historique
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <StatCard label="Plats préparés aujourd'hui" :value="kitchenStats.totalItemsToday" format="number" variant="success" />
                <StatCard label="Commandes complétées" :value="kitchenStats.totalOrdersToday" format="number" variant="primary" />
                <StatCard label="Temps en cuisine" :value="formatDuration(kitchenStats.totalMinutesToday)" format="text" />
                <StatCard label="Sessions aujourd'hui" :value="kitchenStats.sessionsToday" format="number" />
            </div>

            <div v-if="kitchenStats.currentSession" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Session en cours</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <p class="text-blue-600 text-sm font-medium mb-1">Commandes complétées</p>
                        <p class="text-3xl font-bold text-blue-900">{{ kitchenStats.currentSession.stats.total_orders_completed }}</p>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                        <p class="text-emerald-600 text-sm font-medium mb-1">Plats préparés</p>
                        <p class="text-3xl font-bold text-emerald-900">{{ kitchenStats.currentSession.stats.total_items_prepared }}</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <p class="text-purple-600 text-sm font-medium mb-1">Temps moyen</p>
                        <p class="text-3xl font-bold text-purple-900">{{ Math.round(kitchenStats.currentSession.stats.average_preparation_time) }} min</p>
                    </div>
                </div>
            </div>

            <div v-if="recentKitchenSessions.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Sessions récentes</h3>
                    <Button variant="ghost" size="sm" @click="$inertia.visit('/kitchen/sessions')">Voir tout</Button>
                </div>
                <div class="space-y-3">
                    <div v-for="session in recentKitchenSessions" :key="session.id"
                        class="flex items-center justify-between p-4 rounded-lg border transition-all"
                        :class="session.is_open ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-gray-200'">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                :class="session.is_open ? 'bg-emerald-100' : 'bg-gray-200'">
                                <CircleIcon v-if="session.is_open" class="w-5 h-5 text-emerald-500" solid />
                                <StopIcon v-else class="w-5 h-5 text-gray-500" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ formatDate(session.opened_at) }}
                                    <span v-if="session.is_open" class="ml-2 text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">En cours</span>
                                </p>
                                <p class="text-sm text-gray-500">Durée: {{ formatDuration(session.duration_minutes) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">{{ session.stats.total_items_prepared }} plats</p>
                            <p class="text-sm text-gray-500">{{ session.stats.total_orders_completed }} commandes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatCard
                v-if="stats.todaySales?.USD !== undefined"
                label="Ventes du jour (USD)"
                :value="stats.todaySales.USD"
                format="currency"
                currency="USD"
                variant="primary"
            />
            <StatCard
                v-if="stats.todaySales?.CDF !== undefined && stats.todaySales.CDF > 0"
                label="Ventes du jour (FC)"
                :value="stats.todaySales.CDF"
                format="currency"
                currency="CDF"
                variant="primary"
            />
            <StatCard
                v-if="stats.todayOrders !== undefined"
                label="Commandes du jour"
                :value="stats.todayOrders"
                format="number"
                variant="success"
            />
            <StatCard
                v-if="stats.pendingOrders !== undefined"
                label="En attente"
                :value="stats.pendingOrders"
                format="number"
                :variant="stats.pendingOrders > 0 ? 'warning' : 'default'"
            />
            <StatCard
                v-if="stats.activeProducts !== undefined"
                label="Produits actifs"
                :value="stats.activeProducts"
                format="number"
            />
            <StatCard
                v-if="stats.lowStock !== undefined"
                label="Stocks bas"
                :value="stats.lowStock"
                format="number"
                :variant="stats.lowStock > 0 ? 'danger' : 'default'"
            />
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div v-if="permissions.canViewOrders" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Commandes récentes</h3>
                    <Button variant="ghost" size="sm" @click="$inertia.visit('/orders')">Voir tout</Button>
                </div>
                <Table v-if="recentOrders.length > 0" :columns="orderColumns" :data="recentOrders">
                    <template #cell-server="{ row }">
                        {{ row.server?.name || "-" }}
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="getStatusVariant(value)">{{ getStatusLabel(value) }}</Badge>
                    </template>
                    <template #cell-total_amount="{ row }">
                        {{ formatOrderTotal(row) }}
                    </template>
                </Table>
                <EmptyState v-else title="Aucune commande" description="Aucune commande récente à afficher" />
            </div>

            <div v-if="permissions.canViewProducts && topProducts.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Produits populaires</h3>
                <div class="space-y-4">
                    <div v-for="product in topProducts" :key="product.id" class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <CubeIcon class="w-6 h-6 text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
                            <p class="text-xs text-gray-500">{{ product.orders_count }} commandes</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">{{ formatProductPrice(product) }}</p>
                    </div>
                </div>
            </div>

            <div v-if="permissions.canOpenSession && !permissions.canViewProducts" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h3>
                <div class="grid grid-cols-2 gap-4">
                    <Button v-if="currentSession" variant="primary" class="h-24 flex-col" @click="$inertia.visit('/orders/create')">
                        <PlusIcon class="w-8 h-8 mb-2" />
                        Nouvelle commande
                    </Button>
                    <Button variant="secondary" class="h-24 flex-col" @click="$inertia.visit('/orders')">
                        <ClipboardDocumentListIcon class="w-8 h-8 mb-2" />
                        Mes commandes
                    </Button>
                    <Button variant="secondary" class="h-24 flex-col" @click="$inertia.visit('/sessions')">
                        <CurrencyDollarIcon class="w-8 h-8 mb-2" />
                        Mes sessions
                    </Button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";
import { Button, StatCard, Card, Table, Badge, EmptyState } from "@/Components";
import {
    CurrencyDollarIcon,
    PlusIcon,
    FireIcon,
    CubeIcon,
    ClipboardDocumentListIcon,
} from "@heroicons/vue/24/outline";

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

const getExchangeRate = (currencyCode) => {
    if (currencyCode === "USD") return 1;
    const rate = props.exchangeRates?.find(
        (r) => r.currency?.code === currencyCode
    );
    return rate ? parseFloat(rate.rate) : 1;
};

const getOrderCurrency = (order) => {
    return order.currency_relation?.code || order.currency || "USD";
};

const getProductCurrency = (product) => {
    return product?.currency?.code || "USD";
};

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

        const fromRate = getExchangeRate(productCurrency);
        const toRate = getExchangeRate(orderCurrency);
        const amountInUSD = itemTotal / fromRate;
        return sum + amountInUSD * toRate;
    }, 0);
};

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

const formatOrderTotal = (order) => {
    const total = calculateOrderTotal(order);
    const currency = getOrderCurrency(order);
    return formatPrice(total, currency);
};

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
