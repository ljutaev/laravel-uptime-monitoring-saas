<script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";
</script>

<template>
    <AuthLayout>
        <div class="max-w-2xl mx-auto text-center py-12">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">Payment Successful!</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Your subscription has been activated.
            </p>

            <a href="/dashboard" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Go to Dashboard
            </a>
        </div>
    </AuthLayout>
</template>
