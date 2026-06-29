<script setup>
import { computed, reactive, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    isLearner: Boolean,
    filters: Object,
    departments: Array,
    courses: Array,
    units: Array,
    classes: Array,
    examinations: Array,
    selectedExamination: Object,
    entries: Object,
    learnerResults: Object,
});

const page = usePage();
const organisation = computed(() => page.props.organisation || {});
const filter = reactive({
    department_id: props.filters.department_id || '',
    course_id: props.filters.course_id || '',
    subcourse_id: props.filters.subcourse_id || '',
    unit_id: props.filters.unit_id || '',
    examination_id: props.filters.examination_id || '',
    class_id: props.filters.class_id || '',
    result_search: props.filters.result_search || '',
    result_status: props.filters.result_status || '',
});
const resultForm = useForm({ class_id: filter.class_id, results: [] });

const selectedCourse = computed(() => props.courses.find((course) => Number(course.id) === Number(filter.course_id)));
const filteredCourses = computed(() => props.courses
    .filter((course) => !course.parent_course_id)
    .filter((course) => !filter.department_id || Number(course.department_id) === Number(filter.department_id)));
const selectedSubcourse = computed(() => selectedCourse.value?.subcourses?.find((course) => Number(course.id) === Number(filter.subcourse_id)));
const selectedAcademicTarget = computed(() => selectedSubcourse.value || selectedCourse.value);
const filteredUnits = computed(() => props.units.filter((unit) => !selectedAcademicTarget.value || Number(unit.course_id) === Number(selectedAcademicTarget.value.id)));
const filteredClasses = computed(() => props.classes.filter((collegeClass) => !filter.course_id || Number(collegeClass.course_id) === Number(filter.course_id)));
const filteredExaminations = computed(() => props.examinations.filter((exam) => {
    if (!filter.course_id) return false;

    if (selectedAcademicTarget.value?.has_units) {
        return filter.unit_id && Number(exam.unit_id) === Number(filter.unit_id);
    }

    return Number(exam.course_id) === Number(filter.course_id)
        && Number(exam.subcourse_id || 0) === Number(filter.subcourse_id || 0);
}));
const gradeOptions = computed(() => {
    const grades = props.selectedExamination?.effective_score_levels?.map((level) => level.grade).filter(Boolean) || [];

    return [...new Set(grades)];
});
const usesGrade = computed(() => ['grade_only', 'score_levels_with_grades'].includes(props.selectedExamination?.effective_grading_mode));
const dirtyResults = computed(() => resultForm.results.filter((row) => row.dirty && canEditRow(row)));
const pageTitle = computed(() => props.isLearner ? 'My Results' : 'Examination Results');

const applyFilters = () => {
    router.get(route('academics.results.index'), filter, { preserveScroll: true, replace: true });
};

const resetAfterDepartment = () => {
    filter.course_id = '';
    filter.subcourse_id = '';
    filter.unit_id = '';
    filter.examination_id = '';
    filter.class_id = '';
    filter.result_search = '';
    filter.result_status = '';
    applyFilters();
};

const resetAfterCourse = () => {
    filter.subcourse_id = '';
    filter.unit_id = '';
    filter.examination_id = '';
    filter.class_id = '';
    filter.result_search = '';
    filter.result_status = '';
    applyFilters();
};

const resetAfterSubcourse = () => {
    filter.unit_id = '';
    filter.examination_id = '';
    filter.class_id = '';
    filter.result_search = '';
    filter.result_status = '';
    applyFilters();
};

const resetAfterUnit = () => {
    filter.examination_id = '';
    filter.result_search = '';
    filter.result_status = '';
    applyFilters();
};

const gradeForScore = (score) => {
    if (score === '' || score === null || score === undefined) return '';
    const numericScore = Number(score);

    return props.selectedExamination?.effective_score_levels?.find((level) =>
        level.min_score !== null &&
        level.max_score !== null &&
        numericScore >= Number(level.min_score) &&
        numericScore <= Number(level.max_score)
    )?.grade || '';
};

