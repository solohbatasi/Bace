<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ units: Object, courses: Array, departments: Array, filters: Object });

const showingModal = ref(false);
const deletingUnit = ref(null);
const filter = reactive({ search: props.filters.search || '', course_id: props.filters.course_id || '', department_id: props.filters.department_id || '' });
const form = useForm({ id: null, course_id: '', department_id: '', code: '', name: '', credit_hours: 3, year_level: 1, semester_sequence: 1, is_core: true, is_active: true });

const stats = computed(() => ({
    total: props.units.total,
    active: props.units.data.filter((unit) => unit.is_active).length,
    core: props.units.data.filter((unit) => unit.is_core).length,
    assignments: props.units.data.reduce((sum, unit) => sum + unit.lecturer_assignments_count, 0),
}));

watch(filter, () => router.get(route('academics.units.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.course_id = '';
    form.department_id = '';
    form.code = '';
    form.name = '';
    form.credit_hours = 3;
    form.year_level = 1;
    form.semester_sequence = 1;
    form.is_core = true;
    form.is_active = true;
};
const openCreateModal = () => {
    resetForm();
    showingModal.value = true;
};
const edit = (unit) => {
    resetForm();
    form.id = unit.id;
    form.course_id = unit.course_id;
    form.department_id = unit.department_id;
    form.code = unit.code;
    form.name = unit.name;
    form.credit_hours = unit.credit_hours;
    form.year_level = unit.year_level;
    form.semester_sequence = unit.semester_sequence;
    form.is_core = Boolean(unit.is_core);
    form.is_active = Boolean(unit.is_active);
    showingModal.value = true;
};
const closeModal = () => {
    showingModal.value = false;
    resetForm();
};
const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('academics.units.update', form.id), options) : form.post(route('academics.units.store'), options);
};
const destroyUnit = (unit) => {
    deletingUnit.value = unit;
};
const closeDeleteModal = () => {
    deletingUnit.value = null;
};
const confirmDeleteUnit = () => {
    if (!deletingUnit.value) return;

    router.delete(route('academics.units.destroy', deletingUnit.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};
</script>

<template>
    <AppLayout title="Units">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Units</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Core</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.core }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Assignments</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.assignments }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <button class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                Add Unit
            </button>
            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.course_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All courses</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                </select>
                <select v-model="filter.department_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All departments</option>
                    <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search units...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="units.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Course</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="unit in units.data" :key="unit.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ unit.code }} - {{ unit.name }}</p>
                            <p class="text-xs text-gray-500">{{ unit.credit_hours }} credit hours, {{ unit.lecturer_assignments_count }} lecturer assignments</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ unit.course?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ unit.department?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-500">Y{{ unit.year_level }} S{{ unit.semester_sequence }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="unit.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">- {{ unit.is_active ? 'active' : 'inactive' }}</span>
                                <span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-semibold text-blue-400">{{ unit.is_core ? 'core' : 'elective' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(unit)">Edit</button>
                            <button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyUnit(unit)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No units found</p>
                <p class="mt-1 text-sm text-gray-500">Create a unit or adjust your filters.</p>
                <button class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ Add Unit</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="units.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit unit' : 'Create unit' }}</template>
            <template #content>
                <form id="unit-crud-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                        <select v-model="form.course_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select course</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                        <select v-model="form.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select department</option>
                            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit code</label>
                        <input v-model="form.code" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-400">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Credit hours</label>
                        <input v-model="form.credit_hours" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Year</label>
                            <input v-model="form.year_level" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                            <input v-model="form.semester_sequence" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_core" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Core unit
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Active unit
                    </label>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="unit-crud-form" type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save unit' }}</button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingUnit)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete unit</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingUnit?.code }} - {{ deletingUnit?.name }}</span>?
                </p>
                <p class="mt-2">Assignments or enrollments linked to this unit may prevent deletion.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteUnit">Delete unit</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
