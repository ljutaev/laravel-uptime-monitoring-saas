<template>
    <div class="py-4 mt-auto">
        <div class="mb-1 text-sm text-gray-700 dark:text-gray-300">
            {{ used }} of {{ total }} monitors used.
        </div>

        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div
                class="bg-blue-600 h-2.5 rounded-full transition-all duration-500"
                :style="{ width: `${usagePercent}%` }"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const limits = computed(() => page.props.auth?.limits?.monitors ?? {})

// 🧾 Значення
const used = computed(() => limits.value.used ?? 0)
const remaining = computed(() => limits.value.remaining ?? 0)
const total = computed(() => used.value + remaining.value)

// console.log(used.value, remaining.value, total.value)


// 📊 Відсоток використання
const usagePercent = computed(() => {
    if (total.value === 0) return 0
    return Math.min((used.value / total.value) * 100, 100).toFixed(1)
})

console.log((used.value / total.value) * 100 )
</script>
