<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links?.length > 3" class="flex flex-wrap items-center gap-2">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded-md border px-3 py-1.5 text-xs"
            :class="[
                link.active ? 'border-violet-500 bg-violet-500 text-white' : 'border-gray-200 text-gray-500 hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-400 dark:hover:text-white',
                !link.url ? 'pointer-events-none opacity-40' : ''
            ]"
            v-html="link.label"
        />
    </div>
</template>
