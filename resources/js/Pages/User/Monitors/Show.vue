<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

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
    monitor: Object,
    overview: Object,
    chartData: Object,
    recentChecks: Array,
    sslInfo: Object,
    checks: Object,
    incidents: Object,
    currentTab: String,
    currentPeriod: String,
});

const statusColor = computed(() => {
    return {
        'up': 'text-green-600 bg-green-50',
        'down': 'text-red-600 bg-red-50',
        'paused': 'text-gray-600 bg-gray-50',
    }[props.monitor.status] || 'text-gray-600 bg-gray-50';
});

const statusIcon = computed(() => {
    return {
        'up': 'Active',
        'down': 'Down',
        'paused': 'Paused',
    }[props.monitor.status] || 'Unknown';
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.05)' },
            ticks: {
                callback: (value) => value + ' ms'
            }
        },
        x: {
            grid: { display: false }
        }
    }
};

const responseTimeChartData = computed(() => ({
    labels: props.chartData.labels,
    datasets: [{
        data: props.chartData.data,
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 3,
        pointHoverRadius: 5,
    }]
}));

const changePeriod = (period) => {
    router.get(route('monitors.show', props.monitor.id), {
        period: period,
        tab: props.currentTab
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changeTab = (tab) => {
    router.get(route('monitors.show', props.monitor.id), {
        period: props.currentPeriod,
        tab: tab
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getIncidentStatusBadge = (status) => {
    return {
        'ongoing': 'bg-red-100 text-red-800',
        'resolved': 'bg-green-100 text-green-800',
    }[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AuthLayout>
        <template #header>
            Monitor Details
        </template>

        <div class="max-w-full">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ monitor.name }}
                    </h1>
                    <a :href="monitor.url" target="_blank" class="text-sm text-blue-600 hover:underline">
                        {{ monitor.url }} ↗
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Period Selector -->
                    <select
                        :value="currentPeriod"
                        @change="changePeriod($event.target.value)"
                        class="shadow-sm rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="today">Today</option>
                        <option value="7d">7 days</option>
                        <option value="30d">30 days</option>
                    </select>

                    <!-- Status Badges -->
                    <span :class="statusColor" class="px-3 py-1.5 rounded-lg text-sm font-medium">
                        {{ statusIcon }}
                    </span>
                    <span v-if="sslInfo" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-green-50 text-green-600">
                        🔒 Valid
                    </span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-800 mb-6">
                <nav class="flex gap-6">
                    <button
                        @click="changeTab('overview')"
                        :class="currentTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition"
                    >
                        📊 Overview
                    </button>
                    <button
                        @click="changeTab('checks')"
                        :class="currentTab === 'checks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition"
                    >
                        🔍 Checks
                    </button>
                    <button
                        @click="changeTab('incidents')"
                        :class="currentTab === 'incidents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition"
                    >
                        ⚠️ Incidents
                    </button>
                </nav>
            </div>

            <!-- Overview Tab -->
            <div v-if="currentTab === 'overview'">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Uptime</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.uptime }}%</div>
                        <div class="text-xs text-gray-400 mt-1">{{ overview.uptime_duration }}</div>
                    </div>

                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Response time</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.avg_response_time }} ms</div>
                        <div class="text-xs text-gray-400 mt-1">{{ overview.total_checks }} checks</div>
                    </div>

                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Incidents</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.incidents_count }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ overview.incidents_count === 0 ? '0 s' : 'downtime' }}</div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6 mb-6">
                    <div class="h-64">
                        <Line :data="responseTimeChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Two Columns -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Checks -->
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Checks</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm font-medium text-gray-500 dark:text-gray-400 pb-2 border-b border-gray-200 dark:border-gray-800">
                                <span>Response status code</span>
                                <span>Response time</span>
                            </div>
                            <div
                                v-for="(check, index) in recentChecks"
                                :key="index"
                                class="flex items-center justify-between py-2"
                            >
                                <div class="flex items-center gap-2">
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='14'%3E%3Crect fill='%23B22234' width='20' height='14'/%3E%3Cpath d='M0,0h20v1.5H0V0z M0,3h20v1.5H0V3z M0,6h20v1.5H0V6z M0,9h20v1.5H0V9z M0,12h20v1.5H0V12z' fill='%23fff'/%3E%3Crect fill='%233C3B6E' width='8' height='7'/%3E%3C/svg%3E" alt="US" class="w-5">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ check.status_code }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-700 dark:text-gray-400">
                                    {{ check.response_time }} ms
                                </span>
                            </div>
                            <Link
                                :href="route('monitors.show', { id: monitor.id, tab: 'checks' })"
                                class="block text-center text-sm text-blue-600 hover:text-blue-700 pt-2"
                            >
                                View all →
                            </Link>
                        </div>
                    </div>

                    <!-- Incidents -->
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Incidents</h3>
                        <div v-if="overview.incidents_count === 0" class="text-center py-8">
                            <span class="text-green-600 text-2xl">✓</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No incidents found.</p>
                        </div>
                        <div v-else>
                            <Link
                                :href="route('monitors.show', { id: monitor.id, tab: 'incidents' })"
                                class="block text-center text-sm text-blue-600 hover:text-blue-700"
                            >
                                View {{ overview.incidents_count }} incidents →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checks Tab -->
            <div v-if="currentTab === 'checks' && checks">
                <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Checks</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Response status code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Response time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Checked at</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="check in checks.data" :key="check.checked_at" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='14'%3E%3Crect fill='%23B22234' width='20' height='14'/%3E%3Cpath d='M0,0h20v1.5H0V0z M0,3h20v1.5H0V3z M0,6h20v1.5H0V6z M0,9h20v1.5H0V9z M0,12h20v1.5H0V12z' fill='%23fff'/%3E%3Crect fill='%233C3B6E' width='8' height='7'/%3E%3C/svg%3E" alt="US" class="w-5">
                                        <span class="text-sm text-gray-700 dark:text-gray-400">United States, Clifton, NJ</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="check.is_up ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-0.5 rounded text-xs font-medium">
                                            {{ check.status_code || 'N/A' }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                                    {{ check.response_time || 'N/A' }} ms
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                                    {{ check.checked_at }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ checks.from }}-{{ checks.to }} of {{ checks.total }}
                        </span>
                        <div class="flex gap-2">
                            <template v-for="link in checks.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        link.active
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50',
                                        'px-3 py-1.5 rounded text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3 py-1.5 rounded text-sm border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incidents Tab -->
            <div v-if="currentTab === 'incidents' && incidents">
                <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Incidents</h3>
                    </div>

                    <div v-if="incidents.data.length === 0" class="p-12 text-center">
                        <span class="text-green-600 text-4xl">✓</span>
                        <p class="text-gray-500 dark:text-gray-400 mt-4">No incidents found for this period.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Started</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Error</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="incident in incidents.data" :key="incident.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getIncidentStatusBadge(incident.status)" class="px-2 py-0.5 rounded text-xs font-medium capitalize">
                                        {{ incident.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                                    {{ incident.started_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                                    {{ incident.duration }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
                                    <div class="max-w-xs truncate">{{ incident.error_message || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <Link :href="route('incidents.show', incident.id)" class="text-blue-600 hover:text-blue-700">
                                        Details →
                                    </Link>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ incidents.from }}-{{ incidents.to }} of {{ incidents.total }}
                            </span>
                            <div class="flex gap-2">
                                <template v-for="link in incidents.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            link.active
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50',
                                            'px-3 py-1.5 rounded text-sm border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                        ]"
                                    />
                                    <span
                                        v-else
                                        v-html="link.label"
                                        class="px-3 py-1.5 rounded text-sm border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthLayout>
</template>