const updateScore = (row) => {
    const grade = gradeForScore(row.score);
    if (grade) {
        row.grade = grade;
    }
    markDirty(row);
};

const markDirty = (row) => {
    row.dirty = row.score !== row.original_score || row.grade !== row.original_grade || row.comment !== row.original_comment;
};

const canEditRow = (row) => props.selectedExamination?.can_edit_results || !row.has_result;

const applyResultFilters = () => {
    applyFilters();
};

const syncEntries = () => {
    resultForm.class_id = filter.class_id;
    resultForm.results = (props.entries?.data || []).map((entry) => ({
        student_id: entry.student_id,
        semester_registration_id: entry.semester_registration_id,
        enrollment_id: entry.enrollment_id,
        admission_number: entry.admission_number,
        student_name: entry.student_name,
        class_name: entry.class_name,
        has_result: entry.has_result,
        score: entry.score ?? '',
        grade: entry.grade ?? '',
        comment: entry.comment ?? '',
        original_score: entry.score ?? '',
        original_grade: entry.grade ?? '',
        original_comment: entry.comment ?? '',
        dirty: false,
    }));
};

watch(() => props.entries, syncEntries, { immediate: true });

const saveResults = () => {
    if (!props.selectedExamination || !filter.class_id) return;

    resultForm.class_id = filter.class_id;
    resultForm
        .transform((data) => ({
            ...data,
            results: dirtyResults.value.map((row) => ({
                student_id: row.student_id,
                semester_registration_id: row.semester_registration_id,
                enrollment_id: row.enrollment_id,
                score: row.score,
                grade: row.grade,
                comment: row.comment,
            })),
        }))
        .post(route('academics.results.store', props.selectedExamination.id), {
        preserveScroll: true,
        onFinish: () => resultForm.transform((data) => data),
    });
};

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const formatDate = (value) => {
    if (!value) return '';

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const printResult = (result) => {
    const printWindow = window.open('', '_blank', 'width=820,height=900');

    if (!printWindow) return;

    const org = organisation.value;
    const exam = result.examination || {};

    printWindow.document.write(`
        <!doctype html>
        <html>
            <head>
                <title>Result ${escapeHtml(exam.code || result.id)}</title>
                <style>
                    * { box-sizing: border-box; }
                    @page { size: 80mm auto; margin: 3mm; }
                    body { margin: 0; background: #fff; color: #000; font-family: "Courier New", Courier, monospace; font-size: 11px; }
                    .receipt { width: 72mm; margin: 0 auto; }
                    .header, .title, .thanks { text-align: center; }
                    .org-name { font-size: 14px; font-weight: 700; text-transform: uppercase; }
                    .org-meta { margin-top: 3px; font-size: 10px; line-height: 1.35; }
                    .line { border-top: 1px dashed #000; margin: 7px 0; }
                    .title { font-size: 13px; font-weight: 700; text-transform: uppercase; }
                    .row { display: flex; justify-content: space-between; gap: 8px; line-height: 1.45; }
                    .row span:first-child { white-space: nowrap; }
                    .row span:last-child { text-align: right; overflow-wrap: anywhere; }
                    .total-row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; font-weight: 700; }
                    .block { line-height: 1.45; }
                    @media print { .receipt { width: 72mm; margin: 0; } }
                </style>
            </head>
            <body>
                <main class="receipt">
                    <section class="header">
                        <h1 class="org-name">${escapeHtml(org.name || 'Organisation')}</h1>
                        <p class="org-meta">${escapeHtml(org.location || '')}<br>Tel: ${escapeHtml(org.primary_contact || '')}<br>Email: ${escapeHtml(org.official_email || '')}</p>
                    </section>
                    <div class="line"></div>
                    <div class="title">Result Slip</div>
                    <div class="line"></div>
                    <section class="block">
                        <p><strong>Examination:</strong> ${escapeHtml(`${exam.code ? `${exam.code} - ` : ''}${exam.name || ''}`)}</p>
                        <p><strong>Target:</strong> ${escapeHtml(result.target_label || '')}</p>
                        <p><strong>Class:</strong> ${escapeHtml(result.class_label || '')}</p>
                        <p><strong>Term:</strong> ${escapeHtml([exam.academic_year?.name, exam.semester?.name].filter(Boolean).join(' / '))}</p>
                    </section>
                    <div class="line"></div>
                    <section>
                        <div class="total-row"><span>Score</span><span>${escapeHtml(result.score ?? '-')} / ${escapeHtml(exam.max_score ?? 100)}</span></div>
                        <div class="row"><span>Grade</span><span>${escapeHtml(result.grade || '-')}</span></div>
                        <div class="row"><span>Comment</span><span>${escapeHtml(result.comment || '-')}</span></div>
                        <div class="row"><span>Recorded</span><span>${escapeHtml(formatDate(result.recorded_at))}</span></div>
                    </section>
                    <div class="line"></div>
                    <p class="thanks">Downloaded result record</p>
                    <p class="org-meta">Printed: ${escapeHtml(new Date().toLocaleString())}</p>
                </main>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => printWindow.print(), 250);
};
</script>

<template>
    <AppLayout :title="pageTitle">
        <template v-if="isLearner">
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Recorded Results</p>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">My Results</h1>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="filter.result_search" class="h-9 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Search result">
                        <button class="h-9 rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-600 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-300" type="button" @click="applyResultFilters">Search</button>
                    </div>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <table v-if="learnerResults?.data?.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                        <tr>
                            <th class="px-5 py-3">Examination</th>
                            <th class="px-5 py-3">Target</th>
                            <th class="px-5 py-3">Class</th>
                            <th class="px-5 py-3">Score</th>
                            <th class="px-5 py-3">Grade</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                        <tr v-for="result in learnerResults.data" :key="result.id">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ result.examination?.code }} - {{ result.examination?.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatDate(result.recorded_at) }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ result.target_label || '-' }}</td>
                            <td class="px-5 py-4 text-gray-500">{{ result.class_label || '-' }}</td>
                            <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ result.score ?? '-' }} / {{ result.examination?.max_score || 100 }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-500">{{ result.grade || '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button class="rounded-md border border-violet-500/30 px-2.5 py-1.5 text-xs text-violet-600 transition hover:border-violet-400 dark:text-violet-300" type="button" @click="printResult(result)">Download</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="flex min-h-[240px] items-center justify-center px-6 text-center text-sm text-gray-500">
                    No recorded results are available for your account yet.
                </div>
                <div v-if="learnerResults?.links" class="border-t border-gray-200 p-4 dark:border-[#232837]">
                    <Pagination :links="learnerResults.links" />
                </div>
            </div>
        </template>

        <template v-else>
        <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                    <select v-model="filter.department_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" @change="resetAfterDepartment">
                        <option value="">Select department</option>
                        <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                    <select v-model="filter.course_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!filter.department_id" @change="resetAfterCourse">
                        <option value="">Select course</option>
                        <option v-for="course in filteredCourses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                    </select>
                </div>
                <div v-if="selectedCourse?.subcourses?.length">
                    <label v-if="selectedCourse?.subcourses?.length" class="text-xs font-semibold uppercase tracking-wider text-gray-500">Subcourse</label>
                    <select v-if="selectedCourse?.subcourses?.length" v-model="filter.subcourse_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!filter.course_id" @change="resetAfterSubcourse">
                        <option value="">General course</option>
                        <option v-for="subcourse in selectedCourse.subcourses" :key="subcourse.id" :value="subcourse.id">{{ subcourse.code }} - {{ subcourse.name }}</option>
                    </select>
                </div>
                <div v-if="selectedAcademicTarget?.has_units">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unit</label>
                    <select v-model="filter.unit_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!filter.course_id" @change="resetAfterUnit">
                        <option value="">Select unit</option>
                        <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">{{ unit.code }} - {{ unit.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Examination</label>
                    <select v-model="filter.examination_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!filter.course_id || (selectedAcademicTarget?.has_units && !filter.unit_id)" @change="applyFilters">
                        <option value="">Select examination</option>
                        <option v-for="exam in filteredExaminations" :key="exam.id" :value="exam.id">{{ exam.code ? `${exam.code} - ` : '' }}{{ exam.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Class</label>
                    <select v-model="filter.class_id" class="mt-1 h-10 w-full rounded-md border-gray-200 bg-white text-sm disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!filter.examination_id" @change="applyFilters">
                        <option value="">Select class</option>
                        <option v-for="collegeClass in filteredClasses" :key="collegeClass.id" :value="collegeClass.id">{{ collegeClass.code }} - {{ collegeClass.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-[#232837]">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedExamination?.name || 'Select an examination' }}</p>
                    <p class="text-xs text-gray-500">
                        Grading source: {{ selectedExamination?.score_level_source || '-' }}
                        <span v-if="selectedExamination?.max_score">/ Max {{ selectedExamination.max_score }}</span>
                        <span v-if="selectedExamination">/ {{ selectedExamination.can_edit_results ? 'Submitted results can be edited' : 'Submitted results are locked' }}</span>
                    </p>
                </div>
                <button class="h-9 rounded-md bg-emerald-500 px-4 text-sm font-semibold text-white transition hover:bg-emerald-400 disabled:opacity-50" type="button" :disabled="resultForm.processing || !dirtyResults.length" @click="saveResults">
                    {{ resultForm.processing ? 'Saving...' : `Save ${dirtyResults.length || ''} changes` }}
                </button>
                </div>

                <div class="grid gap-2 sm:grid-cols-[1fr_160px_auto]">
                    <input v-model="filter.result_search" class="h-9 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Search learner or admission no.">
                    <select v-model="filter.result_status" class="h-9 rounded-md border-gray-200 bg-white text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]">
                        <option value="">All results</option>
                        <option value="scored">Scored</option>
                        <option value="unscored">Unscored</option>
                    </select>
                    <button class="h-9 rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-600 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-300" type="button" :disabled="!filter.class_id" @click="applyResultFilters">Search</button>
                </div>
            </div>

            <div v-if="resultForm.results.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                        <tr>
                            <th class="px-4 py-3">Learner</th>
                            <th class="px-4 py-3">Class</th>
                            <th class="px-4 py-3">Score</th>
                            <th v-if="usesGrade" class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3">Comment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                        <tr v-for="row in resultForm.results" :key="row.student_id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ row.admission_number }}</p>
                                    <span v-if="row.has_result" class="rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-500">Saved</span>
                                    <span v-if="row.dirty" class="rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-amber-500">Edited</span>
                                </div>
                                <p class="text-xs text-gray-500">{{ row.student_name }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ row.class_name || '-' }}</td>
                            <td class="px-4 py-3">
                                <input v-model="row.score" type="number" min="0" :max="selectedExamination?.max_score || 100" step="0.01" class="h-9 w-28 rounded-md border-gray-200 text-sm disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Score" :disabled="!canEditRow(row)" @input="updateScore(row)">
                            </td>
                            <td v-if="usesGrade" class="px-4 py-3">
                                <select v-if="gradeOptions.length" v-model="row.grade" class="h-9 w-32 rounded-md border-gray-200 text-sm disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16]" :disabled="!canEditRow(row)" @change="markDirty(row)">
                                    <option value="">Grade</option>
                                    <option v-for="grade in gradeOptions" :key="grade" :value="grade">{{ grade }}</option>
                                </select>
                                <input v-else v-model="row.grade" class="h-9 w-32 rounded-md border-gray-200 text-sm disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Grade" :disabled="!canEditRow(row)" @input="markDirty(row)">
                            </td>
                            <td class="px-4 py-3"><input v-model="row.comment" class="h-9 w-full min-w-56 rounded-md border-gray-200 text-sm disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Comment" :disabled="!canEditRow(row)" @input="markDirty(row)"></td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                    <Pagination :links="entries.links" />
                </div>
            </div>
            <div v-else class="flex min-h-[260px] items-center justify-center px-6 text-center text-sm text-gray-500">
                Select department, course, examination, and class to load learners.
            </div>
        </div>
        </template>
    </AppLayout>
</template>
