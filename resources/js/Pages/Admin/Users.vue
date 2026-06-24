<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ users: Object, roles: Array, permissions: Array, filters: Object });

const showingUserModal = ref(false);
const deletingUser = ref(null);
const filter = reactive({ search: props.filters.search || '', status: props.filters.status || '', role: props.filters.role || '' });
const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    status: 'active',
    role_ids: [],
    permission_ids: [],
});

const stats = computed(() => ({
    total: props.users.total,
    active: props.users.data.filter((user) => user.status === 'active').length,
    suspended: props.users.data.filter((user) => user.status === 'suspended').length,
    terminated: props.users.data.filter((user) => user.status === 'terminated').length,
}));

watch(filter, () => router.get(route('admin.users.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.status = 'active';
    form.role_ids = [];
    form.permission_ids = [];
};

const openCreateModal = () => {
    resetForm();
    showingUserModal.value = true;
};

const edit = (user) => {
    resetForm();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.status = user.status;
    form.role_ids = user.roles.map((role) => role.id);
    form.permission_ids = user.permissions.map((permission) => permission.id);
    showingUserModal.value = true;
};

const closeModal = () => {
    showingUserModal.value = false;
    resetForm();
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    form.id ? form.put(route('admin.users.update', form.id), options) : form.post(route('admin.users.store'), options);
};

const destroyUser = (user) => {
    deletingUser.value = user;
};

const closeDeleteUserModal = () => {
    deletingUser.value = null;
};

const confirmDeleteUser = () => {
    if (!deletingUser.value) return;

    router.delete(route('admin.users.destroy', deletingUser.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteUserModal,
    });
};

const postAction = (name, user) => router.post(route(`admin.users.${name}`, user.id), {}, { preserveScroll: true });

const exportCsv = () => {
    const rows = [
        ['Name', 'Email', 'Roles', 'Direct Permissions', 'Status', 'Status Reason', 'Created'],
        ...props.users.data.map((user) => [
            user.name,
            user.email,
            user.roles.map((role) => role.name).join('; '),
            user.permissions.map((permission) => permission.name).join('; '),
            user.status,
            user.status_reason || '',
            user.created_at,
        ]),
    ];

    const csv = rows.map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'users.csv';
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <AppLayout title="Users">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Suspended</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.suspended }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Terminated</p>
                <p class="mt-2 text-3xl font-bold text-red-400">{{ stats.terminated }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <div class="flex flex-wrap gap-2">
                <button class="inline-flex h-8 items-center gap-2 rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white" type="button" @click="exportCsv">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16" />
                    </svg>
                    Export CSV
                </button>
                <button class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
                    <span class="text-base leading-none">+</span>
                    New User
                </button>
                <button
                    v-for="status in ['', 'active', 'suspended', 'terminated']"
                    :key="status || 'all'"
                    class="h-8 rounded-md border px-3 text-xs font-medium capitalize"
                    :class="filter.status === status ? 'border-violet-500 bg-violet-500 text-white' : 'border-gray-200 text-gray-500 hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-400 dark:hover:text-white'"
                    @click="filter.status = status"
                >
                    {{ status || 'All' }}
                </button>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.role" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All roles</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search name, email...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="users.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Access</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <p class="text-gray-700 dark:text-gray-300">{{ user.roles.map((role) => role.name).join(', ') || 'No roles' }}</p>
                            <p class="mt-1 text-gray-500">{{ user.permissions.map((permission) => permission.name).join(', ') || 'No direct permissions' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="user.status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : user.status === 'suspended' ? 'bg-amber-500/10 text-amber-400' : 'bg-red-500/10 text-red-400'">
                                - {{ user.status }}
                            </span>
                            <p v-if="user.status_reason" class="mt-1 text-xs text-gray-500">{{ user.status_reason }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-500">{{ user.created_at }}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="edit(user)">Edit</button>
                                <button v-if="user.status === 'active'" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-amber-600 transition hover:border-amber-400 dark:border-[#2a3040] dark:text-amber-300" type="button" @click="postAction('suspend', user)">Suspend</button>
                                <button v-if="user.status !== 'active'" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-emerald-600 transition hover:border-emerald-400 dark:border-[#2a3040] dark:text-emerald-300" type="button" @click="postAction('activate', user)">Activate</button>
                                <button v-if="user.status === 'active'" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400" type="button" @click="postAction('terminate', user)">Terminate</button>
                                <button class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-500 transition hover:border-red-400 hover:text-red-600 dark:border-[#2a3040] dark:text-gray-400 dark:hover:text-red-300" type="button" @click="destroyUser(user)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <div class="inline-flex size-12 items-center justify-center rounded-md bg-gray-100 text-gray-500 dark:bg-[#222738]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m12-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                    </svg>
                </div>
                <p class="mt-4 font-semibold text-gray-700 dark:text-gray-300">No users found</p>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Create a user or adjust your filters to see account records.</p>
                <button class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ New User</button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="users.links" />
            </div>
        </div>

        <DialogModal :show="showingUserModal" max-width="4xl" @close="closeModal">
            <template #title>
                {{ form.id ? 'Edit user' : 'Create user' }}
            </template>

            <template #content>
                <form id="user-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Password</label>
                        <input v-model="form.password" type="password" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :required="!form.id" :placeholder="form.id ? 'Leave blank to keep current password' : ''">
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-400">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                        <select v-model="form.status" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Roles</p>
                        <div class="mt-2 max-h-44 space-y-2 overflow-y-auto rounded-md border border-gray-200 bg-white p-3 dark:border-[#2a3040] dark:bg-[#0c0f16]">
                            <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm">
                                <input v-model="form.role_ids" type="checkbox" :value="role.id" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                {{ role.name }}
                            </label>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Direct permissions</p>
                        <div class="mt-2 max-h-44 space-y-2 overflow-y-auto rounded-md border border-gray-200 bg-white p-3 dark:border-[#2a3040] dark:bg-[#0c0f16]">
                            <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 text-sm">
                                <input v-model="form.permission_ids" type="checkbox" :value="permission.id" class="rounded border-[#2a3040] bg-[#090c11] text-blue-500 focus:ring-blue-500">
                                {{ permission.name }}
                            </label>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="user-form" type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save user' }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingUser)" max-width="md" @close="closeDeleteUserModal">
            <template #title>Delete user</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingUser?.name }}</span>?
                </p>
                <p class="mt-2">This removes the user account and its direct access assignments.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteUserModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteUser">Delete user</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
