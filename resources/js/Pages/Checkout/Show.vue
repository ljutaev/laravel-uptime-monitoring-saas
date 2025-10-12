<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const props = defineProps({
    plan: Object,
    user: Object,
    paymentMethods: Array, // ['wayforpay', ...]
});

const form = useForm({
    interval: props.plan.interval,       // 'month'|'year'
    payment_method: props.paymentMethods?.[0] ?? 'wayforpay',
});

const proceedToPayment = () => {
    form.post(route('checkout.process', props.plan.id), { preserveScroll: true });
};
</script>

<template>
    <AuthLayout>
        <template #header>
            Checkout
        </template>

        <div class="max-w-5xl mx-auto">
            <!-- Breadcrumb -->
            <div class="mb-4 text-sm">
                <a href="/dashboard" class="text-gray-500 hover:text-gray-700">Home</a>
                <span class="mx-2 text-gray-400">›</span>
                <a href="/plans" class="text-gray-500 hover:text-gray-700">Pricing</a>
                <span class="mx-2 text-gray-400">›</span>
                <span class="text-gray-900 dark:text-white">Checkout</span>
            </div>

            <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Checkout</h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left side - Payment Method -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Payment Method -->
                    <!-- Payment Method -->
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Payment method</h3>

                        <div class="space-y-3">
                            <label
                                v-for="method in paymentMethods"
                                :key="method"
                                class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer"
                                :class="form.payment_method === method ? 'border-blue-500' : 'border-gray-200'"
                                @click="form.payment_method = method"
                            >
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="payment" :value="method" class="w-5 h-5 text-blue-600" v-model="form.payment_method" />
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ method === 'wayforpay' ? 'WayForPay' : method }}</span>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Card</span>
                            </label>
                        </div>
                    </div>

                    <!-- Billing Information -->
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Billing information</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                                <input
                                    type="text"
                                    :value="user.name"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                                <input
                                    type="email"
                                    :value="user.email"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-6 sticky top-4">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Order summary</h3>

                        <!-- Plan Info -->
                        <div class="mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ plan.name }} plan</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Billed {{ plan.interval === 'year' ? 'yearly' : 'monthly' }}.
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="space-y-2 py-4 border-t border-b border-gray-200 dark:border-gray-800">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ plan.price }} {{ plan.currency }}
                                </span>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex justify-between items-center py-4">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ plan.price }} {{ plan.currency }}
                            </span>
                        </div>

                        <!-- Pay Button -->
                        <button
                            @click="proceedToPayment"
                            :disabled="form.processing"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3.5 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Pay {{ plan.price }} {{ plan.currency }}</span>
                        </button>

                        <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
                            By continuing, you agree to the terms of service and authorize to charge your payment method on a recurring basis.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
