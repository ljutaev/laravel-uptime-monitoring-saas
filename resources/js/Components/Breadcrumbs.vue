<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    items: {
        type: Array,
        default: null, // Якщо не передано — створимо автоматично
    },
});

// Отримуємо сторінку від Inertia
const page = usePage();

// Автоматичне визначення назви сторінки на основі маршруту
const autoItems = computed(() => {
    const component = page.component; // Наприклад: "User/Plans" або "Dashboard/Index"
    const parts = component.split("/");

    const breadcrumbsLast = page.props.breadcrumbs || {};

    // Перетворюємо в читабельний вигляд
    const readable = parts
        .map((part) =>
            part
                .replace(/([A-Z])/g, " $1") // розділяє CamelCase
                .trim()
        )
        .join(" / ");

    return [
        { label: "Dashboard", route: "dashboard" },
        { label: readable },
    ];
});

// Використовуємо передані items або авто
const finalItems = computed(() => props.items ?? autoItems.value);
</script>

<template>
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center gap-1.5 text-sm">
            <li
                v-for="(item, index) in finalItems"
                :key="index"
                class="flex items-center gap-1.5"
            >
                <Link
                    v-if="item.route"
                    :href="route(item.route)"
                    class="inline-flex items-center gap-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    class="text-gray-800 dark:text-white/90"
                >
                    {{ item.label }}
                </span>

                <svg
                    v-if="index < finalItems.length - 1"
                    class="stroke-current text-gray-400 dark:text-gray-500"
                    width="17"
                    height="16"
                    viewBox="0 0 17 16"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                        stroke="currentColor"
                        stroke-width="1.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </li>
        </ol>
    </nav>
</template>
