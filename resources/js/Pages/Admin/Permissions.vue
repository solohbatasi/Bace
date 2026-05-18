<script setup>
import { reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ permissions: Object, groups: Array, filters: Object });

const showingModal = ref(false);
const filter = reactive({ search: props.filters.search || '', group: props.filters.group || '' });
const form = useForm({ id: null, name: '', group: '', description: '' });

watch(filter, () => router.get(route('admin.permissions.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.name = '';
    form.group = '';
    form.description = '';
};
const openCreateModal = () => {
    resetForm();
    showingModal.value = true;
};
const edit = (permission) => {
    resetForm();
    form.id = permission.id;
    form.name = permission.name;
    form.group = permission.group || '';
    form.description = permission.description || '';
    showingModal.value = true;
};
const closeModal = () => {
    showingModal.value = false;
    resetForm();
};
const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('admin.permissions.update', form.id), options) : form.post(route('admin.permissions.store'), options);
};
const destroyPermission = (permission) => {
    if (confirm(`Delete ${permission.name}?`)) router.delete(route('admin.permissions.destroy', permission.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Permissions">
        <template #actions>
            <button class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                New Permission
            </button>
        </template>

        <div class="mb-4 flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <p class="text-sm text-gray-500">Define precise capabilities used by roles and direct user overrides.</p>
            <div class="flex gap-2">
                <select v-model="filter.group" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All groups</option>
                    <option v-for="group in groups" :key="group" :value="group">{{ group }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search permissions...">
            </div>
        </div>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="permissions.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Group</th><th class="px-5 py-3">Usage</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="permission in permissions.data" :key="permission.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ permission.name }}</p><p class="text-xs text-gray-500">{{ permission.description || 'No description' }}</p></td>
                        <td class="px-5 py-4"><span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-semibold text-blue-300">{{ permission.group || 'General' }}</span></td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-400">{{ permission.roles_count }} roles, {{ permission.users_count }} users</td>
                        <td class="px-5 py-4 text-right">
                            <button class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(permission)">Edit</button>
                            <button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyPermission(permission)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No permissions yet</p>
                <p class="mt-1 text-sm text-gray-500">Create permissions to enforce access cleanly.</p>
                <button class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ New Permission</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="permissions.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit permission' : 'Create permission' }}</template>
            <template #content>
                <form id="permission-form" class="space-y-4" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="users.update" required>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Group</label>
                        <input v-model="form.group" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="users">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400 disabled:opacity-50" form="permission-form" type="submit" :disabled="form.processing">Save permission</button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
