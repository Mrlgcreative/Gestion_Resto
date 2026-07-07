<template>
  <AppLayout
    :page-title="pageTitle"
    :page-description="pageDescription"
    :user-name="$page.props.auth?.user?.name || 'Utilisateur'"
    :user-role="$page.props.auth?.user?.role?.name || 'Role'"
  >
    <template #sidebar>
      <SidebarContent />
    </template>

    <template #header-right>
      <slot name="header-actions" />
    </template>

    <slot />
  </AppLayout>
</template>

<script setup>
import { AppLayout } from '@/Components';
import SidebarContent from './SidebarContent.vue';

defineProps({
  pageTitle: {
    type: String,
    required: true,
  },
  pageDescription: {
    type: String,
    default: '',
  },
});
</script>
