<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    actionUrl: { type: String, required: true },
    formData:  { type: Object, required: true },
});

const formRef = ref(null);

// onMounted(() => { formRef.value?.submit(); });
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-700 dark:text-gray-300">Redirecting to payment...</p>

            <form ref="formRef" method="POST" :action="actionUrl" accept-charset="utf-8">
                <template v-for="(val, key) in formData" :key="key">
                    <!-- масиви (productName[], тощо) -->
                    <template v-if="Array.isArray(val)">
                        <input v-for="(v,i) in val" :key="`${key}-${i}`" type="hidden" :name="key + '[]'" :value="v" />
                    </template>
                    <template v-else>
                        <input type="hidden" :name="key" :value="val" />
                    </template>
                </template>
            </form>
        </div>
    </div>
</template>
