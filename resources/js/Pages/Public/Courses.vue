<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    organisation: Object,
    courses: Array,
});

const money = (value) => Number(value || 0) ? `KES ${Number(value).toLocaleString()}` : 'Fee on request';
</script>

<template>
    <PublicLayout title="Courses" :organisation="organisation">
        <section class="bg-white py-16 dark:bg-[#090c11]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-violet-600 dark:text-violet-300">Programmes</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white">Courses, Subcourses, and Units</h1>
                    <p class="mt-5 text-base leading-8 text-slate-600 dark:text-slate-300">
                        Explore active courses offered by {{ organisation.name || 'the institution' }}. Each course may include subcourses, units, or practical/theoretical pathways depending on its structure.
                    </p>
                </div>
            </div>
        </section>

        <section class="pb-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-2">
                    <article v-for="course in courses" :key="course.id" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm dark:border-[#273044] dark:bg-[#11141b]">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase text-violet-600 dark:text-violet-300">{{ course.code || course.department || 'Course' }}</p>
                                <h2 class="mt-1 text-xl font-extrabold text-slate-950 dark:text-white">{{ course.name }}</h2>
                                <p class="mt-2 text-sm text-slate-500">{{ course.department || 'Department pending' }}</p>
                            </div>
                            <div class="shrink-0 rounded-md bg-slate-100 px-3 py-2 text-right dark:bg-[#1a1f2f]">
                                <p class="text-sm font-extrabold text-slate-950 dark:text-white">{{ money(course.fees) }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ course.duration_semesters || '-' }} semesters</p>
                            </div>
                        </div>

                        <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ course.description || 'Course description will be published soon.' }}</p>

                        <div v-if="course.subcourses?.length" class="mt-5">
                            <p class="text-xs font-bold uppercase text-slate-400">Subcourses</p>
                            <div class="mt-2 space-y-2">
                                <div v-for="subcourse in course.subcourses" :key="subcourse.id" class="rounded-md border border-blue-100 bg-blue-50 p-3 dark:border-blue-500/15 dark:bg-blue-500/10">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-bold text-blue-900 dark:text-blue-200">{{ subcourse.name }}</p>
                                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300">{{ subcourse.code }}</span>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-blue-700/80 dark:text-blue-200/80">{{ subcourse.description || `${subcourse.duration_semesters || course.duration_semesters || '-'} semester pathway` }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="course.units?.length" class="mt-5">
                            <p class="text-xs font-bold uppercase text-slate-400">Units</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span v-for="unit in course.units" :key="unit.id" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    {{ unit.code || unit.name }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <p v-if="!courses?.length" class="rounded-md border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-[#273044]">No active courses have been published yet.</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
