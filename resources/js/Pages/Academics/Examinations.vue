<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    examinations: Object,
    filters: Object,
    scopeTypes: Array,
    departments: Array,
    courses: Array,
    units: Array,
    academicYears: Array,
    semesters: Array,
    permissions: Object,
});

const showingModal = ref(false);
const showingScoreLevelModal = ref(false);
const deletingExamination = ref(null);
const selectedScoreExamination = ref(null);
const filter = reactive({
    search: props.filters.search || '',
    owner_type: props.filters.owner_type || '',
    course_id: props.filters.course_id || '',
    subcourse_id: props.filters.subcourse_id || '',
    unit_id: props.filters.unit_id || '',
    scope_type: props.filters.scope_type || '',
    status: props.filters.status || '',
});
const form = useForm({
    id: null,
    owner_type: 'course',
    department_id: '',
    course_id: '',
    subcourse_id: '',
    unit_id: '',
    academic_year_id: props.academicYears.find((year) => year.is_current)?.id || '',
    semester_id: props.semesters.find((semester) => semester.is_current)?.id || '',
    code: '',
    name: '',
    scope_type: 'permanent',
    starts_on: '',
    ends_on: '',
    max_score: '',
    weight_percent: '',
    is_analysed: false,
    include_in_final_analysis: true,
    can_edit_results: true,
    is_active: true,
    description: '',
});
const scoreLevelForm = useForm({ grading_mode: 'score_levels_with_grades', levels: [] });
const defaultScoreLevels = [
    [0, 50],
    [50, 75],
    [75, 100],
].map(([min, max]) => ({ min_score: min, max_score: max, grade: '', comment: '' }));
const defaultGradeLevels = [{ min_score: null, max_score: null, grade: '', comment: '' }];
const searchableSelects = reactive({
    department: { isOpen: false, search: '' },
    course: { isOpen: false, search: '' },
});
const departmentDropdown = ref(null);
const courseDropdown = ref(null);

onClickOutside(departmentDropdown, () => {
    searchableSelects.department.isOpen = false;
});
onClickOutside(courseDropdown, () => {
    searchableSelects.course.isOpen = false;
});

watch(filter, () => router.get(route('academics.examinations.index'), filter, { preserveState: true, replace: true }), { deep: true });

const coursesForDepartment = (departmentId) => props.courses
    .filter((course) => !course.parent_course_id)
    .filter((course) => !departmentId || Number(course.department_id) === Number(departmentId));
const filteredDepartments = computed(() => {
    const search = searchableSelects.department.search.toLowerCase();
    if (!search) return props.departments;

    return props.departments.filter((department) =>
        department.name.toLowerCase().includes(search) ||
        department.code.toLowerCase().includes(search)
    );
});
const filteredCourses = computed(() => {
    const search = searchableSelects.course.search.toLowerCase();
    const courses = coursesForDepartment(form.department_id);
    if (!search) return courses;

    return courses.filter((course) =>
        course.name.toLowerCase().includes(search) ||
        course.code.toLowerCase().includes(search)
    );
});
const selectedCourse = computed(() => props.courses.find((course) => Number(course.id) === Number(form.course_id)));
const subcoursesForSelectedCourse = computed(() => selectedCourse.value?.subcourses || []);
const selectedSubcourse = computed(() => subcoursesForSelectedCourse.value.find((course) => Number(course.id) === Number(form.subcourse_id)));
const examinationTargetId = computed(() => form.subcourse_id || form.course_id);
const filteredUnits = computed(() => props.units.filter((unit) => !examinationTargetId.value || Number(unit.course_id) === Number(examinationTargetId.value)));
const semesterOptions = computed(() => props.semesters.filter((semester) => !form.academic_year_id || Number(semester.academic_year_id) === Number(form.academic_year_id)));
const stats = computed(() => ({
    total: props.examinations.total,
    active: props.examinations.data.filter((exam) => exam.is_active).length,
    analysed: props.examinations.data.filter((exam) => exam.is_analysed).length,
    final: props.examinations.data.filter((exam) => exam.include_in_final_analysis).length,
}));
const firstError = computed(() => Object.values(form.errors)[0]);
const scoreLevelError = computed(() => Object.values(scoreLevelForm.errors)[0]);
const selectedDepartmentLabel = computed(() => {
    const department = props.departments.find((department) => Number(department.id) === Number(form.department_id));

    return department ? `${department.code} - ${department.name}` : 'Select department';
});
const selectedCourseLabel = computed(() => {
    const course = props.courses.find((course) => Number(course.id) === Number(form.course_id));

    return course ? `${course.code} - ${course.name}` : (form.department_id ? 'Select course' : 'Select department first');
});

