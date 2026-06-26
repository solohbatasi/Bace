<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    courses: Object,
    filters: Object,
    departments: Array,
    unitCourses: Array,
    lecturers: Array,
    classes: Array,
    semesters: Array,
    academicYears: Array,
    permissions: Object,
});

const filter = reactive({ search: props.filters.search || '', department_id: props.filters.department_id || '' });
const courseForm = useForm({ id: null, department_id: '', code: '', name: '', qualification_level: '', duration_type: 'semesters', duration_semesters: 1, duration: '', fees: 0, has_units: true, grading_mode: 'score_levels_with_grades', description: '', is_active: true });
const unitForm = useForm({ course_id: '', department_id: '', code: '', name: '', duration: '', grading_mode: 'score_levels_with_grades', credit_hours: 3, year_level: 1, semester_sequence: 1, is_core: true, is_active: true });
const assignmentForm = useForm({ unit_id: '', lecturer_id: '', class_id: '', semester_id: '', academic_year_id: '', is_primary: true });
const scoreLevelForm = useForm({ grading_mode: 'score_levels_with_grades', levels: [] });
const showingCourseModal = ref(false);
const showingUnitModal = ref(false);
const showingLecturerModal = ref(false);
const showingScoreLevelModal = ref(false);
const deletingCourse = ref(null);
const selectedScoreCourse = ref(null);
const unitCourseSearch = ref('');
const unitCourseSelectOpen = ref(false);
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

watch(filter, () => router.get(route('academics.courses.index'), filter, { preserveState: true, replace: true }), { deep: true });

const allUnits = computed(() => props.courses.data.flatMap((course) => course.units || []));
const unitEligibleCourses = computed(() => {
    const search = unitCourseSearch.value.toLowerCase();

    return props.unitCourses
        .filter((course) => !unitForm.department_id || Number(course.department_id) === Number(unitForm.department_id))
        .filter((course) => !search || [course.code, course.name].some((value) => String(value || '').toLowerCase().includes(search)));
});
const selectedUnitCourseLabel = computed(() => {
    const course = props.unitCourses.find((course) => Number(course.id) === Number(unitForm.course_id));

    return course ? `${course.code} - ${course.name}` : 'Select course';
});
const stats = computed(() => ({
    total: props.courses.total,
    active: props.courses.data.filter((course) => course.is_active).length,
    units: props.courses.data.reduce((sum, course) => sum + (course.units_count ?? course.units?.length ?? 0), 0),
    lecturers: props.lecturers.length,
}));
const scoreLevelError = computed(() => Object.values(scoreLevelForm.errors)[0]);

const resetCourseForm = () => {
    courseForm.clearErrors();
    courseForm.id = null;
    courseForm.department_id = '';
    courseForm.code = '';
    courseForm.name = '';
    courseForm.qualification_level = '';
    courseForm.duration_type = 'semesters';
    courseForm.duration_semesters = 1;
    courseForm.duration = '';
    courseForm.fees = 0;
    courseForm.has_units = true;
    courseForm.grading_mode = 'score_levels_with_grades';
    courseForm.description = '';
    courseForm.is_active = true;
};

const resetUnitForm = () => {
    unitForm.clearErrors();
    unitForm.reset();
    unitForm.credit_hours = 3;
    unitForm.duration = '';
    unitForm.grading_mode = 'score_levels_with_grades';
    unitForm.year_level = 1;
    unitForm.semester_sequence = 1;
    unitForm.is_core = true;
    unitForm.is_active = true;
    unitCourseSearch.value = '';
    unitCourseSelectOpen.value = false;
};

const resetAssignmentForm = () => {
    assignmentForm.clearErrors();
    assignmentForm.reset();
    assignmentForm.is_primary = true;
};

const openCourseModal = () => {
    resetCourseForm();
    showingCourseModal.value = true;
};

