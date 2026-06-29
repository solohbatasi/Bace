<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    organisation: Object,
});
</script>

<template>
    <PublicLayout title="Contact" :organisation="organisation">
        <section class="bg-white py-16 dark:bg-[#090c11]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-violet-600 dark:text-violet-300">Contact</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white">Talk to {{ organisation.name || 'us' }}</h1>
                    <p class="mt-5 text-base leading-8 text-slate-600 dark:text-slate-300">
                        Reach admissions, administration, or learner support using the official contacts below.
                    </p>
                </div>
            </div>
        </section>

        <section class="pb-16">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="rounded-md border border-slate-200 bg-white p-6 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                    <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">Contact Information</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">Primary phone</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ organisation.primary_contact || 'Not published yet' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">Secondary phone</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ organisation.secondary_contact || 'Not published yet' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">Official email</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ organisation.official_email || organisation.contact_email || 'Not published yet' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">Location</p>
                            <p class="mt-1 text-sm leading-6 text-slate-800 dark:text-slate-100">{{ organisation.location || 'Location will be published soon.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-md border border-slate-200 bg-white p-6 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                    <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">Operating Hours</h2>
                    <div class="mt-5 space-y-2">
                        <div v-for="item in organisation.operation_hours" :key="item.day" class="flex items-center justify-between rounded-md border border-slate-100 px-3 py-2 dark:border-[#273044]">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ item.day }}</p>
                            <p class="text-sm font-semibold" :class="item.is_open ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-400'">
                                {{ item.is_open ? `${item.opens_at} - ${item.closes_at}` : 'Closed' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
