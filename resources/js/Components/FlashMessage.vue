<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, watch, computed, watchEffect } from 'vue';
import { CheckCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/solid';

const page = usePage();
const flash = computed(() => page.props.flash);

const visible = ref(false);
const message = ref('');
const type = ref('success');

console.log('Flash initial:', flash.value);

watchEffect(() => {
    const flash = page.props.flash;

    if (flash?.success || flash?.error) {
        message.value = flash.success || flash.error;
        type.value = flash.success ? 'success' : 'error';
        visible.value = true;

        // авто-зникнення через 4 секунди
        setTimeout(() => (visible.value = false), 4000);
    }
});

const close = () => (visible.value = false);
</script>

<template>
    <transition name="slide-fade">
        <div
            v-if="visible"
            class="fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3 rounded-lg shadow-lg backdrop-blur-sm"
            :class="type === 'success'
        ? 'bg-green-500/90 text-white'
        : 'bg-red-500/90 text-white'"
        >
            <component
                :is="type === 'success' ? CheckCircleIcon : XCircleIcon"
                class="h-6 w-6 flex-shrink-0"
            />

            <span class="text-sm font-medium">{{ message }}</span>

            <button @click="close" class="ml-3 text-white/70 hover:text-white transition">
                <XMarkIcon class="h-5 w-5" />
            </button>
        </div>
    </transition>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.4s ease;
}
.slide-fade-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
