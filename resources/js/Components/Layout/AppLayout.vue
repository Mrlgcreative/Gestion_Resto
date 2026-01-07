<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 transition-transform duration-300 lg:translate-x-0 flex flex-col"
        >
            <!-- Logo + Close Button -->
            <div
                class="flex items-center justify-between h-16 bg-gray-800 px-4 flex-shrink-0"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-8 h-8 lg:w-10 lg:h-10 rounded-full bg-primary-600 flex items-center justify-center overflow-hidden ring-2 ring-primary-400 flex-shrink-0"
                    >
                        <img
                            v-if="appLogo"
                            :src="`${storageUrl}/${appLogo}`"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-sm lg:text-lg">🍽️</span>
                    </div>
                    <span
                        class="text-base lg:text-lg font-bold text-white truncate"
                        >{{ appName }}</span
                    >
                </div>
                <!-- Close button (mobile only) -->
                <button
                    type="button"
                    class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors"
                    @click="sidebarOpen = false"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Navigation (scrollable) -->
            <nav
                class="flex-1 overflow-y-auto py-4 px-3 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent"
            >
                <div class="space-y-1">
                    <slot name="sidebar" />
                </div>
            </nav>

            <!-- User Info -->
            <div
                class="flex-shrink-0 p-3 lg:p-4 border-t border-gray-700 bg-gray-900"
            >
                <div class="flex items-center gap-2 lg:gap-3">
                    <div
                        class="w-8 h-8 lg:w-10 lg:h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-semibold text-sm lg:text-base flex-shrink-0"
                    >
                        {{ userInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-xs lg:text-sm font-medium text-white truncate"
                        >
                            {{ userName }}
                        </p>
                        <p
                            class="text-[10px] lg:text-xs text-gray-400 truncate"
                        >
                            {{ userRole }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Header -->
            <header class="sticky top-0 z-20 bg-white border-b border-gray-200">
                <div
                    class="flex items-center justify-between h-16 px-4 lg:px-8"
                >
                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <h1 class="text-lg font-semibold text-gray-900 lg:hidden">
                        {{ pageTitle }}
                    </h1>

                    <!-- Header Right -->
                    <div class="flex items-center gap-4">
                        <slot name="header-right" />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-8">
                <!-- Page Header -->
                <div v-if="pageTitle" class="mb-6 hidden lg:block">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ pageTitle }}
                    </h1>
                    <p
                        v-if="pageDescription"
                        class="mt-1 text-sm text-gray-500"
                    >
                        {{ pageDescription }}
                    </p>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";

// URL de base pour les images storage
const storageUrl = window.__STORAGE_URL__ || "/storage";

const props = defineProps({
    pageTitle: {
        type: String,
        default: "",
    },
    pageDescription: {
        type: String,
        default: "",
    },
    userName: {
        type: String,
        default: "Utilisateur",
    },
    userRole: {
        type: String,
        default: "Role",
    },
});

const sidebarOpen = ref(false);

const appName = computed(() => usePage().props.app?.name || "RestoApp");
const appLogo = computed(() => usePage().props.app?.logo);

const userInitials = computed(() => {
    return props.userName
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
});
</script>
