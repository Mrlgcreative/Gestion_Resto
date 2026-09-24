<template>
    <Head title="Sessions de Cuisine" />

    <MainLayout
        page-title="Sessions de Cuisine"
        page-description="Historique des sessions et rapports"
    >
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- En-tête avec lien retour -->
                <div class="mb-6 flex items-center justify-between">
                    <Link
                        href="/kitchen"
                        class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            ></path>
                        </svg>
                        Retour à la cuisine
                    </Link>
                </div>

                <!-- Liste des sessions -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Historique des sessions
                        </h3>
                    </div>

                    <div
                        v-if="sessions.data.length === 0"
                        class="p-12 text-center"
                    >
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                        <p class="text-gray-500">Aucune session enregistrée</p>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="session in sessions.data"
                            :key="session.id"
                            class="p-6 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            Session #{{ session.id }}
                                        </h4>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="
                                                session.status === 'open'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            "
                                        >
                                            {{
                                                session.status === "open"
                                                    ? "En cours"
                                                    : "Fermée"
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3"
                                    >
                                        <div>
                                            <div class="text-xs text-gray-500">
                                                Cuisinier
                                            </div>
                                            <div class="font-medium">
                                                {{ session.user.name }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">
                                                Ouverture
                                            </div>
                                            <div class="font-medium">
                                                {{
                                                    formatDateTime(
                                                        session.opened_at
                                                    )
                                                }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">
                                                Fermeture
                                            </div>
                                            <div class="font-medium">
                                                {{
                                                    session.closed_at
                                                        ? formatDateTime(
                                                              session.closed_at
                                                          )
                                                        : "-"
                                                }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">
                                                Durée
                                            </div>
                                            <div class="font-medium">
                                                {{
                                                    formatDuration(
                                                        session.opened_at,
                                                        session.closed_at
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-6 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500"
                                                >Commandes:</span
                                            >
                                            <span
                                                class="font-semibold text-blue-600"
                                            >
                                                {{
                                                    session.total_orders_completed
                                                }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500"
                                                >Plats:</span
                                            >
                                            <span
                                                class="font-semibold text-green-600"
                                            >
                                                {{
                                                    session.total_items_prepared
                                                }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500"
                                                >Temps moyen:</span
                                            >
                                            <span
                                                class="font-semibold text-purple-600"
                                            >
                                                {{
                                                    Math.round(
                                                        session.average_preparation_time ||
                                                            0
                                                    )
                                                }}
                                                min
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="session.notes"
                                        class="mt-3 text-sm text-gray-600 italic"
                                    >
                                        Note: {{ session.notes }}
                                    </div>
                                </div>

                                <div class="flex gap-2 ml-4">
                                    <Link
                                        :href="`/kitchen/sessions/${session.id}`"
                                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition text-sm font-medium"
                                    >
                                        Détails
                                    </Link>
                                    <a
                                        v-if="session.status === 'closed'"
                                        :href="`/kitchen/sessions/${session.id}/report`"
                                        target="_blank"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm font-medium flex items-center gap-2"
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
                                        PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="sessions.links.length > 3"
                        class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <Link
                                v-for="(link, index) in sessions.links"
                                :key="index"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm font-medium transition',
                                    link.active
                                        ? 'bg-red-600 text-white'
                                        : link.url
                                        ? 'bg-white text-gray-700 hover:bg-gray-100'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";

defineProps({
    sessions: { type: Object, required: true },
});

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const formatDuration = (start, end) => {
    const startDate = new Date(start);
    const endDate = end ? new Date(end) : new Date();
    const minutes = Math.floor((endDate - startDate) / 60000);

    if (minutes < 60) {
        return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
};
</script>
