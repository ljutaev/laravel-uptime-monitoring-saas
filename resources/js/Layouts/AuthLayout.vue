<script setup>
    import Sidebar  from "@/Components/User/Sidebar.vue";
    import Breadcrumbs from "@/Components/Breadcrumbs.vue";
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50">
        <Sidebar />
        <main class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden p-8 max-w-[1100px] mx-auto w-full">
            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="flex flex-wrap items-center justify-between gap-3 pb-6"
            >
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"><slot name="header" /></h2>
                <Breadcrumbs/>
            </header>

            <!-- Page Content -->
            <slot></slot>
        </main>
    </div>
</template>
