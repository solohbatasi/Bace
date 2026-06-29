<script setup>
import { computed, reactive, ref, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    RadialLinearScale,
    Title as ChartTitle,
    Tooltip,
    Legend,
    Filler,
    DoughnutController,
    PieController,
    BarController,
    LineController,
} from 'chart.js';
import { Line, Bar, Doughnut, Pie } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    RadialLinearScale,
    ChartTitle,
    Tooltip,
    Legend,
    Filler,
    DoughnutController,
    PieController,
    BarController,
    LineController
);

const props = defineProps({
    mode: String,
    dashboard: Object,
});

// Currency formatter
const money = (value) => `KES ${Number(value || 0).toLocaleString()}`;

// Number formatter
const formatNumber = (value) => Number(value || 0).toLocaleString();

// Compact number formatter
const formatCompact = (value) => Intl.NumberFormat('en', {
    notation: Number(value || 0) >= 1000 ? 'compact' : 'standard',
    maximumFractionDigits: 1,
}).format(Number(value || 0));

// Display value with proper formatting
const displayValue = (item) => item.money ? money(item.value) : formatNumber(item.value);

// Tone color classes
const toneClasses = {
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
};

// Tone border colors
const toneBorderClasses = {
    violet: 'border-violet-500',
    blue: 'border-blue-500',
    sky: 'border-sky-500',
    amber: 'border-amber-500',
    red: 'border-red-500',
    emerald: 'border-emerald-500',
};

// Tone solid colors for charts
const toneSolidColors = {
    violet: '#7c3aed',
    blue: '#2563eb',
    sky: '#0284c7',
    amber: '#d97706',
    red: '#dc2626',
    emerald: '#059669',
};

// Chart color palette
const chartColors = [
    '#7c3aed', '#2563eb', '#059669', '#d97706', '#dc2626',
    '#0891b2', '#7c3aed', '#6b7280', '#8b5cf6', '#06b6d4'
];

// Icon paths
const iconPaths = {
    Students: 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2 M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M21 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
    'Active Learners': 'M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z',
    Lecturers: 'M22 10.5 12 15 2 10.5 12 6l10 4.5Z M6 12.5V17c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5',
    Departments: 'M4 21V5a2 2 0 0 1 2-2h4v18 M14 21V9h4a2 2 0 0 1 2 2v10 M3 21h18 M7 7h1 M7 11h1 M16 13h1 M16 17h1',
    'Total Revenue': 'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6',
    'Confirmed Payments': 'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6',
    'Ticket Check-ins': 'M9 12l2 2 4-5 M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a3 3 0 0 0 0 6v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a3 3 0 0 0 0-6V7Z',
    'Results Recorded': 'M9 11l2 2 4-5 M7 3h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2Z',
};

// Compute title
const title = computed(() => {
    const titles = {
        lecturer: 'Lecturer Dashboard',
        learner: 'My Dashboard',
        default: 'Institution Dashboard'
    };
    return titles[props.mode] || titles.default;
});

// Dashboard filter
const dashboardFilter = reactive({
    period: props.dashboard?.filters?.period || 'this_year',
    start_date: props.dashboard?.filters?.start_date || '',
    end_date: props.dashboard?.filters?.end_date || '',
});

// Period options
const periodOptions = [
    { value: 'this_year', label: 'This Year' },
    { value: 'last_year', label: 'Last Year' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'custom', label: 'Custom' },
];

// Apply filter
const applyDashboardFilter = () => {
    router.get(route('dashboard'), dashboardFilter, { preserveScroll: true, replace: true });
};

// Chart helper functions
const metricValue = (item) => Number(item?.raw ?? item?.value ?? 0) || 0;
const maxMetric = (items = []) => Math.max(...items.map(item => metricValue(item)), 1);
const metricPercent = (value, max) => Math.min(100, Math.round((Number(value || 0) / Math.max(Number(max || 0), 1)) * 100));
const chartTotal = (items = []) => items.reduce((sum, item) => sum + metricValue(item), 0);
const chartByType = (type) => (props.dashboard?.charts || []).find(chart => chart.type === type) || { items: [], title: '', subtitle: '', tone: 'violet' };
const chartSummary = (chart) => {
    const items = chart?.items || [];
    const topItem = [...items].sort((a, b) => metricValue(b) - metricValue(a))[0];

    return {
        total: chartTotal(items),
        top: topItem?.label || 'No data',
        topValue: metricValue(topItem),
    };
};

