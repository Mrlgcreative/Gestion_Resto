<template>
    <Head :title="`Session #${session.id}`" />

    <MainLayout
        :page-title="`Session #${session.id} - ${session.user_name}`"
        page-description="Détails de la session de cuisine"
    >
        <template #header-actions>
            <div class="flex items-center gap-2">
                <Link
                    href="/kitchen/sessions"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors"
                >
                    ← Retour
                </Link>
                <a
                    v-if="session.status === 'closed'"
                    :href="`/kitchen/sessions/${session.id}/report`"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors inline-flex items-center gap-2"
                    download
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    Télécharger PDF
                </a>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Informations de la session -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ session.user_name }}
                            </h2>
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-2"
                                :class="
                                    session.status === 'open'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800'
                                "
                            >
                                {{
                                    session.status === "open"
                                        ? "🔥 Session en cours"
                                        : "✓ Session fermée"
                                }}
                            </span>
                        </div>
                        <div class="text-right text-sm text-gray-600">
                            <div>
                                Ouverte le
                                {{ formatDateTime(session.opened_at) }}
                            </div>
                            <div v-if="session.closed_at">
                                Fermée le
                                {{ formatDateTime(session.closed_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div
                            class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200"
                        >
                            <div class="text-blue-600 text-sm font-medium mb-1">
                                Commandes Complétées
                            </div>
                            <div class="text-3xl font-bold text-blue-900">
                                {{ session.stats.total_orders_completed }}
                            </div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200"
                        >
                            <div
                                class="text-green-600 text-sm font-medium mb-1"
                            >
                                Plats Préparés
                            </div>
                            <div class="text-3xl font-bold text-green-900">
                                {{ session.stats.total_items_prepared }}
                            </div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200"
                        >
                            <div
                                class="text-purple-600 text-sm font-medium mb-1"
                            >
                                Temps Moyen
                            </div>
                            <div class="text-3xl font-bold text-purple-900">
                                {{
                                    Math.round(
                                        session.stats.average_preparation_time
                                    )
                                }}
                                min
                            </div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200"
                        >
                            <div
                                class="text-orange-600 text-sm font-medium mb-1"
                            >
                                Min - Max
                            </div>
                            <div class="text-xl font-bold text-orange-900">
                                {{ session.stats.min_preparation_time }} -
                                {{ session.stats.max_preparation_time }} min
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div
                        v-if="session.notes"
                        class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded"
                    >
                        <h3 class="text-sm font-semibold text-yellow-800 mb-2">
                            📝 Notes de session
                        </h3>
                        <p class="text-sm text-yellow-800">
                            {{ session.notes }}
                        </p>
                    </div>
                </div>

                <!-- Liste des plats préparés -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Plats Préparés ({{ items.length }})
                        </h3>
                    </div>

                    <div v-if="items.length === 0" class="p-12 text-center">
                        <svg
                            class="w-16 h-16 mx-auto text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            ></path>
                        </svg>
                        <p class="text-gray-600">
                            Aucun plat préparé durant cette session
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Commande
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Table
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Plat
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Qté
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Commandé
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Prêt
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Temps Prép.
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="item in items"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        #{{ item.order_number }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                    >
                                        Table {{ item.table_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ item.product_name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900"
                                    >
                                        {{ item.quantity }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-600"
                                    >
                                        {{ formatTime(item.created_at) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-600"
                                    >
                                        {{ formatTime(item.ready_at) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold"
                                    >
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="
                                                getPreparationTimeClass(
                                                    item.preparation_time
                                                )
                                            "
                                        >
                                            {{ item.preparation_time }} min
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
});

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatTime = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getPreparationTimeClass = (time) => {
    if (time === null) return "bg-gray-100 text-gray-800";
    if (time <= 10) return "bg-green-100 text-green-800";
    if (time <= 20) return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
};
</script>
