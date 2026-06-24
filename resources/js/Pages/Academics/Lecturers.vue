<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ lecturers: Object, departments: Array, filters: Object });

const showingModal = ref(false);
const deletingLecturer = ref(null);
const filter = reactive({ search: props.filters.search || '', department_id: props.filters.department_id || '', status: props.filters.status || '' });
const form = useForm({ id: null, department_id: '', staff_number: '', title: '', first_name: '', middle_name: '', last_name: '', email: '', phone: '', hired_on: '', employment_status: 'active' });

const stats = computed(() => ({
    total: props.lecturers.total,
    active: props.lecturers.data.filter((lecturer) => lecturer.employment_status === 'active').length,
    inactive: props.lecturers.data.filter((lecturer) => lecturer.employment_status !== 'active').length,
    assignments: props.lecturers.data.reduce((sum, lecturer) => sum + lecturer.unit_assignments_count, 0),
}));

watch(filter, () => router.get(route('academics.lecturers.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.department_id = '';
    form.staff_number = '';
    form.title = '';
    form.first_name = '';
    form.middle_name = '';
    form.last_name = '';
    form.email = '';
    form.phone = '';
    form.hired_on = '';
    form.employment_status = 'active';
};
const openCreateModal = () => {
    resetForm();
    showingModal.value = true;
};
const edit = (lecturer) => {
    resetForm();
    form.id = lecturer.id;
    form.department_id = lecturer.department_id;
    form.staff_number = lecturer.staff_number;
    form.title = lecturer.title || '';
    form.first_name = lecturer.first_name;
    form.middle_name = lecturer.middle_name || '';
    form.last_name = lecturer.last_name;
    form.email = lecturer.email || '';
    form.phone = lecturer.phone || '';
    form.hired_on = lecturer.hired_on || '';
    form.employment_status = lecturer.employment_status || 'active';
    showingModal.value = true;
};
const closeModal = () => {
    showingModal.value = false;
    resetForm();
};
const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('academics.lecturers.update', form.id), options) : form.post(route('academics.lecturers.store'), options);
};
const destroyLecturer = (lecturer) => {
    deletingLecturer.value = lecturer;
};
const closeDeleteModal = () => {
    deletingLecturer.value = null;
};
const confirmDeleteLecturer = () => {
    if (!deletingLecturer.value) return;

    router.delete(route('academics.lecturers.destroy', deletingLecturer.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};
</script>

<template>
    <AppLayout title="Lecturers">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Lecturers</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Other Status</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.inactive }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Assignments</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.assignments }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <button class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                Add Lecturer
            </button>
            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.status" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                    <option value="terminated">Terminated</option>
                </select>
                <select v-model="filter.department_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All departments</option>
                    <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search lecturer...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="lecturers.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Lecturer</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Assignments</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="lecturer in lecturers.data" :key="lecturer.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ lecturer.title }} {{ lecturer.first_name }} {{ lecturer.last_name }}</p>
                            <p class="text-xs text-gray-500">{{ lecturer.staff_number }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ lecturer.department?.name || '-' }}</td>
                        <td class="px-5 py-4 text-xs text-gray-500">
                            <p>{{ lecturer.email || '-' }}</p>
                            <p>{{ lecturer.phone || '-' }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ lecturer.unit_assignments_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize" :class="lecturer.employment_status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'">- {{ lecturer.employment_status }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(lecturer)">Edit</button>
                            <button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyLecturer(lecturer)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No lecturers found</p>
                <p class="mt-1 text-sm text-gray-500">Create a lecturer or adjust your filters.</p>
                <button class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ Add Lecturer</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="lecturers.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit lecturer' : 'Create lecturer' }}</template>
            <template #content>
                <form id="lecturer-crud-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                        <select v-model="form.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select department</option>
                            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Staff number</label>
                        <input v-model="form.staff_number" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.staff_number" class="mt-1 text-xs text-red-400">{{ form.errors.staff_number }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Title</label>
                        <input v-model="form.title" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">First name</label>
                        <input v-model="form.first_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Middle name</label>
                        <input v-model="form.middle_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Last name</label>
                        <input v-model="form.last_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Phone</label>
                        <input v-model="form.phone" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hired on</label>
                        <input v-model="form.hired_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                        <select v-model="form.employment_status" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="lecturer-crud-form" type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save lecturer' }}</button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingLecturer)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete lecturer</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingLecturer?.title }} {{ deletingLecturer?.first_name }} {{ deletingLecturer?.last_name }}</span>?
                </p>
                <p class="mt-2">Unit assignments or class records linked to this lecturer may prevent deletion.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteLecturer">Delete lecturer</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
