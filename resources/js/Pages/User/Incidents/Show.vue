<script setup>
import { Link } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const props = defineProps({
    incident: Object,
    timeline: Array,
});

const getTimelineIcon = (type) => {
    return {
        'started': '🔴',
        'notification': '✓',
        'resolved': '✅',
    }[type] || '●';
};

const getTimelineColor = (color) => {
    return {
        'red': 'text-red-600 bg-red-50',
        'green': 'text-green-600 bg-green-50',
        'gray': 'text-gray-600 bg-gray-50',
    }[color] || 'text-gray-600 bg-gray-50';
};
</script>

<template>
    <AuthLayout>
        <template #header>
            Incident Details
        </template>

        <div class="max-w-full">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                <Link :href="route('dashboard')" class="hover:text-gray-700">Home</Link>
                <span>›</span>
                <Link :href="route('incidents.index')" class="hover:text-gray-700">Incidents</Link>
                <span>›</span>
                <span class="text-gray-900 dark:text-white">Incident</span>
            </div>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Incident
                </h1>
                <Link :href="route('monitors.show', incident.monitor.id)" class="text-blue-600 hover:underline">
                    ← Back to {{ incident.monitor.name }}
                </Link>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Summary</h3>

                        <div class="space-y-4">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                                <span :class="incident.status === 'resolved' ? 'text-green-600' : 'text-red-600'" class="font-semibold capitalize">
                                    {{ incident.status }}
                                </span>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Cause</div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ incident.status_code || 'N/A' }} {{ incident.error_type }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Location</div>
                                <div class="flex items-center gap-2">
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='14'%3E%3Crect fill='%23B22234' width='20' height='14'/%3E%3Cpath d='M0,0h20v1.5H0V0z M0,3h20v1.5H0V3z M0,6h20v1.5H0V6z M0,9h20v1.5H0V9z M0,12h20v1.5H0V12z' fill='%23fff'/%3E%3Crect fill='%233C3B6E' width='8' height='7'/%3E%3C/svg%3E" alt="US" class="w-5">
                                    <span class="text-sm text-gray-700 dark:text-gray-400">United States, Clifton, NJ</span>
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Duration</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ incident.duration }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Started at</div>
                                <div class="text-sm text-gray-700 dark:text-gray-400">{{ incident.started_at }}</div>
                            </div>

                            <div v-if="incident.resolved_at">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ended at</div>
                                <div class="text-sm text-gray-700 dark:text-gray-400">{{ incident.resolved_at }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Monitor</div>
                                <Link :href="route('monitors.show', incident.monitor.id)" class="text-sm text-blue-600 hover:underline">
                                    {{ incident.monitor.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Timeline</h3>

                        <div class="space-y-4">
                            <div v-for="(event, index) in timeline" :key="index" class="flex items-start gap-4">
                                <div :class="getTimelineColor(event.color)" class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm">{{ getTimelineIcon(event.type) }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ event.title }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ event.time }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