const editCourse = (course) => {
    resetCourseForm();
    courseForm.id = course.id;
    courseForm.department_id = course.department_id;
    courseForm.code = course.code;
    courseForm.name = course.name;
    courseForm.qualification_level = course.qualification_level || '';
    courseForm.duration_type = course.duration_type || (course.duration ? 'custom' : 'semesters');
    courseForm.duration_semesters = course.duration_semesters || 1;
    courseForm.duration = course.duration || '';
    courseForm.fees = course.fees ?? 0;
    courseForm.has_units = Boolean(course.has_units);
    courseForm.grading_mode = course.grading_mode || 'score_levels_with_grades';
    courseForm.description = course.description || '';
    courseForm.is_active = Boolean(course.is_active);
    showingCourseModal.value = true;
};

const closeCourseModal = () => {
    showingCourseModal.value = false;
    resetCourseForm();
};

const openUnitModal = () => {
    resetUnitForm();
    showingUnitModal.value = true;
};

const updateUnitDepartment = () => {
    unitForm.course_id = '';
    unitCourseSearch.value = '';
    unitCourseSelectOpen.value = false;
};

const toggleUnitCourseSelect = () => {
    if (!unitForm.department_id) return;

    unitCourseSelectOpen.value = !unitCourseSelectOpen.value;
    if (unitCourseSelectOpen.value) {
        unitCourseSearch.value = '';
    }
};

const selectUnitCourse = (course) => {
    unitForm.course_id = course.id;
    unitCourseSelectOpen.value = false;
    unitCourseSearch.value = '';
};

const openLecturerModal = () => {
    resetAssignmentForm();
    showingLecturerModal.value = true;
};

const scoreLevelRows = (levels, mode = 'score_levels_with_grades') => (levels?.length ? levels : (mode === 'grade_only' ? defaultGradeLevels : defaultScoreLevels)).map((level) => ({
    min_score: level.min_score,
    max_score: level.max_score,
    grade: level.grade || '',
    comment: level.comment || '',
}));

const openScoreLevelModal = (course) => {
    selectedScoreCourse.value = course;
    scoreLevelForm.clearErrors();
    scoreLevelForm.grading_mode = course.grading_mode || 'score_levels_with_grades';
    scoreLevelForm.levels = scoreLevelRows(course.score_levels, scoreLevelForm.grading_mode);
    showingScoreLevelModal.value = true;
};

const closeScoreLevelModal = () => {
    showingScoreLevelModal.value = false;
    selectedScoreCourse.value = null;
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
    if (!selectedScoreCourse.value) return;

    scoreLevelForm.put(route('academics.courses.score-levels.update', selectedScoreCourse.value.id), {
        preserveScroll: true,
        onSuccess: closeScoreLevelModal,
    });
};

const setCourseGradingFlag = (flag, checked) => {
    courseForm.grading_mode = modeFromFlags(
        flag === 'grade' ? checked : modeUsesGrade(courseForm.grading_mode),
        flag === 'range' ? checked : modeUsesRange(courseForm.grading_mode),
    );
};

