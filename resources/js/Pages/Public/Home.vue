<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    organisation: Object,
    featuredCourses: Array,
    stats: Array,
});

const money = (value) => Number(value || 0) ? `KES ${Number(value).toLocaleString()}` : 'Fee on request';
</script>

<template>
    <PublicLayout title="Home" :organisation="organisation">
        <section class="relative overflow-hidden bg-slate-950 text-white">
            <div class="absolute inset-0 opacity-20">
                <img v-if="organisation.logo_url" :src="organisation.logo_url" :alt="organisation.name" class="h-full w-full object-cover">
                <div v-else class="h-full w-full bg-[linear-gradient(135deg,#111827_0%,#312e81_45%,#0f172a_100%)]" />
            </div>
            <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-violet-200">{{ organisation.short_name || organisation.name }}</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-tight sm:text-6xl">{{ organisation.name || 'Professional College' }}</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">
                        {{ organisation.description || organisation.about || 'A professional learning institution offering structured courses, practical sessions, and learner-focused academic services.' }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link :href="route('courses')" class="rounded-md bg-violet-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-violet-400">Explore Courses</Link>
                        <Link :href="route('contact')" class="rounded-md border border-white/30 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">Contact Admissions</Link>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-8 dark:bg-[#090c11]">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div v-for="item in stats" :key="item.label" class="rounded-md border border-slate-200 bg-slate-50 p-5 dark:border-[#273044] dark:bg-[#11141b]">
                    <p class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ Number(item.value || 0).toLocaleString() }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ item.label }}</p>
                </div>
            </div>
        </section>

        <section class="py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-950 dark:text-white">Featured Courses</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Browse active courses and the subcourses or units learners can take.</p>
                    </div>
                    <Link :href="route('courses')" class="text-sm font-bold text-violet-600 dark:text-violet-300">View all courses</Link>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <article v-for="course in featuredCourses" :key="course.id" class="rounded-md border border-slate-200 bg-white p-5 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase text-violet-600 dark:text-violet-300">{{ course.code || 'Course' }}</p>
                                <h3 class="mt-1 text-lg font-extrabold text-slate-950 dark:text-white">{{ course.name }}</h3>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">{{ course.description || 'Structured learning path with guided academic progression.' }}</p>
                            </div>
                            <span class="shrink-0 rounded-md bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-[#1a1f2f] dark:text-slate-300">{{ money(course.fees) }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span v-for="subcourse in course.subcourses.slice(0, 3)" :key="subcourse.id" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ subcourse.name }}</span>
                            <span v-for="unit in course.units.slice(0, 3)" :key="unit.id" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ unit.code || unit.name }}</span>
                        </div>
                    </article>
                    <p v-if="!featuredCourses?.length" class="rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-[#273044]">No public courses are active yet.</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