const scopeLabel = (scope) => ({
    permanent: 'All time',
    semester: 'Semester',
    period: 'Period',
})[scope] || scope;
const modeUsesGrade = (mode) => ['grade_only', 'score_levels_with_grades'].includes(mode);
const modeUsesRange = (mode) => ['score_levels', 'score_levels_with_grades'].includes(mode);
const modeFromFlags = (usesGrade, usesRange) => {
    if (usesGrade && usesRange) return 'score_levels_with_grades';
    if (usesRange) return 'score_levels';

    return 'grade_only';
};

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.owner_type = 'course';
    form.department_id = '';
    form.course_id = '';
    form.subcourse_id = '';
    form.unit_id = '';
    form.academic_year_id = props.academicYears.find((year) => year.is_current)?.id || '';
    form.semester_id = props.semesters.find((semester) => semester.is_current)?.id || '';
    form.code = '';
    form.name = '';
    form.scope_type = 'permanent';
    form.starts_on = '';
    form.ends_on = '';
    form.max_score = '';
    form.weight_percent = '';
    form.is_analysed = false;
    form.include_in_final_analysis = true;
    form.can_edit_results = true;
    form.is_active = true;
    form.description = '';
    searchableSelects.department.search = '';
    searchableSelects.course.search = '';
    searchableSelects.department.isOpen = false;
    searchableSelects.course.isOpen = false;
};

const openCreateModal = () => {
    resetForm();
    showingModal.value = true;
};

const dateValue = (value) => value ? String(value).slice(0, 10) : '';

const edit = (examination) => {
    resetForm();
    form.id = examination.id;
    form.owner_type = examination.unit_id ? 'unit' : 'course';
    form.course_id = examination.course_id || examination.subcourse?.parent_course_id || examination.unit?.course?.parent_course_id || examination.unit?.course_id || '';
    form.subcourse_id = examination.subcourse_id || '';
    form.department_id = props.courses.find((course) => Number(course.id) === Number(form.course_id))?.department_id || '';
    form.unit_id = examination.unit_id || '';
    form.academic_year_id = examination.academic_year_id || '';
    form.semester_id = examination.semester_id || '';
    form.code = examination.code || '';
    form.name = examination.name;
    form.scope_type = examination.scope_type;
    form.starts_on = dateValue(examination.starts_on);
    form.ends_on = dateValue(examination.ends_on);
    form.max_score = examination.max_score || '';
    form.weight_percent = examination.weight_percent || '';
    form.is_analysed = Boolean(examination.is_analysed);
    form.include_in_final_analysis = Boolean(examination.include_in_final_analysis);
    form.can_edit_results = Boolean(examination.can_edit_results);
    form.is_active = Boolean(examination.is_active);
    form.description = examination.description || '';
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    resetForm();
};

const onOwnerTypeChange = () => {
    form.unit_id = '';
};

const onCourseChange = () => {
    if (!filteredUnits.value.some((unit) => Number(unit.id) === Number(form.unit_id))) {
        form.unit_id = '';
    }
};

const onSubcourseChange = () => {
    onCourseChange();
};

const selectDepartment = (department) => {
    form.department_id = department.id;
    form.course_id = '';
    form.subcourse_id = '';
    form.unit_id = '';
    searchableSelects.department.isOpen = false;
    searchableSelects.department.search = '';
};

const selectCourse = (course) => {
    form.course_id = course.id;
    form.subcourse_id = '';
    if (!form.department_id) {
        form.department_id = course.department_id;
    }
    searchableSelects.course.isOpen = false;
    searchableSelects.course.search = '';
    onCourseChange();
};

const toggleDepartment = () => {
    searchableSelects.department.isOpen = !searchableSelects.department.isOpen;
    if (searchableSelects.department.isOpen) {
        searchableSelects.course.isOpen = false;
        nextTick(() => document.querySelector('#exam-department-search')?.focus());
    }
};

const toggleCourse = () => {
    if (!form.department_id) return;

    searchableSelects.course.isOpen = !searchableSelects.course.isOpen;
    if (searchableSelects.course.isOpen) {
        searchableSelects.department.isOpen = false;
        nextTick(() => document.querySelector('#exam-course-search')?.focus());
    }
};

