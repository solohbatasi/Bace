<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 transition hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-25 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:hover:text-white dark:focus:ring-offset-[#090c11]">
        <slot />
    </button>
</template>
