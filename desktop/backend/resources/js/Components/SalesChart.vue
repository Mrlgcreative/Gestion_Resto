<template>
  <div class="w-full h-full">
    <Line v-if="hasData" :key="chartKey" :data="chartData" :options="chartOptions" />
    <div v-else class="h-full flex items-center justify-center text-gray-500">
      Aucune donnée disponible pour cette période
    </div>
  </div>
</template>

<script setup>
import { computed, watch, ref } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  showOrders: {
    type: Boolean,
    default: true,
  },
});

// Force re-render when data changes
const chartKey = ref(0);
watch(() => props.data, () => {
  chartKey.value++;
}, { deep: true });

const hasData = computed(() => props.data && props.data.length > 0);

const chartData = computed(() => {
  const labels = props.data.map(item => item.date);
  const revenueData = props.data.map(item => parseFloat(item.revenue) || 0);
  const ordersData = props.data.map(item => parseInt(item.orders_count) || 0);
  
  const datasets = [
    {
      label: 'Chiffre d\'affaires ($)',
      data: revenueData,
      backgroundColor: 'rgba(59, 130, 246, 0.15)',
      borderColor: 'rgb(59, 130, 246)',
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      fill: true,
      tension: 0.4,
      yAxisID: 'y',
    },
  ];

  if (props.showOrders) {
    datasets.push({
      label: 'Nombre de commandes',
      data: ordersData,
      backgroundColor: 'rgba(16, 185, 129, 0.15)',
      borderColor: 'rgb(16, 185, 129)',
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      fill: true,
      tension: 0.4,
      yAxisID: 'y1',
    });
  }

  return { labels, datasets };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        padding: 20,
      },
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleColor: '#fff',
      bodyColor: '#fff',
      padding: 12,
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      displayColors: true,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.datasetIndex === 0) {
            label += new Intl.NumberFormat('fr-FR', {
              style: 'currency',
              currency: 'USD',
            }).format(context.parsed.y);
          } else {
            label += context.parsed.y + ' commandes';
          }
          return label;
        },
      },
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        color: '#6b7280',
      },
    },
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      title: {
        display: true,
        text: 'Chiffre d\'affaires ($)',
        color: 'rgb(59, 130, 246)',
      },
      ticks: {
        color: 'rgb(59, 130, 246)',
        callback: function(value) {
          return '$' + value.toLocaleString('fr-FR');
        },
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)',
      },
    },
    y1: {
      type: 'linear',
      display: props.showOrders,
      position: 'right',
      title: {
        display: true,
        text: 'Commandes',
        color: 'rgb(16, 185, 129)',
      },
      ticks: {
        color: 'rgb(16, 185, 129)',
        stepSize: 1,
      },
      grid: {
        drawOnChartArea: false,
      },
    },
  },
}));
</script>
