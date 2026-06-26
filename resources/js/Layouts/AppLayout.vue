<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    title: String,
    actions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const sidebarOpen = ref(localStorage.getItem('sidebar') !== 'closed');
const organisation = computed(() => page.props.organisation || {});
const organisationInitials = computed(() => (organisation.value.short_name || organisation.value.name || 'ORG').substring(0, 3).toUpperCase());
const userPermissions = computed(() => page.props.auth?.permissions || []);
const can = (permission) => permission
    .split('|')
    .some((item) => userPermissions.value.includes(item));

const navigation = computed(() => [
    { name: 'Dashboard', route: 'dashboard', href: route('dashboard'), icon: 'dashboard' },
    { name: 'Users', route: 'admin.users.*', href: route('admin.users.index'), icon: 'users', permission: 'users.view' },
    { name: 'Students', route: 'students.*', href: route('students.index'), icon: 'student', permission: 'students.view' },
    { name: 'Payments', route: 'finance.payments.*', href: route('finance.payments.index'), icon: 'payments', permission: 'payments.view|finance.view' },
    { name: 'Academic Settings', route: 'academics.settings.*', href: route('academics.settings.index'), icon: 'calendar', permission: 'academic-settings.view|classes.manage' },
    { name: 'Departments', route: 'academics.departments.*', href: route('academics.departments.index'), icon: 'building', permission: 'departments.view|classes.manage' },
    { name: 'Courses', route: 'academics.courses.*', href: route('academics.courses.index'), icon: 'book', permission: 'courses.view|classes.manage' },
    { name: 'Units', route: 'academics.units.*', href: route('academics.units.index'), icon: 'layers', permission: 'units.view|classes.manage' },
    { name: 'Lecturers', route: 'academics.lecturers.*', href: route('academics.lecturers.index'), icon: 'lecturer', permission: 'lecturers.view|classes.manage' },
    { name: 'Enrollments', route: 'academics.enrollments.*', href: route('academics.enrollments.index'), icon: 'clipboard', permission: 'enrollments.view|classes.manage' },
    { name: 'Assignments', route: 'academics.assignments.*', href: route('academics.assignments.index'), icon: 'file', permission: 'assignments.view|assignments.manage' },
    { name: 'Roles', route: 'admin.roles.*', href: route('admin.roles.index'), icon: 'shield', permission: 'roles.view' },
    { name: 'Permissions', route: 'admin.permissions.*', href: route('admin.permissions.index'), icon: 'key', permission: 'permissions.view|permissions.manage' },
    { name: 'Organisation Settings', route: 'admin.organisation-settings.*', href: route('admin.organisation-settings.index'), icon: 'building', permission: 'organisation-settings.view|classes.manage' },
    { name: 'System Health', route: 'admin.system-health', href: route('admin.system-health'), icon: 'activity', permission: 'system-health.view|health.view' },
    page.props.jetstream.hasApiFeatures ? { name: 'API Tokens', route: 'api-tokens.index', href: route('api-tokens.index'), icon: 'mobile', permission: 'api-tokens.view' } : null,
].filter((item) => item && (!item.permission || can(item.permission))));

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('sidebar', sidebarOpen.value ? 'open' : 'closed');
};

