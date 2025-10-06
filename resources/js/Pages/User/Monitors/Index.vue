<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const props = defineProps({
    monitors: Array,
});

const deleteMonitor = (id) => {
    if (confirm('Ви впевнені що хочете видалити цей монітор?')) {
        router.delete(`/dashboard/monitors/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthLayout>
        <template #header>
            Monitors
        </template>

        <div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <!-- Header -->
                <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Monitors
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Track your domains.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <Link
                            :href="route('monitors.create')"
                            class="bg-blue-500 hover:bg-blue-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Add New
                        </Link>
                    </div>
                </div>

                <!-- Table -->
                <div class="custom-scrollbar overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                Name
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                Uptime
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                Interval
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                Last Check
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                Created At
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-x divide-y divide-gray-200 dark:divide-gray-800">
                        <tr
                            v-for="monitor in monitors"
                            :key="monitor.id"
                            class="transition hover:bg-gray-50 dark:hover:bg-gray-900"
                        >
                            <!-- Name & URL -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="flex items-center gap-2 text-sm font-medium text-blue-500">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4 fill-current"
                                        :class="{
                                                'text-green-500': monitor.status === 'up',
                                                'text-red-500': monitor.status === 'down',
                                                'text-gray-400': monitor.status === 'paused'
                                            }"
                                        viewBox="0 0 16 16"
                                    >
                                        <g style="opacity:0.25">
                                            <circle cx="8" cy="8" r="8"></circle>
                                        </g>
                                        <circle cx="8" cy="8" r="4"></circle>
                                    </svg>
                                    {{ monitor.name }}
                                </p>
                                <span class="text-xs text-gray-500 dark:text-gray-400 pl-6">
                                        {{ monitor.url }}
                                    </span>
                            </td>

                            <!-- Uptime -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                    {{ monitor.uptime_30d }}%
                                </p>
                            </td>

                            <!-- Interval -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ monitor.check_interval }} хв
                                </p>
                            </td>

                            <!-- Last Check -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ monitor.last_checked_at || 'Ще не перевірявся' }}
                                </p>
                            </td>

                            <!-- Created At -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                    {{ monitor.created_at }}
                                </p>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex gap-2">
                                    <Link
                                        :href="route('monitors.edit', monitor.id)"
                                        class="text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="deleteMonitor(monitor.id)"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="monitors.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-gray-500 dark:text-gray-400">
                                    У вас ще немає моніторів. Створіть перший!
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