// Line chart data
const lineChartData = computed(() => {
    const chart = chartByType('line');
    const items = chart.items || [];
    const labels = items.map(item => item.label);
    const data = items.map(item => metricValue(item));

    return {
        labels,
        datasets: [{
            label: chart.title || 'Enrollments',
            data,
            borderColor: toneSolidColors[chart.tone] || '#7c3aed',
            backgroundColor: toneSolidColors[chart.tone] ? `${toneSolidColors[chart.tone]}20` : 'rgba(124, 58, 237, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: toneSolidColors[chart.tone] || '#7c3aed',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
        }]
    };
});

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: function(context) {
                    return `${context.parsed.y.toLocaleString()} enrollments`;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.06)',
                drawBorder: false,
            },
            ticks: {
                font: {
                    size: 11,
                    weight: '500',
                },
                color: '#94a3b8',
                callback: function(value) {
                    return formatCompact(value);
                }
            }
        },
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: {
                    size: 11,
                    weight: '500',
                },
                color: '#94a3b8',
                maxRotation: 45,
                minRotation: 0,
            }
        }
    }
};

// Bar chart data
const barChartData = computed(() => {
    const chart = chartByType('bar');
    const items = chart.items || [];
    const labels = items.map(item => item.label);
    const data = items.map(item => metricValue(item));

    return {
        labels,
        datasets: [{
            label: chart.title || 'Tickets',
            data,
            backgroundColor: toneSolidColors[chart.tone] || '#7c3aed',
            borderColor: toneSolidColors[chart.tone] || '#7c3aed',
            borderWidth: 0,
            borderRadius: 6,
            maxBarThickness: 40,
        }]
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: function(context) {
                    return `${context.parsed.y.toLocaleString()} items`;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.06)',
                drawBorder: false,
            },
            ticks: {
                font: {
                    size: 11,
                    weight: '500',
                },
                color: '#94a3b8',
                callback: function(value) {
                    return formatCompact(value);
                }
            }
        },
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: {
                    size: 11,
                    weight: '500',
                },
                color: '#94a3b8',
                maxRotation: 45,
                minRotation: 0,
            }
        }
    }
};

// Doughnut chart data
const doughnutChartData = computed(() => {
    const chart = chartByType('doughnut');
    const items = chart.items || [];
    const data = items.map(item => metricValue(item));
    const colors = items.map((_, index) => chartColors[index % chartColors.length]);

    return {
        labels: items.map(item => item.label),
        datasets: [{
            data,
            backgroundColor: colors,
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 15,
        }]
    };
});

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                padding: 12,
                usePointStyle: true,
                pointStyle: 'circle',
                font: {
                    size: 12,
                    weight: '600',
                },
                color: '#334155',
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                    return `${context.label}: ${money(context.parsed)} (${percentage}%)`;
                }
            }
        }
    }
};

// Pie chart data
const pieChartData = computed(() => {
    const chart = chartByType('pie');
    const items = chart.items || [];
    const data = items.map(item => metricValue(item));
    const colors = items.map((_, index) => chartColors[index % chartColors.length]);

    return {
        labels: items.map(item => item.label),
        datasets: [{
            data,
            backgroundColor: colors,
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 15,
        }]
    };
});

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 12,
                usePointStyle: true,
                pointStyle: 'circle',
                font: {
                    size: 12,
                    weight: '600',
                },
                color: '#334155',
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                    return `${context.label}: ${formatNumber(context.parsed)} (${percentage}%)`;
                }
            }
        }
    }
};

// Watch for filter changes
watch(() => dashboardFilter.period, () => {
    if (dashboardFilter.period !== 'custom') {
        applyDashboardFilter();
    }
});