const logout = () => router.post(route('logout'));
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#090c11] dark:text-gray-100">
            <aside
                class="fixed inset-y-0 left-0 z-40 hidden border-r border-gray-200 bg-white transition-all duration-200 lg:flex lg:flex-col dark:border-[#232837] dark:bg-[#11141b]"
                :class="sidebarOpen ? 'w-60' : 'w-[72px]'"
            >
                <div class="flex h-14 items-center gap-3 border-b border-gray-200 px-5 dark:border-[#232837]">
                    <div class="inline-flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-violet-500 text-[10px] font-bold text-white">
                        <img v-if="organisation.logo_url" :src="organisation.logo_url" :alt="organisation.name || 'Organisation logo'" class="size-full object-cover">
                        <span v-else>{{ organisationInitials }}</span>
                    </div>
                    <span v-if="sidebarOpen" class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ organisation.short_name || organisation.name || 'ISP' }}</span>
                </div>

                <div class="flex-1 overflow-y-auto px-2 py-4">
                    <p v-if="sidebarOpen" class="px-3 pb-3 text-[11px] font-medium uppercase tracking-wider text-gray-500">Menu</p>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            class="group flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium transition"
                            :class="route().current(item.route) ? 'bg-violet-500/10 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-[#1a1f2f] dark:hover:text-gray-100'"
                            :title="sidebarOpen ? null : item.name"
                        >
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path v-if="item.icon === 'dashboard'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" />
                                <path v-else-if="item.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m15 0v-1a3 3 0 0 0-2-2.83M13 5.17a3 3 0 0 1 0 5.66M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                <path v-else-if="item.icon === 'student'" stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0M4 5l8-3 8 3-8 3-8-3Zm3 2v3" />
                                <path v-else-if="item.icon === 'payments'" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v10H4V7Zm0 3h16M7 14h4m5 0h1" />
                                <path v-else-if="item.icon === 'calendar'" stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Zm3 8h3m3 0h3m-9 4h3m3 0h3" />
                                <path v-else-if="item.icon === 'building'" stroke-linecap="round" stroke-linejoin="round" d="M4 21h16M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1" />
                                <path v-else-if="item.icon === 'book'" stroke-linecap="round" stroke-linejoin="round" d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21V5.5Zm0 0A2.5 2.5 0 0 1 7.5 8H20" />
                                <path v-else-if="item.icon === 'layers'" stroke-linecap="round" stroke-linejoin="round" d="m12 3 8 4-8 4-8-4 8-4Zm-8 8 8 4 8-4M4 15l8 4 8-4" />
                                <path v-else-if="item.icon === 'lecturer'" stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0M17 10h4v7h-4" />
                                <path v-else-if="item.icon === 'clipboard'" stroke-linecap="round" stroke-linejoin="round" d="M9 4h6l1 2h3v15H5V6h3l1-2Zm0 7h6m-6 4h6" />
                                <path v-else-if="item.icon === 'file'" stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7V3Zm7 0v5h5M9 13h6m-6 4h6" />
                                <path v-else-if="item.icon === 'shield'" stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-3.5 7-10V5l-7-2-7 2v6c0 6.5 7 10 7 10Z" />
                                <path v-else-if="item.icon === 'key'" stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 1 1-1.2 2.86L4 19.66V22h3v-2h2v-2h2l2.14-2.14A4 4 0 0 1 15 7Z" />
                                <path v-else-if="item.icon === 'activity'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l2-7 4 14 2-7h4" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm2 17h2" />
                            </svg>
                            <span v-if="sidebarOpen">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-[#252b3a] dark:text-gray-300">
                            {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div v-if="sidebarOpen" class="min-w-0">
                            <p class="truncate text-xs font-semibold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </div>
                    <button
                        v-if="sidebarOpen"
                        class="mt-3 h-7 w-full rounded-md border border-gray-200 text-xs text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
                        @click="logout"
                    >
                        Log Out
                    </button>
                </div>
            </aside>

            <div class="flex min-h-screen flex-col transition-all duration-200" :class="sidebarOpen ? 'lg:pl-60' : 'lg:pl-[72px]'">
                <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
                            title="Toggle sidebar"
                            @click="toggleSidebar"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
                            <p v-if="$slots.subtitle" class="text-xs text-gray-500"><slot name="subtitle" /></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <slot name="actions" />
                        <ThemeToggle />
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="inline-flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 ring-1 ring-gray-200 transition hover:ring-violet-400 dark:bg-[#252b3a] dark:text-gray-200 dark:ring-[#2a3040] dark:hover:ring-violet-500/60">
                                    {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700" />
                                <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                <DropdownLink v-if="$page.props.jetstream.hasApiFeatures && can('api-tokens.view')" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                <div class="border-t border-gray-200 dark:border-gray-700" />
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">Log Out</DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </header>

                <div class="flex gap-2 overflow-x-auto border-b border-gray-200 bg-white px-4 py-2 lg:hidden dark:border-[#232837] dark:bg-[#11141b]">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="route().current(item.route) ? 'bg-violet-500/10 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-gray-500 dark:text-gray-400'"
                    >
                        {{ item.name }}
                    </Link>
                </div>

                <main class="mx-auto w-full max-w-[1180px] flex-1 px-4 py-6 sm:px-6">
                    <slot />
                </main>

                <footer class="mx-auto mt-auto flex w-full max-w-[1180px] flex-col gap-1 border-t border-gray-200 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-[#232837]">
                    <span>ISP SaaS v1.0.0</span>
                    <span>Maintained by bAtasi</span>
                </footer>
            </div>
        </div>
    </div>
</template>
