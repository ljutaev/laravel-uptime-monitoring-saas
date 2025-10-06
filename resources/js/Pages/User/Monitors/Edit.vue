<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const props = defineProps({
    monitor: {
        type: Object,
        default: () => ({
            name: '',
            url: '',
            check_interval: 1,
            notifications_enabled: true,
        })
    },
    minInterval: {
        type: Number,
        default: 1
    }
});

const isEdit = !!props.monitor.id;

const form = useForm({
    name: props.monitor.name,
    url: props.monitor.url,
    check_interval: props.monitor.check_interval,
    notifications_enabled: props.monitor.notifications_enabled,
});

const submit = () => {
    if (isEdit) {
        form.put(route('monitors.update', props.monitor.id));
    } else {
        form.post(route('monitors.store'));
    }
};
</script>

<template>
    <AuthLayout>
        <template #header>
            {{ isEdit ? 'Edit Monitor' : 'Create Monitor' }}
        </template>

        <div class="max-w-full">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <!-- Header -->
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ isEdit ? 'Edit Monitor' : 'New Monitor' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ isEdit ? 'Update monitor settings' : 'Add a new website to monitor' }}
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="My Website"
                            class="shadow-sm w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            :class="{ 'border-red-500': form.errors.name }"
                        >
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- URL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            URL <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.url"
                            type="url"
                            placeholder="https://example.com"
                            class="shadow-sm w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            :class="{ 'border-red-500': form.errors.url }"
                        >
                        <p v-if="form.errors.url" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.url }}
                        </p>
                    </div>

                    <!-- Check Interval -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Check Interval (minutes) <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="form.check_interval"
                            type="number"
                            :min="minInterval"
                            max="60"
                            class="shadow-sm w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            :class="{ 'border-red-500': form.errors.check_interval }"
                        >
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Minimum: {{ minInterval }} minutes (based on your plan)
                        </p>
                        <p v-if="form.errors.check_interval" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.check_interval }}
                        </p>
                    </div>

                    <!-- Notifications -->
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                v-model="form.notifications_enabled"
                                type="checkbox"
                                class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900"
                            >
                            <div>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable Notifications
                                </span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    Receive alerts when this monitor goes down
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
                        <a
                            :href="route('monitors.index')"
                            class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-500 hover:bg-blue-600 inline-flex items-center justify-center gap-2 rounded-lg px-6 py-2.5 text-sm font-medium text-white transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            {{ isEdit ? 'Update Monitor' : 'Create Monitor' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthLayout>
</template
