<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    title: String,
    organisation: Object,
});

const page = usePage();
const organisation = computed(() => props.organisation || page.props.organisation || {});
const organisationName = computed(() => organisation.value.name || organisation.value.short_name || 'Organisation');
const initials = computed(() => (organisation.value.short_name || organisationName.value || 'ORG').substring(0, 3).toUpperCase());
const navItems = [
    { label: 'Home', route: 'home' },
    { label: 'About', route: 'about' },
    { label: 'Courses', route: 'courses' },
    { label: 'Contact', route: 'contact' },
];
</script>

<template>
    <Head :title="title ? `${title} - ${organisationName}` : organisationName" />

    <div class="min-h-screen bg-slate-50 text-slate-950 dark:bg-[#090c11] dark:text-white">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-[#232837] dark:bg-[#11141b]/95">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-violet-600 text-xs font-bold text-white">
                        <img v-if="organisation.logo_url" :src="organisation.logo_url" :alt="`${organisationName} logo`" class="size-full object-cover">
                        <span v-else>{{ initials }}</span>
                    </span>
                    <span class="truncate text-sm font-extrabold text-slate-950 dark:text-white">{{ organisationName }}</span>
                </Link>

                <nav class="hidden items-center gap-1 md:flex">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="rounded-md px-3 py-2 text-sm font-bold transition"
                        :class="route().current(item.route) ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-[#1a1f2f] dark:hover:text-white'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-violet-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-violet-500"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-slate-200 dark:hover:border-violet-400 dark:hover:text-white"
                    >
                        Log in
                    </Link>
                </div>
            </div>
            <div class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2 md:hidden dark:border-[#232837]">
                <Link
                    v-for="item in navItems"
                    :key="`${item.route}-mobile`"
                    :href="route(item.route)"
                    class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-bold"
                    :class="route().current(item.route) ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-slate-500 dark:text-slate-300'"
                >
                    {{ item.label }}
                </Link>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm text-slate-500 sm:px-6 md:grid-cols-[1.2fr_1fr_1fr] lg:px-8">
                <div>
                    <p class="font-extrabold text-slate-950 dark:text-white">{{ organisationName }}</p>
                    <p class="mt-2 max-w-md leading-6">{{ organisation.about || organisation.description || 'Professional learning management and academic services.' }}</p>
                </div>
                <div>
                    <p class="font-bold text-slate-800 dark:text-slate-100">Contact</p>
                    <p class="mt-2">{{ organisation.primary_contact || 'Contact pending' }}</p>
                    <p>{{ organisation.official_email || organisation.contact_email || 'Email pending' }}</p>
                </div>
                <div>
                    <p class="font-bold text-slate-800 dark:text-slate-100">Location</p>
                    <p class="mt-2">{{ organisation.location || 'Location pending' }}</p>
                </div>
            </div>
            <div class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-slate-100 px-4 py-4 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:border-[#232837]">
                <span>{{ organisationName }} v1.0.0</span>
                <span>Maintained by bAtasi</span>
            </div>
        </footer>
    </div>
</template>
