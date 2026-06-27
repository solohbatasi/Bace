<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ units: Object, courses: Array, departments: Array, filters: Object, permissions: Object });

const showingModal = ref(false);
const deletingUnit = ref(null);
const courseSelectOpen = ref(false);
const courseSearch = ref('');
const showingScoreLevelModal = ref(false);
const selectedScoreUnit = ref(null);
const filter = reactive({ search: props.filters.search || '', course_id: props.filters.course_id || '', department_id: props.filters.department_id || '' });
const form = useForm({ id: null, course_id: '', department_id: '', code: '', name: '', duration: '', grading_mode: 'score_levels_with_grades', credit_hours: 3, year_level: 1, semester_sequence: 1, is_core: true, is_active: true });
const scoreLevelForm = useForm({ grading_mode: 'score_levels_with_grades', levels: [] });
const defaultScoreLevels = [
    [0, 50],
    [50, 75],
    [75, 100],
].map(([min, max]) => ({ min_score: min, max_score: max, grade: '', comment: '' }));
const defaultGradeLevels = [{ min_score: null, max_score: null, grade: '', comment: '' }];
const modeUsesGrade = (mode) => ['grade_only', 'score_levels_with_grades'].includes(mode);
const modeUsesRange = (mode) => ['score_levels', 'score_levels_with_grades'].includes(mode);
const modeFromFlags = (usesGrade, usesRange) => {
    if (usesGrade && usesRange) return 'score_levels_with_grades';
    if (usesRange) return 'score_levels';
    return 'grade_only';
};

const formCourses = computed(() => {
    const search = courseSearch.value.toLowerCase();

    return props.courses
        .filter((course) => !form.department_id || Number(course.department_id) === Number(form.department_id))
        .filter((course) => !search || [course.code, course.name].some((value) => String(value || '').toLowerCase().includes(search)));
});
const selectedCourseLabel = computed(() => {
    const course = props.courses.find((course) => Number(course.id) === Number(form.course_id));

    return course ? `${course.code} - ${course.name}` : 'Select course';
});

const stats = computed(() => ({
    total: props.units.total,
    active: props.units.data.filter((unit) => unit.is_active).length,
    core: props.units.data.filter((unit) => unit.is_core).length,
    assignments: props.units.data.reduce((sum, unit) => sum + unit.lecturer_assignments_count, 0),
}));
const scoreLevelError = computed(() => Object.values(scoreLevelForm.errors)[0]);

