<template>
    <div class="kitchen-session-control">
        <!-- Écran de blocage si pas de session -->
        <div
            v-if="!hasSession"
            class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 rounded-2xl shadow-2xl p-12 mb-6"
        >
            <div class="max-w-lg mx-auto text-center">
                <div class="mb-8">
                    <div
                        class="w-24 h-24 mx-auto bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center shadow-lg animate-pulse"
                    >
                        <svg
                            class="w-12 h-12 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                </div>

                <h2 class="text-3xl font-bold text-white mb-4">
                    Bienvenue en Cuisine ! 👨‍🍳
                </h2>
                <p class="text-gray-300 text-lg mb-8">
                    Pour commencer à travailler et voir les commandes, vous
                    devez d'abord ouvrir une session de travail.
                </p>

                <div class="bg-gray-800/50 rounded-xl p-6 mb-8 text-left">
                    <h3
                        class="text-white font-semibold mb-3 flex items-center gap-2"
                    >
                        <span class="text-yellow-400">📋</span> Pourquoi ouvrir
                        une session ?
                    </h3>
                    <ul class="text-gray-400 space-y-2 text-sm">
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            Suivre vos performances (plats préparés, temps
                            moyen)
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            Générer des rapports détaillés de votre travail
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            Accéder à toutes les fonctionnalités de la cuisine
                        </li>
                    </ul>
                </div>

                <button
                    @click="openSession"
                    :disabled="loading"
                    class="bg-gradient-to-r from-red-600 to-orange-500 hover:from-red-700 hover:to-orange-600 text-white font-bold py-4 px-12 rounded-xl transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg text-lg"
                >
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg
                            class="animate-spin h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Ouverture en cours...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        🔥 Ouvrir ma Session de Travail
                    </span>
                </button>
            </div>
        </div>

        <!-- Barre de session active (plus compacte) -->
        <div v-else class="bg-white rounded-lg shadow-lg p-4 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 bg-green-500 rounded-full animate-pulse"
                        ></span>
                        <span class="font-semibold text-gray-800"
                            >Session Active</span
                        >
                        <span class="text-gray-500">•</span>
                        <span class="text-gray-600">{{
                            formatDuration(session.duration_minutes)
                        }}</span>
                    </div>

                    <div class="hidden md:flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-500">📦</span>
                            <span class="font-medium text-gray-700">{{
                                session.stats.total_orders_completed
                            }}</span>
                            <span class="text-gray-500">commandes</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">🍽️</span>
                            <span class="font-medium text-gray-700">{{
                                session.stats.total_items_prepared
                            }}</span>
                            <span class="text-gray-500">plats</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-500">⏱️</span>
                            <span class="font-medium text-gray-700"
                                >{{
                                    Math.round(
                                        session.stats.average_preparation_time
                                    )
                                }}
                                min</span
                            >
                            <span class="text-gray-500">moy.</span>
                        </div>
                    </div>
                </div>

                <button
                    @click="showCloseDialog = true"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-sm"
                >
                    Fermer la Session
                </button>
            </div>
        </div>

        <!-- Dialog pour fermer la session -->
        <teleport to="body">
            <div
                v-if="showCloseDialog"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click.self="showCloseDialog = false"
            >
                <div
                    class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4"
                >
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">
                        Fermer la session ?
                    </h3>

                    <div class="mb-6">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            Notes (optionnel)
                        </label>
                        <textarea
                            v-model="closeNotes"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                            rows="4"
                            placeholder="Ajoutez des notes sur cette session..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="showCloseDialog = false"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition-colors duration-200"
                        >
                            Annuler
                        </button>
                        <button
                            @click="closeSession"
                            :disabled="loading"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="loading">Fermeture...</span>
                            <span v-else>Confirmer</span>
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import axios from "axios";

const emit = defineEmits(["session-changed"]);

const session = ref(null);
const hasSession = ref(false);
const loading = ref(false);
const showCloseDialog = ref(false);
const closeNotes = ref("");
let refreshInterval = null;

// Émettre un événement quand la session change
watch(hasSession, (newValue) => {
    emit("session-changed", newValue);
});

const fetchSession = async () => {
    try {
        const response = await axios.get("/kitchen/sessions/current");
        session.value = response.data.session;
        hasSession.value = response.data.has_open_session;
    } catch (error) {
        console.error("Erreur lors de la récupération de la session:", error);
    }
};

const openSession = async () => {
    loading.value = true;
    try {
        await axios.post("/kitchen/sessions/open");
        await new Promise((resolve) => setTimeout(resolve, 300));
        await fetchSession();
    } catch (error) {
        console.error("Erreur lors de l'ouverture de la session:", error);
    } finally {
        loading.value = false;
    }
};

const closeSession = async () => {
    loading.value = true;
    try {
        await axios.post("/kitchen/sessions/close", {
            notes: closeNotes.value,
        });
        showCloseDialog.value = false;
        closeNotes.value = "";
        await new Promise((resolve) => setTimeout(resolve, 300));
        await fetchSession();
    } catch (error) {
        console.error("Erreur lors de la fermeture de la session:", error);
    } finally {
        loading.value = false;
    }
};

const formatDuration = (minutes) => {
    if (minutes < 60) {
        return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
};

onMounted(() => {
    fetchSession();
    // Rafraîchir toutes les 30 secondes
    refreshInterval = setInterval(fetchSession, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
