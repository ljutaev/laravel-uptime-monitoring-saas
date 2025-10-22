<script setup>
import { Link } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const props = defineProps({
    incidents: Object,
});

const getStatusBadge = (status) => {
    return {
        'ongoing': 'bg-red-100 text-red-800',
        'resolved': 'bg-green-100 text-green-800',
    }[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AuthLayout>
        <template #header>
            Incidents
        </template>

        <div class="max-w-full">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Incidents
                </h1>
            </div>

            <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div v-if="incidents.data.length === 0" class="p-12 text-center">
                    <span class="text-green-600 text-4xl">✓</span>
                    <p class="text-gray-500 dark:text-gray-400 mt-4">No incidents found. All systems operational!</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Monitor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Started</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Error</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <tr v-for="incident in incidents.data" :key="incident.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td class="px-6 py-4">
                                <Link :href="route('monitors.show', incident.monitor.id)" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                    {{ incident.monitor.name }}
                                </Link>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ incident.monitor.url }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusBadge(incident.status)" class="px-2 py-0.5 rounded text-xs font-medium capitalize">
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
                </div>

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
    </AuthLayout>
</template>
