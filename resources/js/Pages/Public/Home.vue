<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    organisation: Object,
    featuredCourses: Array,
    stats: Array,
});

const money = (value) => {
    const num = Number(value || 0);
    return num ? `Ksh ${num.toLocaleString()}` : 'Fee on request';
};

const durationText = (course) => {
    if (course.duration_type === 'custom' && course.duration) return course.duration;
    if (course.duration_semesters) return `${course.duration_semesters} Semester${course.duration_semesters > 1 ? 's' : ''}`;
    if (course.duration) return course.duration;
    return '2 Years';
};

const displayStats = props.stats?.length ? props.stats : [
    { label: 'Active Courses', value: '25+', icon: 'M12 6v12m6-6H6' },
    { label: 'Departments', value: '8', icon: 'M3 21h18M5 21V7l7-4 7 4v14' },
    { label: 'Registered Students', value: '1,200+', icon: 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m4-4a4 4 0 1 0 0-8 4 4 0 0 0 0 8z' },
    { label: 'Years of Excellence', value: '15+', icon: 'M12 15l-3 2 1-4-3-3h4l1-4 1 4h4l-3 3 1 4z' },
];

const courses = props.featuredCourses || [];

const courseImages = [
    '/images/course-business.jpg',
    '/images/course-it.jpg',
    '/images/course-education.jpg',
    '/images/course-accounting.jpg',
];

const features = [
    {
        icon: 'graduation',
        title: 'Quality Education',
        description: 'Industry-aligned curricula taught by experienced lecturers.',
    },
    {
        icon: 'briefcase',
        title: 'Practical Training',
        description: 'Hands-on experience and modern learning facilities.',
    },
    {
        icon: 'users',
        title: 'Student Support',
        description: 'Academic advising and personalised support.',
    },
    {
        icon: 'rocket',
        title: 'Career Opportunities',
        description: 'Pathway to internships and job placements.',
    },
];

// SVG Icon components
const icons = {
    graduation: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10.5L12 15L2 10.5L12 6L22 10.5Z" />
            <path d="M6 12.5V17C6 18.7 8.7 20 12 20C15.3 20 18 18.7 18 17V12.5" />
            <path d="M12 20V15" />
        </svg>
    `,
    briefcase: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
        </svg>
    `,
    users: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
    `,
    rocket: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z" />
            <path d="M2 17l10 5 10-5" />
            <path d="M2 12l10 5 10-5" />
            <path d="M12 2v5" />
            <path d="M12 17v5" />
        </svg>
    `,
};
</script>

<template>
    <PublicLayout :title="organisation?.name || 'Home'" :organisation="organisation">
        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-white dark:bg-slate-900">
                <!-- Decorative Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-violet-50/50 via-transparent to-blue-50/50 dark:from-violet-950/20 dark:via-transparent dark:to-blue-950/20"></div>
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-violet-100/30 blur-3xl dark:bg-violet-900/10"></div>
                <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-blue-100/30 blur-3xl dark:bg-blue-900/10"></div>

                <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                    <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                        <!-- Left Content -->
                        <div class="flex flex-col justify-center">
                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-100 px-4 py-1.5 text-xs font-semibold text-violet-700 dark:bg-violet-500/10 dark:text-violet-400">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-violet-500"></span>
                                </span>
                                {{ organisation?.short_name || organisation?.name || 'Bace Training College' }}
                            </div>

                            <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl">
                                Build Your Future
                                <span class="block bg-gradient-to-r from-violet-600 to-blue-600 bg-clip-text text-transparent">
                                    With Quality Education
                                </span>
                            </h1>

                            <p class="mt-4 max-w-lg text-base leading-relaxed text-slate-600 dark:text-slate-300 sm:text-lg">
                                {{ organisation?.description || 'Discover a world of opportunities with our career-focused programs and experienced lecturers.' }}
                            </p>

                            <div class="mt-8 flex flex-wrap gap-4">
                                <Link
                                    :href="route('courses')"
                                    class="group inline-flex items-center rounded-lg bg-violet-600 px-8 py-3.5 text-sm font-semibold text-white transition-all hover:bg-violet-700 hover:shadow-lg hover:shadow-violet-500/25"
                                >
                                    View Courses
                                    <svg class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>

                                <Link
                                    :href="route('contact')"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-8 py-3.5 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    Apply Now
                                </Link>
                            </div>

                            <!-- Trust Indicators -->
                            <div class="mt-8 flex items-center gap-6">
                                <div class="flex -space-x-2">
                                    <div v-for="n in 4" :key="n" class="h-8 w-8 rounded-full border-2 border-white bg-gradient-to-br from-violet-400 to-blue-400 dark:border-slate-800"></div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">1,200+ Students</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Trusted by learners worldwide</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Content - Stats Grid -->
                        <div class="grid grid-cols-2 gap-4 self-center">
                            <div
                                v-for="item in displayStats"
                                :key="item.label"
                                class="group rounded-xl border border-slate-200 bg-white p-6 text-center transition-all hover:border-violet-300 hover:shadow-lg hover:shadow-violet-500/5 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-violet-500/30"
                            >
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-violet-100 text-violet-600 transition-all group-hover:scale-110 dark:bg-violet-500/10 dark:text-violet-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path :d="item.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                    </svg>
                                </div>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ item.value }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Courses -->
            <section class="border-t border-slate-200 bg-slate-50 py-16 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-10 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Featured Courses</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Explore our comprehensive programs designed for your success</p>
                        </div>
                        <Link
                            :href="route('courses')"
                            class="inline-flex items-center text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                        >
                            View All
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div v-if="courses.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(course, index) in courses.slice(0, 4)"
                            :key="course.id"
                            class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition-all hover:-translate-y-2 hover:border-violet-300 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800 dark:hover:border-violet-500/30"
                        >
                            <div class="relative h-48 overflow-hidden bg-slate-100 dark:bg-slate-700">
                                <img
                                    :src="course.image_url || courseImages[index] || '/images/course-card.jpg'"
                                    :alt="course.name"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-slate-900/30 to-transparent"></div>
                                <div class="absolute bottom-3 left-3">
                                    <span class="inline-block rounded-full bg-violet-500/90 px-3 py-1 text-xs font-bold uppercase text-white backdrop-blur-sm dark:bg-violet-500/80">
                                        {{ course.code || course.qualification_level || 'Program' }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-5">
                                <h3 class="line-clamp-2 text-lg font-bold text-slate-900 dark:text-white">
                                    {{ course.name }}
                                </h3>

                                <div class="mt-4 space-y-2 text-sm text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ durationText(course) }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        {{ money(course.fees) }} / Year
                                    </div>
                                </div>

                                <Link
                                    :href="route('courses')"
                                    class="mt-5 inline-flex items-center text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                                >
                                    View Details
                                    <svg class="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-slate-300 p-16 text-center text-slate-500 dark:border-slate-700 dark:text-slate-400"
                    >
                        <p>No public courses are active yet.</p>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us -->
            <section class="bg-white py-16 dark:bg-slate-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-12 text-center">
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Why Choose Us</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">What makes us stand out from the rest</p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="group rounded-xl border border-slate-200 bg-white p-6 text-center transition-all hover:-translate-y-2 hover:border-violet-300 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800 dark:hover:border-violet-500/30"
                        >
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-violet-100 text-violet-600 transition-all group-hover:scale-110 dark:bg-violet-500/10 dark:text-violet-400">
                                <div class="h-8 w-8" v-html="icons[feature.icon]"></div>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ feature.description }}</p>
                            <Link
                                :href="route('contact')"
                                class="mt-4 inline-block text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                            >
                                Apply Now →
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="border-t border-slate-200 bg-slate-50 py-16 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 to-blue-600 p-10 sm:p-12">
                        <!-- Decorative elements -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-white"></div>
                            <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-white"></div>
                            <div class="absolute top-1/2 left-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></div>
                        </div>

                        <div class="relative text-center">
                            <h2 class="text-3xl font-bold text-white sm:text-4xl">
                                Ready to start your journey?
                            </h2>
                            <p class="mx-auto mt-3 max-w-xl text-lg text-violet-100">
                                Join thousands of students who are building their future with us.
                            </p>
                            <div class="mt-8 flex flex-wrap justify-center gap-4">
                                <Link
                                    :href="route('contact')"
                                    class="inline-flex items-center rounded-lg bg-white px-8 py-3.5 text-sm font-semibold text-violet-600 transition-all hover:scale-105 hover:shadow-xl"
                                >
                                    Apply Now
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                                <Link
                                    :href="route('courses')"
                                    class="inline-flex items-center rounded-lg border border-white/30 px-8 py-3.5 text-sm font-semibold text-white transition-all hover:bg-white/10 hover:scale-105"
                                >
                                    Explore Courses
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style scoped>
/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Line clamp utilities */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar */
@media (prefers-color-scheme: dark) {
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #1e293b;
    }

    ::-webkit-scrollbar-thumb {
        background: #7c3aed;
        border-radius: 9999px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #6d28d9;
    }
}

/* Ping animation for status dot */
@keyframes ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* SVG icon styling */
.group:hover svg {
    transform: scale(1.1);
}
</style>
