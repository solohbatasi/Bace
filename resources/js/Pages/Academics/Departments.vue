<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ departments: Object, departmentOptions: Array, lecturerOptions: Array, filters: Object });

const showingModal = ref(false);
const deletingDepartment = ref(null);
const filter = reactive({ search: props.filters.search || '' });
const form = useForm({ id: null, parent_department_id: '', head_lecturer_id: '', code: '', name: '', description: '', is_active: true });

const stats = computed(() => ({
    total: props.departments.total,
    active: props.departments.data.filter((department) => department.is_active).length,
    courses: props.departments.data.reduce((sum, department) => sum + department.courses_count, 0),
    lecturers: props.departments.data.reduce((sum, department) => sum + department.lecturers_count, 0),
}));

watch(filter, () => router.get(route('academics.departments.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.parent_department_id = '';
    form.head_lecturer_id = '';
    form.code = '';
    form.name = '';
    form.description = '';
    form.is_active = true;
};
const openCreateModal = () => {
    resetForm();
    showingModal.value = true;
};
const edit = (department) => {
    resetForm();
    form.id = department.id;
    form.parent_department_id = department.parent_department_id || '';
    form.head_lecturer_id = department.head_lecturer_id || '';
    form.code = department.code;
    form.name = department.name;
    form.description = department.description || '';
    form.is_active = Boolean(department.is_active);
    showingModal.value = true;
};
const closeModal = () => {
    showingModal.value = false;
    resetForm();
};
const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('academics.departments.update', form.id), options) : form.post(route('academics.departments.store'), options);
};
const destroyDepartment = (department) => {
    deletingDepartment.value = department;
};
const closeDeleteModal = () => {
    deletingDepartment.value = null;
};
const confirmDeleteDepartment = () => {
    if (!deletingDepartment.value) return;

    router.delete(route('academics.departments.destroy', deletingDepartment.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};
</script>

<template>
    <AppLayout title="Departments">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Departments</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Courses</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.courses }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Lecturers</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.lecturers }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 md:flex-row">
            <button class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                Add Department
            </button>
            <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search code, department...">
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="departments.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Parent</th>
                        <th class="px-5 py-3">Courses</th>
                        <th class="px-5 py-3">Units</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="department in departments.data" :key="department.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ department.code }} - {{ department.name }}</p>
                            <p class="text-xs text-gray-500">{{ department.description || 'No description' }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ department.parent?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ department.courses_count }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ department.units_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="department.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">- {{ department.is_active ? 'active' : 'inactive' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(department)">Edit</button>
                            <button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyDepartment(department)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No departments found</p>
                <p class="mt-1 text-sm text-gray-500">Create a department or adjust your search.</p>
                <button class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ Add Department</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="departments.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit department' : 'Create department' }}</template>
            <template #content>
                <form id="department-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Code</label>
                        <input v-model="form.code" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-400">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Parent department</label>
                        <select v-model="form.parent_department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            <option value="">None</option>
                            <option v-for="department in departmentOptions.filter((department) => department.id !== form.id)" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Head lecturer</label>
                        <select v-model="form.head_lecturer_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            <option value="">None</option>
                            <option v-for="lecturer in lecturerOptions" :key="lecturer.id" :value="lecturer.id">{{ lecturer.title }} {{ lecturer.first_name }} {{ lecturer.last_name }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Active department
                    </label>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="department-form" type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save department' }}</button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingDepartment)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete department</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingDepartment?.code }} - {{ deletingDepartment?.name }}</span>?
                </p>
                <p class="mt-2">Courses, units, and lecturers linked to this department may prevent deletion.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteDepartment">Delete department</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
