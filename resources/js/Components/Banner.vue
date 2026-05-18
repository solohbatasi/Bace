<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const style = ref('success');
const message = ref('');
let timeoutId;

watchEffect(() => {
    style.value = page.props.jetstream.flash?.bannerStyle || 'success';
    message.value = page.props.jetstream.flash?.banner || '';

    if (message.value) {
        show.value = true;
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => (show.value = false), 4200);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="show && message"
            class="fixed right-5 top-5 z-50 w-[min(420px,calc(100vw-2.5rem))] rounded-md border bg-white p-4 shadow-2xl shadow-black/20 dark:bg-[#11141b]"
            :class="style === 'danger' ? 'border-red-500/40 text-red-100' : 'border-violet-500/40 text-gray-100'"
        >
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-md"
                    :class="style === 'danger' ? 'bg-red-500/15 text-red-300' : 'bg-violet-500 text-white'"
                >
                    <svg v-if="style === 'danger'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.3 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                    </svg>
                    <svg v-else class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ style === 'danger' ? 'Action failed' : 'Action completed' }}</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ message }}</p>
                </div>

                <button class="text-gray-400 transition hover:text-gray-200" type="button" @click="show = false">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>