// Initialize theme
onMounted(() => {
    // Dark mode support for charts
    const isDark = document.documentElement.classList.contains('dark');
    // Update chart colors based on theme if needed
});
</script>

<template>
    <AppLayout :title="title">
        <!-- Lecturer Dashboard -->
        <template v-if="props.mode === 'lecturer'">
            <div class="space-y-6 pb-24">
                <!-- Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Lecturer Dashboard
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Welcome back, <span class="font-semibold text-slate-700 dark:text-slate-300">{{ dashboard.lecturerName }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <select
                            v-model="dashboardFilter.period"
                            class="h-10 rounded-lg border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            @change="applyDashboardFilter"
                        >
                            <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <template v-if="dashboardFilter.period === 'custom'">
                            <input
                                v-model="dashboardFilter.start_date"
                                type="date"
                                class="h-10 rounded-lg border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                @change="applyDashboardFilter"
                            >
                            <input
                                v-model="dashboardFilter.end_date"
                                type="date"
                                class="h-10 rounded-lg border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                @change="applyDashboardFilter"
                            >
                        </template>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid gap-5 lg:grid-cols-2">
                    <div
                        v-for="item in dashboard.stats"
                        :key="item.label"
                        class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold transition group-hover:scale-110" :class="toneClasses[item.tone]">
                                {{ item.label.slice(0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ item.value }}</p>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses and Attendance -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- My Courses -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">My Courses</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="course in dashboard.courses"
                                    :key="course.code"
                                    class="rounded-lg border border-slate-100 p-4 transition hover:border-violet-200 dark:border-slate-700 dark:hover:border-violet-500"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ course.name }}</p>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ course.code }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-sm font-semibold text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                                            {{ course.students }} students
                                        </span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="unit in course.units"
                                            :key="`${unit.code}-${unit.class}`"
                                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                        >
                                            {{ unit.code }} / {{ unit.class }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="!dashboard.courses?.length" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No teaching assignments yet.
                                </p>
                                <Link
                                    :href="route('academics.courses.index')"
                                    class="inline-flex items-center justify-center rounded-lg border border-violet-500 px-5 py-2.5 text-sm font-semibold text-violet-600 transition hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-500/10"
                                >
                                    View All Courses
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Overview -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Attendance Overview</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="dashboard.attendance?.length" class="h-72">
                                <div class="flex h-full items-end gap-6 border-b border-slate-200 pb-2 dark:border-slate-700">
                                    <div
                                        v-for="item in dashboard.attendance"
                                        :key="item.label"
                                        class="flex flex-1 flex-col items-center gap-2"
                                    >
                                        <div class="relative w-full max-w-[60px]">
                                            <div
                                                class="rounded-t-lg bg-gradient-to-t from-violet-600 to-violet-400 transition-all hover:opacity-80"
                                                :style="{ height: `${Math.max(item.value, 4)}%`, minHeight: '20px' }"
                                            />
                                            <div class="absolute -top-7 left-1/2 -translate-x-1/2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                                {{ item.value }}%
                                            </div>
                                        </div>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ item.label }}</p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                No attendance records yet.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Assignments and Exams -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- Pending Assignments -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pending Assignments</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="assignment in dashboard.pendingAssignments"
                                    :key="assignment.title"
                                    class="rounded-lg border-l-4 border-violet-500 bg-slate-50 p-4 dark:bg-slate-800/50"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ assignment.title }}</p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                {{ assignment.unit }}
                                                <span v-if="assignment.due" class="inline-flex items-center">
                                                    <span class="mx-2">·</span>
                                                    Due {{ assignment.due }}
                                                </span>
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-sm font-semibold text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                                            {{ assignment.submissions }} submitted
                                        </span>
                                    </div>
                                </div>
                                <p v-if="!dashboard.pendingAssignments?.length" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No pending assignments.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Exams -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Upcoming Exams</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="exam in dashboard.upcomingExams"
                                    :key="exam.title"
                                    class="rounded-lg border-l-4 border-blue-500 bg-slate-50 p-4 dark:bg-slate-800/50"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ exam.title }}</p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                {{ exam.target }}
                                                <span v-if="exam.date" class="inline-flex items-center">
                                                    <span class="mx-2">·</span>
                                                    {{ exam.date }}
                                                </span>
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                                            Max {{ exam.max_score || 100 }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="!dashboard.upcomingExams?.length" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No upcoming exams.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Learner Dashboard -->
        <template v-else-if="props.mode === 'learner'">
            <div class="space-y-6">
                <!-- Header -->
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        My Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Welcome back, <span class="font-semibold text-slate-700 dark:text-slate-300">{{ dashboard.studentName }}</span>
                    </p>
                </div>

                <!-- Quick Access Grid -->
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="item in dashboard.quickAccess"
                        :key="item.label"
                        :href="route(item.route)"
                        class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-violet-400 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl text-xl font-bold transition group-hover:scale-110" :class="toneClasses[item.tone]">
                            {{ item.label.slice(3, 4) || item.label.slice(0, 1) }}
                        </div>
                        <p class="mt-4 font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Institution Dashboard -->
        <template v-else>
            <div class="space-y-6 pb-24">
                <!-- Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Institution Dashboard
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Comprehensive overview of your institution
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <select
                            v-model="dashboardFilter.period"
                            class="h-10 rounded-lg border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            @change="applyDashboardFilter"
                        >
                            <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <template v-if="dashboardFilter.period === 'custom'">
                            <input
                                v-model="dashboardFilter.start_date"
                                type="date"
                                class="h-10 rounded-lg border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                @change="applyDashboardFilter"
                            >
                            <input
                                v-model="dashboardFilter.end_date"
                                type="date"
                                class="h-10 rounded-lg border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                @change="applyDashboardFilter"
                            >
                        </template>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid gap-5 lg:grid-cols-2">
                    <div
                        v-for="item in dashboard.stats"
                        :key="item.label"
                        class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl transition group-hover:scale-110" :class="toneClasses[item.tone]">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path :d="iconPaths[item.label] || iconPaths.Students" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ displayValue(item) }}</p>
                                <p v-if="item.change" class="mt-1 text-sm font-semibold" :class="item.change?.startsWith('-') ? 'text-red-500' : 'text-emerald-500'">
                                    {{ item.change }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- Line Chart -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ chartByType('line').title }}
                                    </h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Total {{ formatCompact(chartTotal(chartByType('line').items)) }} registrations
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="toneClasses[chartByType('line').tone]">
                                    {{ chartByType('line').subtitle }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-72">
                                <Line
                                    v-if="chartByType('line').items?.length"
                                    :data="lineChartData"
                                    :options="lineChartOptions"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                                    No data available
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Chart -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ chartByType('pie').title }}
                                    </h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Largest: {{ chartSummary(chartByType('pie')).top }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="toneClasses[chartByType('pie').tone]">
                                    {{ chartByType('pie').subtitle }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-72">
                                <Pie
                                    v-if="chartByType('pie').items?.length"
                                    :data="pieChartData"
                                    :options="pieChartOptions"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                                    No data available
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- Doughnut Chart -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ chartByType('doughnut').title }}
                                    </h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Total {{ money(chartTotal(chartByType('doughnut').items)) }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="toneClasses[chartByType('doughnut').tone]">
                                    {{ chartByType('doughnut').subtitle }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-72">
                                <Doughnut
                                    v-if="chartByType('doughnut').items?.length"
                                    :data="doughnutChartData"
                                    :options="doughnutChartOptions"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                                    No data available
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ chartByType('bar').title }}
                                    </h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Peak: {{ chartSummary(chartByType('bar')).top }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="toneClasses[chartByType('bar').tone]">
                                    {{ chartByType('bar').subtitle }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-72">
                                <Bar
                                    v-if="chartByType('bar').items?.length"
                                    :data="barChartData"
                                    :options="barChartOptions"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                                    No data available
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Overview Panels -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <div
                        v-for="panel in dashboard.overview"
                        :key="panel.title"
                        class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ panel.title }}</h2>
                        </div>
                        <div class="p-6">
                            <!-- Doughnut Overview -->
                            <div v-if="panel.chart?.type === 'doughnut'" class="mb-6">
                                <div class="flex flex-col items-center gap-6 sm:flex-row">
                                    <div class="relative h-44 w-44 shrink-0">
                                        <svg viewBox="0 0 100 100" class="h-44 w-44 -rotate-90">
                                            <circle cx="50" cy="50" r="36" fill="none" class="stroke-slate-200 dark:stroke-slate-700" stroke-width="16" />
                                            <circle
                                                cx="50"
                                                cy="50"
                                                r="36"
                                                fill="none"
                                                :stroke="toneSolidColors[panel.tone]"
                                                stroke-width="16"
                                                :stroke-dasharray="`${(panel.chart.primary / panel.chart.total) * 226.19} 226.19`"
                                                :stroke-dashoffset="0"
                                            />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                                {{ metricPercent(panel.chart.primary, panel.chart.total) }}%
                                            </p>
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Complete</p>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-4">
                                        <div v-for="item in panel.items" :key="`${panel.title}-line-${item.label}`">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ item.label }}</span>
                                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ item.value }}</span>
                                            </div>
                                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :style="{
                                                        width: `${metricPercent(metricValue(item), maxMetric(panel.items))}%`,
                                                        backgroundColor: toneSolidColors[panel.tone]
                                                    }"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bar Overview -->
                            <div v-else class="mb-6">
                                <div class="flex h-48 items-end gap-4 border-b border-slate-200 pb-2 dark:border-slate-700">
                                    <div
                                        v-for="item in panel.items"
                                        :key="`${panel.title}-bar-${item.label}`"
                                        class="relative flex flex-1 flex-col items-center gap-2"
                                    >
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ item.value }}</span>
                                        <div
                                            class="w-full max-w-[40px] rounded-t-lg transition-all"
                                            :style="{
                                                height: `${Math.max(metricPercent(metricValue(item), maxMetric(panel.items)), 8)}%`,
                                                minHeight: '20px',
                                                backgroundColor: toneSolidColors[panel.tone]
                                            }"
                                        />
                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Cards -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div
                                    v-for="item in panel.items"
                                    :key="`${panel.title}-${item.label}`"
                                    class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50"
                                >
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ item.value }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ item.detail }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rankings and Attention -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- Rankings -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ dashboard.rankings.title }}</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in dashboard.rankings.items"
                                    :key="item.label"
                                    class="flex items-center gap-4 rounded-lg p-3 transition hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                >
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-100 font-bold text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ item.detail }}</p>
                                    </div>
                                    <span class="font-bold text-violet-600 dark:text-violet-400">{{ item.value }}</span>
                                </div>
                                <p v-if="!dashboard.rankings.items?.length" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No course activity in this period.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Attention -->
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ dashboard.attention.title }}</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="item in dashboard.attention.items"
                                    :key="item.label"
                                    class="rounded-lg border-l-4 p-4 transition hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                    :class="[toneBorderClasses[item.tone], 'bg-slate-50 dark:bg-slate-800/50']"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ item.detail }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold" :class="toneClasses[item.tone]">
                                            {{ item.value }}
                                        </span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :style="{
                                                width: `${metricPercent(metricValue(item), maxMetric(dashboard.attention.items))}%`,
                                                backgroundColor: toneSolidColors[item.tone]
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 z-30 -mx-4 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                    <div class="mx-auto flex max-w-7xl items-center gap-3 overflow-x-auto">
                        <div class="hidden shrink-0 pr-2 sm:block">
                            <p class="text-xs font-bold uppercase text-slate-400">Quick Access</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Common actions</p>
                        </div>
                        <Link
                            v-for="item in dashboard.quickAccess"
                            :key="item.label"
                            :href="route(item.route)"
                            class="min-w-32 shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-sm font-bold text-slate-700 transition hover:border-violet-400 hover:bg-white hover:text-violet-700 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700 dark:hover:text-violet-300"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