watch(filter, () => router.get(route('academics.units.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.course_id = '';
    form.department_id = '';
    form.code = '';
    form.name = '';
    form.duration = '';
    form.grading_mode = 'score_levels_with_grades';
    form.credit_hours = 3;
    form.year_level = 1;
    form.semester_sequence = 1;
    form.is_core = true;
    form.is_active = true;
    courseSearch.value = '';
    courseSelectOpen.value = false;
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
    form.duration = unit.duration || '';
    form.grading_mode = unit.grading_mode || 'score_levels_with_grades';
    form.credit_hours = unit.credit_hours;
    form.year_level = unit.year_level;
    form.semester_sequence = unit.semester_sequence;
    form.is_core = Boolean(unit.is_core);
    form.is_active = Boolean(unit.is_active);
    showingModal.value = true;
};
const updateDepartment = () => {
    form.course_id = '';
    courseSearch.value = '';
    courseSelectOpen.value = false;
};
const toggleCourseSelect = () => {
    if (!form.department_id) return;

    courseSelectOpen.value = !courseSelectOpen.value;
    if (courseSelectOpen.value) {
        courseSearch.value = '';
    }
};
const selectCourse = (course) => {
    form.course_id = course.id;
    courseSelectOpen.value = false;
    courseSearch.value = '';
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
const scoreLevelRows = (levels, mode = 'score_levels_with_grades') => (levels?.length ? levels : (mode === 'grade_only' ? defaultGradeLevels : defaultScoreLevels)).map((level) => ({
    min_score: level.min_score,
    max_score: level.max_score,
    grade: level.grade || '',
    comment: level.comment || '',
}));
const openScoreLevelModal = (unit) => {
    selectedScoreUnit.value = unit;
    scoreLevelForm.clearErrors();
    scoreLevelForm.grading_mode = unit.grading_mode || 'score_levels_with_grades';
    scoreLevelForm.levels = scoreLevelRows(unit.score_levels, scoreLevelForm.grading_mode);
    showingScoreLevelModal.value = true;
};
const closeScoreLevelModal = () => {
    showingScoreLevelModal.value = false;
    selectedScoreUnit.value = null;
    scoreLevelForm.clearErrors();
    scoreLevelForm.grading_mode = 'score_levels_with_grades';
    scoreLevelForm.levels = [];
};
const addScoreLevel = () => {
    scoreLevelForm.levels.push(modeUsesRange(scoreLevelForm.grading_mode)
        ? { min_score: '', max_score: '', grade: '', comment: '' }
        : { min_score: null, max_score: null, grade: '', comment: '' });
};
const removeScoreLevel = (index) => {
    scoreLevelForm.levels.splice(index, 1);
};
const saveScoreLevels = () => {
    if (!selectedScoreUnit.value) return;

    scoreLevelForm.put(route('academics.units.score-levels.update', selectedScoreUnit.value.id), {
        preserveScroll: true,
        onSuccess: closeScoreLevelModal,
    });
};
const setUnitGradingFlag = (flag, checked) => {
    form.grading_mode = modeFromFlags(
        flag === 'grade' ? checked : modeUsesGrade(form.grading_mode),
        flag === 'range' ? checked : modeUsesRange(form.grading_mode),
    );
};
const setScoreLevelGradingFlag = (flag, checked) => {
    const nextMode = modeFromFlags(
        flag === 'grade' ? checked : modeUsesGrade(scoreLevelForm.grading_mode),
        flag === 'range' ? checked : modeUsesRange(scoreLevelForm.grading_mode),
    );

    scoreLevelForm.grading_mode = nextMode;
    if (!scoreLevelForm.levels.length) {
        scoreLevelForm.levels = scoreLevelRows([], nextMode);
    } else if (modeUsesRange(nextMode) && scoreLevelForm.levels.every((level) => level.min_score === null || level.min_score === '')) {
        scoreLevelForm.levels = scoreLevelRows([], nextMode);
    }
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
            <button v-if="permissions?.canAdd" class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
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
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                            <p>{{ unit.course?.name || '-' }}</p>
                            <p v-if="unit.course?.parent_course" class="text-xs text-gray-500">Under {{ unit.course.parent_course.code }} - {{ unit.course.parent_course.name }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ unit.department?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-500">Y{{ unit.year_level }} S{{ unit.semester_sequence }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="unit.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">- {{ unit.is_active ? 'active' : 'inactive' }}</span>
                                <span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-semibold text-blue-400">{{ unit.is_core ? 'core' : 'elective' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="permissions?.canManageScoreLevels" class="mr-2 rounded-md border border-violet-500/30 px-2.5 py-1.5 text-xs text-violet-600 hover:border-violet-400 dark:text-violet-300" @click="openScoreLevelModal(unit)">Score Levels</button>
                            <button v-if="permissions?.canEdit" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" @click="edit(unit)">Edit</button>
                            <button v-if="permissions?.canDelete" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" @click="destroyUnit(unit)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No units found</p>
                <p class="mt-1 text-sm text-gray-500">Create a unit or adjust your filters.</p>
                <button v-if="permissions?.canAdd" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" @click="openCreateModal">+ Add Unit</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="units.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit unit' : 'Create unit' }}</template>
            <template #content>
                <form id="unit-crud-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                        <select v-model="form.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="updateDepartment">
                            <option value="">Select department</option>
                            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                        <div class="relative mt-1">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-900 disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                :disabled="!form.department_id"
                                @click="toggleCourseSelect"
                            >
                                <span class="truncate">{{ form.department_id ? selectedCourseLabel : 'Select department first' }}</span>
                                <span class="text-xs text-gray-400">▼</span>
                            </button>
                            <div v-if="courseSelectOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                <div class="sticky top-0 border-b border-gray-200 bg-white p-2 dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        v-model="courseSearch"
                                        class="w-full rounded-md border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                        placeholder="Search code or course"
                                    >
                                </div>
                                <button
                                    v-for="course in formCourses"
                                    :key="course.id"
                                    type="button"
                                    class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#252b3a]"
                                    :class="Number(form.course_id) === Number(course.id) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300'"
                                    @click="selectCourse(course)"
                                >
                                    <span class="block font-semibold">{{ course.code }}</span>
                                    <span class="text-xs text-gray-500">{{ course.name }}</span>
                                    <span v-if="course.parent_course_id" class="block text-[11px] text-violet-500">Subcourse</span>
                                </button>
                                <div v-if="!formCourses.length" class="px-3 py-2 text-sm text-gray-500">
                                    No unit-based courses found
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.course_id" class="mt-1 text-xs text-red-400">{{ form.errors.course_id }}</p>
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
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit duration</label>
                        <input v-model="form.duration" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Optional, e.g. 6 weeks">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grading</label>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesGrade(form.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setUnitGradingFlag('grade', $event.target.checked)">
                                Grade
                            </label>
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesRange(form.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setUnitGradingFlag('range', $event.target.checked)">
                                Range
                            </label>
                        </div>
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

        <DialogModal :show="showingScoreLevelModal" max-width="4xl" @close="closeScoreLevelModal">
            <template #title>Unit Score Levels</template>
            <template #content>
                <form id="unit-score-level-form" class="space-y-3" @submit.prevent="saveScoreLevels">
                    <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-[#151a25]">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedScoreUnit?.code }} - {{ selectedScoreUnit?.name }}</p>
                        <p class="mt-1 text-xs text-gray-500">Set score ranges, grades, and comments for this unit.</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Use</label>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesGrade(scoreLevelForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setScoreLevelGradingFlag('grade', $event.target.checked)">
                                Grade
                            </label>
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesRange(scoreLevelForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setScoreLevelGradingFlag('range', $event.target.checked)">
                                Range
                            </label>
                        </div>
                    </div>

                    <p v-if="scoreLevelError" class="text-xs text-red-400">{{ scoreLevelError }}</p>

                    <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-[#2a3040]">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                            <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                                <tr>
                                    <th v-if="modeUsesRange(scoreLevelForm.grading_mode)" class="px-3 py-2">From</th>
                                    <th v-if="modeUsesRange(scoreLevelForm.grading_mode)" class="px-3 py-2">To</th>
                                    <th v-if="modeUsesGrade(scoreLevelForm.grading_mode)" class="px-3 py-2">Grade</th>
                                    <th class="px-3 py-2">Comment</th>
                                    <th class="px-3 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                                <tr v-for="(level, index) in scoreLevelForm.levels" :key="index">
                                    <td v-if="modeUsesRange(scoreLevelForm.grading_mode)" class="px-3 py-2"><input v-model="level.min_score" type="number" min="0" max="100" step="0.01" class="w-24 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" required></td>
                                    <td v-if="modeUsesRange(scoreLevelForm.grading_mode)" class="px-3 py-2"><input v-model="level.max_score" type="number" min="0" max="100" step="0.01" class="w-24 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" required></td>
                                    <td v-if="modeUsesGrade(scoreLevelForm.grading_mode)" class="px-3 py-2"><input v-model="level.grade" class="w-28 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="A, B, Pass" required></td>
                                    <td class="px-3 py-2"><input v-model="level.comment" class="w-full min-w-48 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Excellent, Pass, Retry"></td>
                                    <td class="px-3 py-2 text-right"><button class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-400" type="button" @click="removeScoreLevel(index)">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-medium text-violet-600 hover:border-violet-400 dark:border-[#2a3040] dark:text-violet-300" type="button" @click="addScoreLevel">
                        + Add range
                    </button>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeScoreLevelModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="unit-score-level-form" type="submit" :disabled="scoreLevelForm.processing">
                    {{ scoreLevelForm.processing ? 'Saving...' : 'Save score levels' }}
                </button>
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