const setUnitGradingFlag = (flag, checked) => {
    unitForm.grading_mode = modeFromFlags(
        flag === 'grade' ? checked : modeUsesGrade(unitForm.grading_mode),
        flag === 'range' ? checked : modeUsesRange(unitForm.grading_mode),
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

const saveCourse = () => {
    const options = { preserveScroll: true, onSuccess: closeCourseModal };
    courseForm.id ? courseForm.put(route('academics.courses.update', courseForm.id), options) : courseForm.post(route('academics.courses.store'), options);
};

const destroyCourse = (course) => {
    deletingCourse.value = course;
};

const closeDeleteCourseModal = () => {
    deletingCourse.value = null;
};

const confirmDeleteCourse = () => {
    if (!deletingCourse.value) return;

    router.delete(route('academics.courses.destroy', deletingCourse.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteCourseModal,
    });
};

const saveUnit = () => unitForm.post(route('academics.units.store'), {
    preserveScroll: true,
    onSuccess: () => {
        resetUnitForm();
        showingUnitModal.value = false;
    },
});

const assignLecturer = () => assignmentForm.post(route('academics.units.lecturers.store', assignmentForm.unit_id), {
    preserveScroll: true,
    onSuccess: () => {
        resetAssignmentForm();
        showingLecturerModal.value = false;
    },
});

const exportCsv = () => {
    const rows = [
        ['Course Code', 'Course Name', 'Department', 'Qualification', 'Duration Semesters', 'Fees', 'Units'],
        ...props.courses.data.map((course) => [
            course.code,
            course.name,
            course.department?.name || '',
            course.qualification_level || '',
            course.duration_semesters,
            course.fees,
            (course.units || []).map((unit) => unit.code).join('; '),
        ]),
    ];

    const csv = rows.map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'courses.csv';
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <AppLayout title="Course Management">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Courses</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Units Listed</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.units }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Lecturers</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.lecturers }}</p>
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
                <button v-if="permissions?.canAddCourse" class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCourseModal">
                    <span class="text-base leading-none">+</span>
                    Add Course
                </button>
                <button v-if="permissions?.canAddUnit" class="inline-flex h-8 items-center gap-2 rounded-md border border-blue-500/40 px-3 text-xs font-semibold text-blue-600 transition hover:border-blue-500 hover:bg-blue-500/10 dark:text-blue-300" type="button" @click="openUnitModal">
                    <span class="text-base leading-none">+</span>
                    Add Unit
                </button>
                <button v-if="permissions?.canAssignLecturer" class="inline-flex h-8 items-center gap-2 rounded-md border border-emerald-500/40 px-3 text-xs font-semibold text-emerald-600 transition hover:border-emerald-500 hover:bg-emerald-500/10 dark:text-emerald-300" type="button" @click="openLecturerModal">
                    <span class="text-base leading-none">+</span>
                    Add Lecturer
                </button>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.department_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All departments</option>
                    <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search code, course...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="courses.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Course</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Duration</th>
                        <th class="px-5 py-3">Fees</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Units</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="course in courses.data" :key="course.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ course.code }} - {{ course.name }}</p>
                            <p class="text-xs text-gray-500">{{ course.qualification_level || 'No qualification level' }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ course.department?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-500">
                            {{ course.duration_type === 'custom' ? (course.duration || '-') : `${course.duration_semesters} semesters` }}
                        </td>
                        <td class="px-5 py-4 font-semibold text-emerald-500">KES {{ Number(course.fees).toLocaleString() }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="course.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">
                                - {{ course.is_active ? 'active' : 'inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex max-w-xl flex-wrap gap-2">
                                <span v-if="!course.has_units" class="rounded-md bg-gray-100 px-2.5 py-1.5 text-xs text-gray-500 dark:bg-[#1a1f2b]">Does not use units</span>
                                <span v-for="unit in course.units" v-else :key="unit.id" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 dark:border-[#2a3040] dark:text-gray-300">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ unit.code }}</span>
                                    {{ unit.name }}
                                    <span v-if="unit.duration" class="text-gray-400">({{ unit.duration }})</span>
                                </span>
                                <span v-if="course.has_units && !course.units.length" class="text-xs text-gray-500">No units</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="permissions?.canManageCourseScoreLevels" class="mr-2 rounded-md border border-violet-500/30 px-2.5 py-1.5 text-xs text-violet-600 transition hover:border-violet-400 dark:text-violet-300" type="button" @click="openScoreLevelModal(course)">Score Levels</button>
                            <button v-if="permissions?.canEditCourse" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editCourse(course)">Edit</button>
                            <button v-if="permissions?.canDeleteCourse" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400" type="button" @click="destroyCourse(course)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <div class="inline-flex size-12 items-center justify-center rounded-md bg-gray-100 text-gray-500 dark:bg-[#222738]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21V5.5Zm0 0A2.5 2.5 0 0 1 7.5 8H20" />
                    </svg>
                </div>
                <p class="mt-4 font-semibold text-gray-700 dark:text-gray-300">No courses found</p>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Create a course or adjust the filters to see academic records.</p>
                <button v-if="permissions?.canAddCourse" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openCourseModal">+ Add Course</button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="courses.links" />
            </div>
        </div>

        <DialogModal :show="showingCourseModal" max-width="2xl" @close="closeCourseModal">
            <template #title>{{ courseForm.id ? 'Edit course' : 'Add course' }}</template>

            <template #content>
                <form id="course-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="saveCourse">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                        <select v-model="courseForm.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select department</option>
                            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                        </select>
                        <p v-if="courseForm.errors.department_id" class="mt-1 text-xs text-red-400">{{ courseForm.errors.department_id }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Code</label>
                        <input v-model="courseForm.code" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="courseForm.errors.code" class="mt-1 text-xs text-red-400">{{ courseForm.errors.code }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course name</label>
                        <input v-model="courseForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="courseForm.errors.name" class="mt-1 text-xs text-red-400">{{ courseForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Qualification</label>
                        <input v-model="courseForm.qualification_level" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Duration tracking</label>
                        <select v-model="courseForm.duration_type" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="semesters">Semester duration</option>
                            <option value="custom">Custom duration</option>
                        </select>
                    </div>
                    <div v-if="courseForm.duration_type === 'semesters'">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semesters</label>
                        <input v-model="courseForm.duration_semesters" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div v-else>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course duration</label>
                        <input v-model="courseForm.duration" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="e.g. 3 months, 10 weeks" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fees</label>
                        <input v-model="courseForm.fees" type="number" min="0" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <label class="flex items-center gap-2 pt-6 text-sm">
                        <input v-model="courseForm.has_units" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Course has units
                    </label>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grading</label>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesGrade(courseForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setCourseGradingFlag('grade', $event.target.checked)">
                                Grade
                            </label>
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesRange(courseForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500" @change="setCourseGradingFlag('range', $event.target.checked)">
                                Range
                            </label>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 pt-6 text-sm">
                        <input v-model="courseForm.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Active course
                    </label>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                        <textarea v-model="courseForm.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeCourseModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="course-form" type="submit" :disabled="courseForm.processing">
                    {{ courseForm.processing ? 'Saving...' : (courseForm.id ? 'Update course' : 'Save course') }}
                </button>
            </template>
        </DialogModal>

        <DialogModal :show="showingUnitModal" max-width="2xl" @close="showingUnitModal = false">
            <template #title>Add unit</template>

            <template #content>
                <form id="unit-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="saveUnit">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                        <select v-model="unitForm.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="updateUnitDepartment">
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
                                :disabled="!unitForm.department_id"
                                @click="toggleUnitCourseSelect"
                            >
                                <span class="truncate">{{ unitForm.department_id ? selectedUnitCourseLabel : 'Select department first' }}</span>
                                <span class="text-xs text-gray-400">▼</span>
                            </button>
                            <div v-if="unitCourseSelectOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                <div class="sticky top-0 border-b border-gray-200 bg-white p-2 dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        v-model="unitCourseSearch"
                                        class="w-full rounded-md border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                        placeholder="Search code or course"
                                    >
                                </div>
                                <button
                                    v-for="course in unitEligibleCourses"
                                    :key="course.id"
                                    type="button"
                                    class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#252b3a]"
                                    :class="Number(unitForm.course_id) === Number(course.id) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300'"
                                    @click="selectUnitCourse(course)"
                                >
                                    <span class="block font-semibold">{{ course.code }}</span>
                                    <span class="text-xs text-gray-500">{{ course.name }}</span>
                                </button>
                                <div v-if="!unitEligibleCourses.length" class="px-3 py-2 text-sm text-gray-500">
                                    No unit-based courses found
                                </div>
                            </div>
                        </div>
                        <p v-if="unitForm.errors.course_id" class="mt-1 text-xs text-red-400">{{ unitForm.errors.course_id }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit code</label>
                        <input v-model="unitForm.code" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="unitForm.errors.code" class="mt-1 text-xs text-red-400">{{ unitForm.errors.code }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit name</label>
                        <input v-model="unitForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit duration</label>
                        <input v-model="unitForm.duration" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Optional, e.g. 6 weeks">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grading</label>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesGrade(unitForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-blue-500 focus:ring-blue-500" @change="setUnitGradingFlag('grade', $event.target.checked)">
                                Grade
                            </label>
                            <label class="flex items-center gap-2">
                                <input :checked="modeUsesRange(unitForm.grading_mode)" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-blue-500 focus:ring-blue-500" @change="setUnitGradingFlag('range', $event.target.checked)">
                                Range
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Credit hours</label>
                        <input v-model="unitForm.credit_hours" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Year</label>
                            <input v-model="unitForm.year_level" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                            <input v-model="unitForm.semester_sequence" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="unitForm.is_core" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-blue-500 focus:ring-blue-500">
                        Core unit
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="unitForm.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-blue-500 focus:ring-blue-500">
                        Active unit
                    </label>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="showingUnitModal = false">Cancel</button>
                <button class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-400 disabled:opacity-50" form="unit-form" type="submit" :disabled="unitForm.processing">
                    {{ unitForm.processing ? 'Saving...' : 'Save unit' }}
                </button>
            </template>
        </DialogModal>

        <DialogModal :show="showingLecturerModal" max-width="2xl" @close="showingLecturerModal = false">
            <template #title>Add lecturer</template>

            <template #content>
                <form id="lecturer-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="assignLecturer">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit</label>
                        <select v-model="assignmentForm.unit_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select unit</option>
                            <option v-for="unit in allUnits" :key="unit.id" :value="unit.id">{{ unit.code }} - {{ unit.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lecturer</label>
                        <select v-model="assignmentForm.lecturer_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select lecturer</option>
                            <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">{{ lecturer.title }} {{ lecturer.first_name }} {{ lecturer.last_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Class</label>
                        <select v-model="assignmentForm.class_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select class</option>
                            <option v-for="collegeClass in classes" :key="collegeClass.id" :value="collegeClass.id">{{ collegeClass.code }} - {{ collegeClass.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                        <select v-model="assignmentForm.semester_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select semester</option>
                            <option v-for="semester in semesters" :key="semester.id" :value="semester.id">{{ semester.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic year</label>
                        <select v-model="assignmentForm.academic_year_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="">Select academic year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 pt-6 text-sm">
                        <input v-model="assignmentForm.is_primary" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-emerald-500 focus:ring-emerald-500">
                        Primary lecturer
                    </label>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="showingLecturerModal = false">Cancel</button>
                <button class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-400 disabled:opacity-50" form="lecturer-form" type="submit" :disabled="assignmentForm.processing">
                    {{ assignmentForm.processing ? 'Saving...' : 'Save lecturer' }}
                </button>
            </template>
        </DialogModal>

        <DialogModal :show="showingScoreLevelModal" max-width="4xl" @close="closeScoreLevelModal">
            <template #title>Course Score Levels</template>

            <template #content>
                <form id="course-score-level-form" class="space-y-3" @submit.prevent="saveScoreLevels">
                    <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-[#151a25]">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedScoreCourse?.code }} - {{ selectedScoreCourse?.name }}</p>
                        <p class="mt-1 text-xs text-gray-500">Set score ranges, grades, and comments for this course.</p>
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
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="course-score-level-form" type="submit" :disabled="scoreLevelForm.processing">
                    {{ scoreLevelForm.processing ? 'Saving...' : 'Save score levels' }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingCourse)" max-width="md" @close="closeDeleteCourseModal">
            <template #title>Delete course</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingCourse?.code }} - {{ deletingCourse?.name }}</span>?
                </p>
                <p class="mt-2">This removes the course from active academic records.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteCourseModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteCourse">Delete course</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
