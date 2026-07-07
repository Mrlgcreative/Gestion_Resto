<template>
    <div class="space-y-1">
        <!-- Dashboard -->
        <SidebarSection title="Principal">
            <SidebarLink href="/dashboard" label="Dashboard">
                <template #icon>
                    <Squares2X2Icon class="w-5 h-5" />
                </template>
            </SidebarLink>
        </SidebarSection>

        <!-- Ventes -->
        <SidebarSection
            v-if="permissions.canViewOrders || permissions.canViewSessions"
            title="Ventes"
        >
            <SidebarLink
                v-if="permissions.canViewOrders"
                href="/orders"
                label="Commandes"
            >
                <template #icon>
                    <ClipboardDocumentListIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewSessions"
                href="/sessions"
                label="Sessions Caisse"
            >
                <template #icon>
                    <CurrencyDollarIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
        </SidebarSection>

        <!-- Cuisine -->
        <SidebarSection v-if="permissions.canViewKitchen" title="Cuisine">
            <SidebarLink
                v-if="permissions.canViewKitchen"
                href="/kitchen"
                label="Écran Cuisine"
            >
                <template #icon>
                    <FireIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
        </SidebarSection>

        <!-- Gestion -->
        <SidebarSection
            v-if="
                permissions.canViewCategories ||
                permissions.canViewProducts ||
                permissions.canViewStocks ||
                permissions.canViewServers
            "
            title="Gestion"
        >
            <SidebarLink
                v-if="permissions.canViewProducts"
                href="/products"
                label="Produits"
            >
                <template #icon>
                    <CubeIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewCategories"
                href="/categories"
                label="Catégories"
            >
                <template #icon>
                    <TagIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewStocks"
                href="/stocks"
                label="Stocks"
            >
                <template #icon>
                    <ArchiveBoxIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewServers"
                href="/servers"
                label="Serveurs"
            >
                <template #icon>
                    <UserGroupIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
        </SidebarSection>

        <!-- Administration -->
        <SidebarSection
            v-if="
                permissions.canViewUsers ||
                permissions.canViewSettings ||
                permissions.canViewReports
            "
            title="Administration"
        >
            <SidebarLink
                v-if="permissions.canViewUsers"
                href="/users"
                label="Utilisateurs"
            >
                <template #icon>
                    <UsersIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewReports"
                href="/reports"
                label="Rapports"
            >
                <template #icon>
                    <ChartBarIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
            <SidebarLink
                v-if="permissions.canViewSettings"
                href="/settings"
                label="Paramètres"
            >
                <template #icon>
                    <Cog6ToothIcon class="w-5 h-5" />
                </template>
            </SidebarLink>
        </SidebarSection>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { SidebarSection, SidebarLink } from "@/Components";
import {
    Squares2X2Icon,
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    FireIcon,
    CubeIcon,
    TagIcon,
    ArchiveBoxIcon,
    UserGroupIcon,
    UsersIcon,
    ChartBarIcon,
    Cog6ToothIcon,
} from "@heroicons/vue/24/outline";

const page = usePage();

const permissions = computed(() => page.props.permissions || {});
</script>
