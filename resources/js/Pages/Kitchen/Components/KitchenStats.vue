<template>
    <div class="grid grid-cols-4 gap-4 mb-6">
        <StatCard
            title="En attente"
            :value="waiting"
            icon="⏳"
            color="yellow"
        />
        <StatCard
            title="En préparation"
            :value="preparing"
            icon="🔥"
            color="orange"
        />
        <StatCard title="Prêts" :value="ready" icon="✅" color="green" />
        <StatCard
            title="Temps moyen"
            :value="avgTime"
            icon="⏱️"
            color="blue"
            suffix="min"
        />
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
