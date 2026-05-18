<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Welcome, Admin">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Users</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Ready</p>
                <p class="mt-2 text-xs text-emerald-400">Access controls online</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Roles</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">RBAC</p>
                <p class="mt-2 text-xs text-gray-500">Role-based permissions</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">API Tokens</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Sanctum</p>
                <p class="mt-2 text-xs text-gray-500">Mobile access enabled</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Sessions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Tracked</p>
                <p class="mt-2 text-xs text-emerald-400">Database session driver</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Access Operations</h2>
                    <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-400">Healthy</span>
                </div>
                <div class="grid gap-3 p-5 md:grid-cols-3">
                    <a :href="route('admin.users.index')" class="rounded-md border border-gray-200 p-4 transition hover:border-violet-500/60 dark:border-[#2a3040]">
                        <p class="font-semibold text-gray-900 dark:text-white">Manage Users</p>
                        <p class="mt-1 text-sm text-gray-500">Create accounts, assign access, suspend, or terminate.</p>
                    </a>
                    <a :href="route('admin.roles.index')" class="rounded-md border border-gray-200 p-4 transition hover:border-violet-500/60 dark:border-[#2a3040]">
                        <p class="font-semibold text-gray-900 dark:text-white">Roles</p>
                        <p class="mt-1 text-sm text-gray-500">Bundle permissions into team-ready profiles.</p>
                    </a>
                    <a :href="route('admin.system-health')" class="rounded-md border border-gray-200 p-4 transition hover:border-violet-500/60 dark:border-[#2a3040]">
                        <p class="font-semibold text-gray-900 dark:text-white">System Health</p>
                        <p class="mt-1 text-sm text-gray-500">Review security, sessions, and mobile tokens.</p>
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Security Status</h2>
                </div>
                <div class="space-y-4 p-5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Password hashing</span>
                        <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-400">Enforced</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Two-factor auth</span>
                        <span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-semibold text-blue-300">Available</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Suspension middleware</span>
                        <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-400">Active</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">API tokens</span>
                        <span class="rounded-md bg-violet-500/10 px-2 py-1 text-xs font-semibold text-violet-300">Enabled</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
