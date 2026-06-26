<script setup>
import { reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ roles: Object, permissions: Array, filters: Object, permissionsMeta: Object });

const showingModal = ref(false);
const filter = reactive({ search: props.filters.search || '' });
const form = useForm({ id: null, name: '', description: '', permission_ids: [] });

watch(filter, () => router.get(route('admin.roles.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.name = '';
    form.description = '';
    form.permission_ids = [];
};
const openCreateModal = () => {
    if (!props.permissionsMeta?.canAdd) return;

    resetForm();
    showingModal.value = true;
};
const edit = (role) => {
    if (!props.permissionsMeta?.canEdit) return;

    resetForm();
    form.id = role.id;
    form.name = role.name;
    form.description = role.description || '';
    form.permission_ids = role.permissions.map((permission) => permission.id);
    showingModal.value = true;
};
const closeModal = () => {
    showingModal.value = false;
    resetForm();
};
const save = () => {
    if (form.id && !props.permissionsMeta?.canEdit) return;
    if (!form.id && !props.permissionsMeta?.canAdd) return;

    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('admin.roles.update', form.id), options) : form.post(route('admin.roles.store'), options);
};
const destroyRole = (role) => {
    if (confirm(`Delete ${role.name}?`)) router.delete(route('admin.roles.destroy', role.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Roles">
        <template #actions>
            <button v-if="permissionsMeta?.canAdd" class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                New Role
            </button>
        </template>

        <div class="mb-4 flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <p class="text-sm text-gray-500">Bundle permissions into clean operational access profiles.</p>
            </div>
            <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search roles...">
        </div>

        <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="roles.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr><th class="px-5 py-3">Role</th><th class="px-5 py-3">Permissions</th><th class="px-5 py-3">Users</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="role in roles.data" :key="role.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4"><p class="font-semibold text-gray-900 dark:text-white">{{ role.name }}</p><p class="text-xs text-gray-500">{{ role.description || 'No description' }}</p></td>
                        <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-400">{{ role.permissions.map((permission) => permission.name).join(', ') || 'No permissions' }}</td>
                        <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ role.users_count }}</td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="permissionsMeta?.canEdit" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(role)">Edit</button>
                            <button v-if="permissionsMeta?.canDelete" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyRole(role)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No roles yet</p>
                <p class="mt-1 text-sm text-gray-500">Create your first role and attach permissions.</p>
                <button v-if="permissionsMeta?.canAdd" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ New Role</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="roles.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit role' : 'Create role' }}</template>
            <template #content>
                <form id="role-form" class="space-y-4" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                        <textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Permissions</p>
                        <div class="mt-2 max-h-56 space-y-2 overflow-y-auto rounded-md border border-gray-200 bg-white p-3 dark:border-[#2a3040] dark:bg-[#0c0f16]">
                            <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="form.permission_ids" type="checkbox" :value="permission.id" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                {{ permission.name }}
                                <span class="text-xs text-gray-600">{{ permission.group }}</span>
                            </label>
                        </div>
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button v-if="form.id ? permissionsMeta?.canEdit : permissionsMeta?.canAdd" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400 disabled:opacity-50" form="role-form" type="submit" :disabled="form.processing">Save role</button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
