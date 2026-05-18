<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ metrics: Object, security: Array, sessions: Array, tokens: Array });

const revokeSession = (session) => {
    if (confirm('Revoke this browser session?')) router.delete(route('admin.system-health.sessions.destroy', session.id), { preserveScroll: true });
};
const revokeToken = (token) => {
    if (confirm('Revoke this API token?')) router.delete(route('admin.system-health.tokens.destroy', token.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="System Health">
        <template #actions>
            <button class="inline-flex h-8 items-center gap-2 rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white" @click="router.reload()">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M20 9a8 8 0 0 0-14.9-4M4 15a8 8 0 0 0 14.9 4" />
                </svg>
                Refresh
            </button>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="(value, key) in metrics" :key="key" class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ key.replaceAll('_', ' ') }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Security enforcement</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                <div v-for="item in security" :key="item.name" class="grid gap-2 px-5 py-4 md:grid-cols-[220px_150px_1fr]">
                    <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                    <span class="w-fit rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-400">{{ item.status }}</span>
                    <p class="text-sm text-gray-500">{{ item.detail }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Frontend sessions</h2>
                </div>
                <table v-if="sessions.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                        <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                            <td class="px-5 py-4"><p class="font-medium text-gray-900 dark:text-white">{{ session.user }}</p><p class="text-xs text-gray-500">{{ session.ip_address }} - {{ session.last_activity }}</p></td>
                            <td class="px-5 py-4 text-xs text-gray-500">{{ session.user_agent }}</td>
                            <td class="px-5 py-4 text-right"><button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="revokeSession(session)">Revoke</button></td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="flex min-h-[220px] flex-col items-center justify-center px-6 text-center">
                    <p class="font-semibold text-gray-700 dark:text-gray-300">No active sessions</p>
                    <p class="mt-1 text-sm text-gray-500">Frontend browser sessions will appear here.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Mobile API tokens</h2>
                </div>
                <table v-if="tokens.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                        <tr v-for="token in tokens" :key="token.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                            <td class="px-5 py-4"><p class="font-medium text-gray-900 dark:text-white">{{ token.name }}</p><p class="text-xs text-gray-500">{{ token.owner }} - Last used {{ token.last_used_at || 'never' }}</p></td>
                            <td class="px-5 py-4 text-xs text-gray-500">{{ token.abilities?.join(', ') || 'All abilities' }}</td>
                            <td class="px-5 py-4 text-right"><button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="revokeToken(token)">Revoke</button></td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="flex min-h-[220px] flex-col items-center justify-center px-6 text-center">
                    <p class="font-semibold text-gray-700 dark:text-gray-300">No API tokens</p>
                    <p class="mt-1 text-sm text-gray-500">Sanctum tokens for mobile access will appear here.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
