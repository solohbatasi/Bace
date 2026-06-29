<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    organisation: Object,
    stats: Array,
});
</script>

<template>
    <PublicLayout title="About" :organisation="organisation">
        <section class="bg-white py-16 dark:bg-[#090c11]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-violet-600 dark:text-violet-300">About us</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ organisation.name || 'Our Institution' }}</h1>
                    <p class="mt-5 text-base leading-8 text-slate-600 dark:text-slate-300">
                        {{ organisation.about || organisation.description || 'We provide organised academic programmes, learner support, and practical pathways for professional growth.' }}
                    </p>
                </div>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 md:grid-cols-2 lg:px-8">
                <article class="rounded-md border border-slate-200 bg-white p-6 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                    <p class="text-sm font-bold uppercase text-violet-600 dark:text-violet-300">Mission</p>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ organisation.mission || 'To deliver reliable, learner-centered education with clear academic progression and practical skills.' }}</p>
                </article>
                <article class="rounded-md border border-slate-200 bg-white p-6 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                    <p class="text-sm font-bold uppercase text-blue-600 dark:text-blue-300">Vision</p>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ organisation.vision || 'To become a trusted institution for professional training, academic excellence, and community impact.' }}</p>
                </article>
            </div>
        </section>

        <section class="bg-slate-100 py-12 dark:bg-[#0c0f16]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in stats" :key="item.label" class="rounded-md border border-slate-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ Number(item.value || 0).toLocaleString() }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ item.label }}</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
