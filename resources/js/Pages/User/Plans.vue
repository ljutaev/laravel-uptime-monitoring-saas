<script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { ref } from "vue";

const props = defineProps({
    plans: Array,
});

// рік чи місяць
const billingCycle = ref("month"); // "month" | "year"
</script>

<template>
    <AuthLayout>
        <template #header>
            Plans
        </template>

        <h2 class="mb-7 text-center text-title-sm font-bold text-gray-800 dark:text-white/90 text-2xl">
            Flexible Plans Tailored to Fit<br> Your Unique Needs!
        </h2>

        <!-- Перемикач -->
        <div class="mb-10 text-center">
            <div class="relative z-1 mx-auto inline-flex rounded-full bg-gray-200 p-1 dark:bg-gray-800">
                <span :class="billingCycle === 'month' ? 'translate-x-0' : 'translate-x-full'" class="z-10 absolute top-1/2 -z-1 flex h-11 w-[120px] -translate-y-1/2 rounded-full bg-white shadow-theme-xs duration-200 ease-linear dark:bg-white/10 translate-x-0"></span>
                <button :class="billingCycle === 'month' ? 'text-gray-800 dark:text-white/90' : 'text-gray-500 hover:text-gray-700 dark:hover:text-white/70 dark:text-gray-400'" class="z-10 flex h-11 w-[120px] items-center justify-center text-base font-medium text-gray-800 dark:text-white/90" @click="billingCycle = 'month'">
                    Monthly
                </button>
                <button :class="billingCycle === 'year' ? 'text-gray-800 dark:text-white/90' : 'text-gray-500 hover:text-gray-700 dark:hover:text-white/80 dark:text-gray-400'" class="z-10 flex h-11 w-[120px] items-center justify-center text-base font-medium hover:text-gray-700 text-gray-500 dark:hover:text-white/80 dark:text-gray-400" @click="billingCycle = 'year'">
                    Annually
                </button>
            </div>
        </div>

        <div class="gird-cols-1 grid gap-5 sm:grid-cols-2 xl:grid-cols-3 xl:gap-6">
            <div
                v-for="plan in plans"
                :key="plan.name"
                class="rounded-2xl border p-6"
                :class="plan.active ? ' p-6 dark:border-gray-800 dark:bg-white/[0.03] border-gray-800 bg-gray-800 text-white' :' border-gray-200 bg-white  p-6 dark:border-white/10 dark:bg-white/10'"
            >
                <span class="mb-3 block text-theme-xl font-semibold text-gray-800 dark:text-white/90"
                      :class="plan.active ? 'text-white' : 'text-gray-800 dark:text-white/90'"
                >
                    {{ plan.name }}
                  </span>

                <div class="mb-1 flex items-center justify-between">
                    <div class="flex items-end">
                        <h2 class="text-title-md font-bold text-gray-800 dark:text-white/90" x-text="monthly === true ? '$15.00' : '190.00'"
                            :class="plan.active ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
                        >
                            <span v-if="plan.monthly_price === 0">0 USD</span>
                            <span v-else>
                                {{ billingCycle === 'month' ? plan.monthly_price : plan.yearly_price }}
                                {{ plan.currency }}
                              </span>

                            <span class="mb-1 inline-block text-sm text-gray-500 dark:text-gray-400" v-if="plan.monthly_price != '0'">
                                /{{ billingCycle }}
                            </span>
                        </h2>


                    </div>

                    <span class="text-theme-xl font-semibold text-gray-400 line-through" v-if="billingCycle === 'year' && plan.monthly_price != 0">
                        {{billingCycle === 'month' ?  plan.monthly_price  : plan.monthly_price * 12 }} {{ plan.currency }}
                    </span>
                </div>

                <p class="text-sm"
                   :class="plan.active ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
                >
                    {{ plan.description }}
                </p>

                <div class="my-6 h-px w-full bg-gray-200 dark:bg-gray-800"></div>

                <div class="mb-8 space-y-3">
                    <p
                        v-for="(feature, index) in plan.features"
                        :key="index"
                        class="flex items-center gap-3 text-sm"
                        :class="plan.active ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
                    >
                        <template v-if="feature.label.toLowerCase() === 'domains'">
                            <span>🌐</span> {{ feature.value }} Monitors
                        </template>

                        <template v-else-if="feature.label.toLowerCase() === 'check interval'">
                            <span>⏱️</span> {{ feature.value }} {{ feature.value == '1' ? 'minute' : "minutes"}} check Interval
                        </template>

                        <template v-else>
                            <svg
                                v-if="feature.available"
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M13.4017 4.35986L6.12166 11.6399L2.59833 8.11657"
                                    stroke="#12B76A"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                            <span v-else>❌</span>
                            {{ feature.label }}
                        </template>
                    </p>
                </div>

                <a
                    :href="route('checkout.show', { plan: 1, interval: billingCycle })"
                    class="flex w-full items-center justify-center rounded-lg p-3.5 text-sm font-medium text-white shadow-theme-xs transition-colors  dark:bg-white/10"
                    :class="plan.active ? 'bg-blue-800 hover:bg-white hover:text-blue-500 dark:bg-white/10' : 'bg-gray-800 hover:bg-blue-500'"
                >
                    Choose Starter
                </a>
            </div>
        </div>

    </AuthLayout>
</template>
