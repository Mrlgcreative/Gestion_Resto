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

    <slot />
  </AppLayout>
</template>

<script setup>
import { AppLayout, Alert } from '@/Components';
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
