<script setup>
import { useTheme } from '@/Composables/useTheme';

const { theme, setTheme } = useTheme();

const cycleTheme = () => {
    setTheme(theme.value === 'system' ? 'dark' : theme.value === 'dark' ? 'light' : 'system');
};
</script>

<template>
    <button
        type="button"
        class="inline-flex size-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition hover:border-violet-400 hover:text-violet-600 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
        :title="`Theme: ${theme}. Click for next mode.`"
        @click="cycleTheme"
    >
        <svg v-if="theme === 'system'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v10H4V5Zm6 14h4m-6 0h8" />
        </svg>
        <svg v-else-if="theme === 'dark'" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
            <circle cx="12" cy="12" r="4" />
        </svg>
        <svg v-else class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z" />
        </svg>
    </button>
</template>
