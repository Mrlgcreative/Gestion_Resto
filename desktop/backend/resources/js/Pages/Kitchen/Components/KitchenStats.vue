<template>
    <div class="grid grid-cols-4 gap-4">
        <StatCard title="En attente" :value="waiting" color="yellow" />
        <StatCard title="En preparation" :value="preparing" color="orange" />
        <StatCard title="Prets" :value="ready" color="green" />
        <StatCard title="Temps moyen" :value="avgTime" color="blue" suffix="min" />
    </div>
</template>

<script setup>
import StatCard from "./StatCard.vue";

defineProps({
    waiting: { type: Number, default: 0 },
    preparing: { type: Number, default: 0 },
    ready: { type: Number, default: 0 },
    avgTime: { type: [Number, String], default: 0 },
});
</script>