const onAcademicYearChange = () => {
    if (!semesterOptions.value.some((semester) => Number(semester.id) === Number(form.semester_id))) {
        form.semester_id = semesterOptions.value[0]?.id || '';
    }
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    form.id ? form.put(route('academics.examinations.update', form.id), options) : form.post(route('academics.examinations.store'), options);
};

const scoreLevelRows = (levels, mode = 'score_levels_with_grades') => (levels?.length ? levels : (mode === 'grade_only' ? defaultGradeLevels : defaultScoreLevels)).map((level) => ({
    min_score: level.min_score,
    max_score: level.max_score,
    grade: level.grade || '',
    comment: level.comment || '',
}));

const openScoreLevelModal = (examination) => {
    selectedScoreExamination.value = examination;
    scoreLevelForm.clearErrors();
    scoreLevelForm.grading_mode = examination.grading_mode || 'score_levels_with_grades';
    scoreLevelForm.levels = scoreLevelRows(examination.score_levels, scoreLevelForm.grading_mode);
    showingScoreLevelModal.value = true;
};

const closeScoreLevelModal = () => {
    showingScoreLevelModal.value = false;
    selectedScoreExamination.value = null;
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
    if (!selectedScoreExamination.value) return;

    scoreLevelForm.put(route('academics.examinations.score-levels.update', selectedScoreExamination.value.id), {
        preserveScroll: true,
        onSuccess: closeScoreLevelModal,
    });
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

const destroyExamination = (examination) => {
    deletingExamination.value = examination;
};

const closeDeleteModal = () => {
    deletingExamination.value = null;
};

const confirmDelete = () => {
    if (!deletingExamination.value) return;

    router.delete(route('academics.examinations.destroy', deletingExamination.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};
</script>

<template>
    <AppLayout title="Examinations">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Examinations</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Analysed</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.analysed }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Final Analysis</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.final }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <button v-if="permissions?.canAdd" class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal">
                <span class="text-base leading-none">+</span>
                Add Examination
            </button>
            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.owner_type" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All owners</option>
                    <option value="course">Courses</option>
                    <option value="unit">Units</option>
                </select>
                <select v-model="filter.scope_type" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All scopes</option>
                    <option v-for="scope in scopeTypes" :key="scope" :value="scope">{{ scopeLabel(scope) }}</option>
                </select>
                <select v-model="filter.status" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search exams...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="examinations.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Examination</th>
                        <th class="px-5 py-3">For</th>
                        <th class="px-5 py-3">Availability</th>
                        <th class="px-5 py-3">Analysis</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="exam in examinations.data" :key="exam.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ exam.code ? `${exam.code} - ` : '' }}{{ exam.name }}</p>
                            <p class="text-xs text-gray-500">{{ exam.description || 'No description' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ exam.unit ? `${exam.unit.code} - ${exam.unit.name}` : `${exam.subcourse?.code || exam.course?.code} - ${exam.subcourse?.name || exam.course?.name}` }}</p>
                            <p class="text-xs text-gray-500">{{ exam.unit ? `Unit in ${exam.unit.course?.code || 'course'}` : (exam.subcourse ? `Subcourse under ${exam.course?.code}` : 'Course examination') }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                            <p>{{ scopeLabel(exam.scope_type) }}</p>
                            <p v-if="exam.scope_type === 'semester'" class="text-xs text-gray-500">{{ exam.academic_year?.name }} / {{ exam.semester?.name }}</p>
                            <p v-if="exam.scope_type === 'period'" class="text-xs text-gray-500">{{ exam.starts_on }} to {{ exam.ends_on }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="exam.is_analysed ? 'bg-blue-500/10 text-blue-400' : 'bg-gray-100 text-gray-500 dark:bg-[#1a1f2b]'">
                                    {{ exam.is_analysed ? 'Analysed' : 'Not analysed' }}
                                </span>
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="exam.include_in_final_analysis ? 'bg-amber-500/10 text-amber-500' : 'bg-gray-100 text-gray-500 dark:bg-[#1a1f2b]'">
                                    {{ exam.include_in_final_analysis ? 'Final' : 'Excluded' }}
                                </span>
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="exam.can_edit_results ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-400'">
                                    {{ exam.can_edit_results ? 'Editable' : 'Locked' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="exam.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">
                                {{ exam.is_active ? 'active' : 'inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="permissions?.canManageScoreLevels" class="mr-2 rounded-md border border-violet-500/30 px-2.5 py-1.5 text-xs text-violet-600 transition hover:border-violet-400 dark:text-violet-300" type="button" @click="openScoreLevelModal(exam)">Score Levels</button>
                            <button v-if="permissions?.canEdit || permissions?.canManage" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="edit(exam)">Edit</button>
                            <button v-if="permissions?.canDelete" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400" type="button" @click="destroyExamination(exam)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No examinations found</p>
                <p class="mt-1 text-sm text-gray-500">Create an examination or adjust your filters.</p>
                <button v-if="permissions?.canAdd" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openCreateModal">+ Add Examination</button>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-[#232837]"><Pagination :links="examinations.links" /></div>
        </div>

        <DialogModal :show="showingModal" max-width="4xl" @close="closeModal">
            <template #title>{{ form.id ? 'Edit examination' : 'Create examination' }}</template>
            <template #content>
                <form id="examination-form" class="max-h-[70vh] space-y-4 overflow-y-auto pr-1 text-gray-700 dark:text-gray-300" @submit.prevent="save">
                    <p v-if="firstError" class="rounded-md border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-400">{{ firstError }}</p>

                    <section class="rounded-md border border-gray-200 p-4 dark:border-[#2a3040]">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Owner</h3>
                                <p class="text-xs text-gray-500">Attach this exam to a course or one of its units.</p>
                            </div>
                            <div class="inline-grid grid-cols-2 rounded-md border border-gray-200 bg-gray-50 p-1 text-xs font-semibold dark:border-[#2a3040] dark:bg-[#0c0f16]">
                                <button type="button" class="rounded px-3 py-1.5 transition" :class="form.owner_type === 'course' ? 'bg-white text-violet-700 shadow-sm dark:bg-[#1a1f2b] dark:text-violet-300' : 'text-gray-500'" @click="form.owner_type = 'course'; onOwnerTypeChange()">Course</button>
                                <button type="button" class="rounded px-3 py-1.5 transition" :class="form.owner_type === 'unit' ? 'bg-white text-violet-700 shadow-sm dark:bg-[#1a1f2b] dark:text-violet-300' : 'text-gray-500'" @click="form.owner_type = 'unit'; onOwnerTypeChange()">Unit</button>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div ref="departmentDropdown" class="relative">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                                <button
                                    type="button"
                                    class="mt-1 flex h-10 w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 text-left text-sm text-gray-900 transition focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                    @click="toggleDepartment"
                                >
                                    <span class="truncate">{{ selectedDepartmentLabel }}</span>
                                    <span class="text-xs text-gray-400">v</span>
                                </button>
                                <div v-if="searchableSelects.department.isOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        id="exam-department-search"
                                        v-model="searchableSelects.department.search"
                                        type="text"
                                        class="sticky top-0 w-full border-b border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none dark:border-[#2a3040] dark:bg-[#1a1f2b] dark:text-white"
                                        placeholder="Search department..."
                                        @click.stop
                                    >
                                    <button
                                        v-for="department in filteredDepartments"
                                        :key="department.id"
                                        type="button"
                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#2a3040]"
                                        @click="selectDepartment(department)"
                                    >
                                        <span class="font-medium">{{ department.code }}</span>
                                        <span class="text-gray-500"> - {{ department.name }}</span>
                                    </button>
                                    <div v-if="!filteredDepartments.length" class="px-3 py-2 text-sm text-gray-500">No departments found</div>
                                </div>
                            </div>

                            <div ref="courseDropdown" class="relative">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                                <button
                                    type="button"
                                    class="mt-1 flex h-10 w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 text-left text-sm text-gray-900 transition focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                    :disabled="!form.department_id"
                                    @click="toggleCourse"
                                >
                                    <span class="truncate">{{ selectedCourseLabel }}</span>
                                    <span class="text-xs text-gray-400">v</span>
                                </button>
                                <div v-if="searchableSelects.course.isOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        id="exam-course-search"
                                        v-model="searchableSelects.course.search"
                                        type="text"
                                        class="sticky top-0 w-full border-b border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none dark:border-[#2a3040] dark:bg-[#1a1f2b] dark:text-white"
                                        placeholder="Search course..."
                                        @click.stop
                                    >
                                    <button
                                        v-for="course in filteredCourses"
                                        :key="course.id"
                                        type="button"
                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#2a3040]"
                                        @click="selectCourse(course)"
                                    >
                                        <span class="font-medium">{{ course.code }}</span>
                                        <span class="text-gray-500"> - {{ course.name }}</span>
                                    </button>
                                    <div v-if="!filteredCourses.length" class="px-3 py-2 text-sm text-gray-500">No courses found</div>
                                </div>
                            </div>

                            <div v-if="subcoursesForSelectedCourse.length">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Subcourse</label>
                                <select v-model="form.subcourse_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" @change="onSubcourseChange">
                                    <option value="">General course</option>
                                    <option v-for="subcourse in subcoursesForSelectedCourse" :key="subcourse.id" :value="subcourse.id">{{ subcourse.code }} - {{ subcourse.name }}</option>
                                </select>
                            </div>

                            <div v-if="form.owner_type === 'unit'" class="md:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit</label>
                                <select v-model="form.unit_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                    <option value="">{{ form.course_id ? 'Select unit' : 'Select course first' }}</option>
                                    <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">{{ unit.code }} - {{ unit.name }}</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-md border border-gray-200 p-4 dark:border-[#2a3040]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Details</h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,160px)_1fr]">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Code</label>
                                <input v-model="form.code" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Optional">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                                <input v-model="form.name" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                            <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                        </div>
                    </section>

                    <section class="rounded-md border border-gray-200 p-4 dark:border-[#2a3040]">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Availability</h3>
                                <p class="text-xs text-gray-500">Choose whether the exam is always available or tied to time.</p>
                            </div>
                            <div class="grid grid-cols-3 rounded-md border border-gray-200 bg-gray-50 p-1 text-xs font-semibold dark:border-[#2a3040] dark:bg-[#0c0f16]">
                                <button v-for="scope in scopeTypes" :key="scope" type="button" class="rounded px-2 py-1.5 transition" :class="form.scope_type === scope ? 'bg-white text-violet-700 shadow-sm dark:bg-[#1a1f2b] dark:text-violet-300' : 'text-gray-500'" @click="form.scope_type = scope">{{ scopeLabel(scope) }}</button>
                            </div>
                        </div>

                        <div v-if="form.scope_type === 'semester'" class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic year</label>
                                <select v-model="form.academic_year_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="onAcademicYearChange">
                                    <option value="">Select year</option>
                                    <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                                <select v-model="form.semester_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                    <option value="">Select semester</option>
                                    <option v-for="semester in semesterOptions" :key="semester.id" :value="semester.id">{{ semester.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.scope_type === 'period'" class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Starts on</label>
                                <input v-model="form.starts_on" type="date" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ends on</label>
                                <input v-model="form.ends_on" type="date" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-md border border-gray-200 p-4 dark:border-[#2a3040]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Scoring And Analysis</h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Max score</label>
                                <input v-model="form.max_score" type="number" min="0" step="0.01" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Optional">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Weight %</label>
                                <input v-model="form.weight_percent" type="number" min="0" max="100" step="0.01" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Optional">
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <label class="flex min-h-10 items-center gap-2 rounded-md border border-gray-200 px-3 text-sm dark:border-[#2a3040]">
                                <input v-model="form.is_analysed" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                Analysed
                            </label>
                            <label class="flex min-h-10 items-center gap-2 rounded-md border border-gray-200 px-3 text-sm dark:border-[#2a3040]">
                                <input v-model="form.include_in_final_analysis" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                Final analysis
                            </label>
                            <label class="flex min-h-10 items-center gap-2 rounded-md border border-gray-200 px-3 text-sm dark:border-[#2a3040]">
                                <input v-model="form.can_edit_results" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                Editable results
                            </label>
                            <label class="flex min-h-10 items-center gap-2 rounded-md border border-gray-200 px-3 text-sm dark:border-[#2a3040]">
                                <input v-model="form.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                                Active
                            </label>
                        </div>
                    </section>
                </form>
            </template>
            <template #footer>
                <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button class="rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                    <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="examination-form" type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save examination' }}</button>
                </div>
            </template>
        </DialogModal>

        <DialogModal :show="showingScoreLevelModal" max-width="4xl" @close="closeScoreLevelModal">
            <template #title>Examination Score Levels</template>

            <template #content>
                <form id="examination-score-level-form" class="space-y-3" @submit.prevent="saveScoreLevels">
                    <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-[#151a25]">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedScoreExamination?.code || 'Exam' }} - {{ selectedScoreExamination?.name }}</p>
                        <p class="mt-1 text-xs text-gray-500">Set score ranges, grades, and comments for this examination.</p>
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
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="examination-score-level-form" type="submit" :disabled="scoreLevelForm.processing">
                    {{ scoreLevelForm.processing ? 'Saving...' : 'Save score levels' }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingExamination)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete examination</template>
            <template #content>
                Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingExamination?.name }}</span>?
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDelete">Delete examination</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
