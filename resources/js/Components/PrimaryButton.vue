<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-md border border-transparent bg-violet-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-[#090c11]">
        <slot />
    </button>
</template>
