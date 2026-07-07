<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Left: Logo + Mobile menu -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <Bars3Icon class="w-6 h-6" />
                        </button>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ appName.charAt(0) }}
                            </div>
                            <span class="text-lg font-bold text-gray-900 hidden sm:block">{{ appName }}</span>
                        </div>
                    </div>

                    <!-- Center: Service Tabs -->
                    <div class="hidden md:flex items-center gap-1">
                        <button
                            v-for="service in services"
                            :key="service.id"
                            @click="activeService = service.slug"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                            :class="activeService === service.slug
                                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="activeService === service.slug ? 'bg-blue-500' : 'bg-gray-300'"></span>
                            {{ service.name }}
                        </button>
                    </div>

                    <!-- Mobile Service Dropdown -->
                    <div class="md:hidden relative" v-if="services.length > 0">
                        <button
                            @click="serviceDropdownOpen = !serviceDropdownOpen"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                        >
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            {{ activeServiceName || 'Service' }}
                            <ChevronDownIcon class="w-4 h-4" />
                        </button>
                        <div
                            v-if="serviceDropdownOpen"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 py-1 z-50"
                        >
                            <button
                                v-for="service in services"
                                :key="service.id"
                                @click="activeService = service.slug; serviceDropdownOpen = false"
                                class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <span class="w-2 h-2 rounded-full" :class="activeService === service.slug ? 'bg-blue-500' : 'bg-gray-300'"></span>
                                {{ service.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Right: User Info + Service + Logout -->
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex items-center gap-2 text-sm">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-xs">
                                {{ userInitials }}
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 leading-tight">{{ userName }}</p>
                                <p class="text-xs" :class="userService ? 'text-emerald-600' : 'text-gray-500'">
                                    {{ userService ? `${userRole} - ${userService.name}` : userRole }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="logout"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 transition-all"
                            title="Déconnexion"
                        >
                            <ArrowRightStartOnRectangleIcon class="w-5 h-5" />
                            <span class="hidden sm:inline">Déconnexion</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transition-transform duration-300 lg:translate-x-0 pt-16"
        >
            <nav class="h-full overflow-y-auto py-4 px-3">
                <slot name="sidebar" />
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <main class="p-4 sm:p-6 lg:p-8">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success" class="mb-4">
                    <Alert variant="success" dismissible>
                        {{ $page.props.flash.success }}
                    </Alert>
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4">
                    <Alert variant="danger" dismissible>
                        {{ $page.props.flash.error }}
                    </Alert>
                </div>
                <div v-if="$page.props.flash?.warning" class="mb-4">
                    <Alert variant="warning" dismissible>
                        {{ $page.props.flash.warning }}
                    </Alert>
                </div>

                <!-- Page Header -->
                <div v-if="pageTitle" class="mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ pageTitle }}</h1>
                            <p v-if="pageDescription" class="mt-1 text-sm text-gray-500">{{ pageDescription }}</p>
                        </div>
                        <slot name="header-right" />
                    </div>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Alert } from "@/Components";
import {
    Bars3Icon,
    ChevronDownIcon,
    ArrowRightStartOnRectangleIcon,
} from "@heroicons/vue/24/outline";

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
const serviceDropdownOpen = ref(false);

const userService = computed(() => page.props.auth?.user?.service);
const activeService = ref(userService.value?.slug || "restaurant");

const page = usePage();

const appName = computed(() => page.props.app?.name || "RestoApp");
const appLogo = computed(() => page.props.app?.logo);

const services = computed(() => page.props.services || []);

const activeServiceName = computed(() => {
    const s = services.value.find(service => service.slug === activeService.value);
    return s?.name || "Service";
});

const userInitials = computed(() => {
    return props.userName
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
});

const logout = () => {
    router.post("/logout");
};
</script>
