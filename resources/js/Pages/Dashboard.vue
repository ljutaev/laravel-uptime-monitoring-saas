<script setup>
import AuthenticatedLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    stats: { type: Object, required: true },
    recentChecks: { type: Array, default: () => [] },
    recentIncidents: { type: Array, default: () => [] },
})

const badgeClassByStatusCode = (code) => {
    if (!code) return 'bg-gray-400'
    if (code >= 200 && code < 300) return 'bg-green-500'
    if (code >= 300 && code < 400) return 'bg-blue-500'
    if (code >= 400 && code < 500) return 'bg-amber-500'
    return 'bg-red-500'
}

console.log(props.recentChecks)
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>Dashboard</template>

        <!-- KPI cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <!-- Monitors -->
            <article class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90">
                    <!-- icon -->
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none"><path d="M14.0003 24.5898V24.5863M14.0003 12.8684V24.5863M9.06478 16.3657V10.6082M18.9341 5.67497C18.9341 5.67497 12.9204 8.68175 9.06706 10.6084M23.5913 8.27989C23.7686 8.55655 23.8679 8.88278 23.8679 9.2241V18.7779C23.8679 19.4407 23.4934 20.0467 22.9005 20.3431L14.7834 24.4015C14.537 24.5248 14.2686 24.5864 14.0003 24.5863M23.5913 8.27989L14.7834 12.6837C14.2908 12.93 13.7109 12.93 13.2182 12.6837L4.41037 8.27989M23.5913 8.27989C23.4243 8.01927 23.1881 7.80264 22.9005 7.65884L14.7834 3.60044C14.2908 3.35411 13.7109 3.35411 13.2182 3.60044L5.10118 7.65884C4.81359 7.80264 4.57737 8.01927 4.41037 8.27989M4.41037 8.27989C4.23309 8.55655 4.13379 8.88278 4.13379 9.2241V18.7779C4.13379 19.4407 4.5083 20.0467 5.10118 20.3431L13.2182 24.4015C13.4644 24.5246 13.7324 24.5862 14.0003 24.5863" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                        {{ stats.monitors.total }}
                    </h3>
                    <p class="flex items-center gap-3 text-gray-500 dark:text-gray-400">Monitors</p>
                </div>
            </article>

            <!-- Checks (last 24h) -->
            <article class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90">
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none"><path d="M14.0003 24.5898V24.5863M14.0003 12.8684V24.5863M9.06478 16.3657V10.6082M18.9341 5.67497C18.9341 5.67497 12.9204 8.68175 9.06706 10.6084M23.5913 8.27989C23.7686 8.55655 23.8679 8.88278 23.8679 9.2241V18.7779C23.8679 19.4407 23.4934 20.0467 22.9005 20.3431L14.7834 24.4015C14.537 24.5248 14.2686 24.5864 14.0003 24.5863M23.5913 8.27989L14.7834 12.6837C14.2908 12.93 13.7109 12.93 13.2182 12.6837L4.41037 8.27989M23.5913 8.27989C23.4243 8.01927 23.1881 7.80264 22.9005 7.65884L14.7834 3.60044C14.2908 3.35411 13.7109 3.35411 13.2182 3.60044L5.10118 7.65884C4.81359 7.80264 4.57737 8.01927 4.41037 8.27989M4.41037 8.27989C4.23309 8.55655 4.13379 8.88278 4.13379 9.2241V18.7779C4.13379 19.4407 4.5083 20.0467 5.10118 20.3431L13.2182 24.4015C13.4644 24.5246 13.7324 24.5862 14.0003 24.5863" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                        {{ stats.checks_24h }}
                    </h3>
                    <p class="flex items-center gap-3 text-gray-500 dark:text-gray-400">Checks (24h)</p>
                </div>
            </article>

            <!-- Incidents (ongoing) -->
            <article class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90">
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none"><path d="M14.0003 24.5898V24.5863M14.0003 12.8684V24.5863M9.06478 16.3657V10.6082M18.9341 5.67497C18.9341 5.67497 12.9204 8.68175 9.06706 10.6084M23.5913 8.27989C23.7686 8.55655 23.8679 8.88278 23.8679 9.2241V18.7779C23.8679 19.4407 23.4934 20.0467 22.9005 20.3431L14.7834 24.4015C14.537 24.5248 14.2686 24.5864 14.0003 24.5863M23.5913 8.27989L14.7834 12.6837C14.2908 12.93 13.7109 12.93 13.2182 12.6837L4.41037 8.27989M23.5913 8.27989C23.4243 8.01927 23.1881 7.80264 22.9005 7.65884L14.7834 3.60044C14.2908 3.35411 13.7109 3.35411 13.2182 3.60044L5.10118 7.65884C4.81359 7.80264 4.57737 8.01927 4.41037 8.27989M4.41037 8.27989C4.23309 8.55655 4.13379 8.88278 4.13379 9.2241V18.7779C4.13379 19.4407 4.5083 20.0467 5.10118 20.3431L13.2182 24.4015C13.4644 24.5246 13.7324 24.5862 14.0003 24.5863" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                        {{ stats.ongoing_incidents }}
                    </h3>
                    <p class="flex items-center gap-3 text-gray-500 dark:text-gray-400">Incidents</p>
                </div>
            </article>
        </div>

        <div class="grid gap-6 grid-cols-2 items-start">
            <!-- Recent Checks -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Checks</h3>
                    </div>
                </div>

                <div class="max-w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full">
                        <thead>
                        <tr class="border-t border-gray-100 dark:border-gray-800 hidden">
                            <th class="px-6 py-3 text-left">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Response status code</p>
                            </th>
                            <th class="px-6 py-3 text-right">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Response time</p>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="!recentChecks.length" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3.5" colspan="2">
                                <p class="text-gray-500 font-medium text-theme-sm dark:text-gray-400">No checks yet.</p>
                            </td>
                        </tr>

                        <tr v-for="(row, i) in recentChecks" :key="i" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 flex items-center gap-2">
                    <span class="inline-block rounded-full px-2 py-0.25 font-medium text-white" :class="badgeClassByStatusCode(row.status_code)">
                      {{ row.status_code ?? '—' }}
                    </span>
                                    <Link :href="`/monitors/${row.monitor.id}`" class="text-gray-500 hover:underline">{{ row.monitor.name }}</Link>
                                    <span class="text-xs text-gray-400">• {{ row.checked_at }}</span>
                                </p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400 text-right">{{ row.response_time ?? '—' }} ms</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Incidents -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Incidents</h3>
                    </div>
                </div>

                <div class="max-w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full">
                        <thead>
                        <tr class="border-t border-gray-100 dark:border-gray-800 hidden">
                            <th class="px-6 py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">—</p></th>
                            <th class="px-6 py-3 text-right"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">—</p></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="!recentIncidents.length" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 flex gap-1 items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-green-500 text-success mr-2" viewBox="0 0 20 20"><path d="m10,0C4.48,0,0,4.48,0,10s4.48,10,10,10,10-4.48,10-10S15.52,0,10,0Zm-1.77,14.41l-3.8-3.81,1.48-1.48,2.32,2.33,5.85-5.87,1.48,1.48-7.33,7.35Z"/></svg>
                                    No incidents found.
                                </p>
                            </td>
                        </tr>

                        <tr v-for="inc in recentIncidents" :key="inc.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                    <span
                        class="inline-block rounded-full px-2 py-0.25 font-medium text-white mr-2"
                        :class="inc.status === 'ongoing' ? 'bg-red-500' : 'bg-green-500'"
                    >
                      {{ inc.status }}
                    </span>
                                    <Link :href="`/monitors/${inc.monitor.id}`" class="hover:underline">{{ inc.monitor.name }}</Link>
                                    <span class="text-xs text-gray-400">• {{ inc.started_at }} <span v-if="inc.resolved_at">→ {{ inc.resolved_at }}</span></span>
                                </p>
                                <p v-if="inc.error" class="text-xs text-gray-500 mt-1">{{ inc.error }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400 text-right">{{ inc.duration }}</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
