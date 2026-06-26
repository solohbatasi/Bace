<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    academicYears: Object,
    semesters: Object,
    classes: Object,
    courses: Array,
    departments: Array,
    lecturers: Array,
    academicYearOptions: Array,
    semesterOptions: Array,
    permissions: Object,
});

const initialTab = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('classes_page')) return 'classes';
    if (params.has('semesters_page')) return 'semesters';
    return 'academicYears';
};

const activeTab = ref(initialTab());
const deleting = ref(null);

const yearForm = useForm({ id: null, name: '', starts_on: '', ends_on: '', is_current: false });
const semesterForm = useForm({ id: null, academic_year_id: '', name: '', sequence: 1, starts_on: '', ends_on: '', is_current: false });
const classForm = useForm({ id: null, course_id: '', department_id: '', academic_year_id: '', semester_id: '', class_lecturer_id: '', code: '', name: '', year_level: 1, capacity: '', is_active: true });

const tabs = computed(() => [
    { key: 'academicYears', label: 'Academic Years', count: props.academicYears.total },
    { key: 'semesters', label: 'Semesters', count: props.semesters.total },
    { key: 'classes', label: 'Classes', count: props.classes.total },
]);

const singularLabel = computed(() => ({
    academicYears: 'Academic Year',
    semesters: 'Semester',
    classes: 'Class',
})[activeTab.value]);

const canAddCurrent = computed(() => ({
    academicYears: props.permissions?.canAddAcademicYear,
    semesters: props.permissions?.canAddSemester,
    classes: props.permissions?.canAddClass,
})[activeTab.value]);

const canEdit = (type) => ({
    academicYears: props.permissions?.canEditAcademicYear,
    semesters: props.permissions?.canEditSemester,
    classes: props.permissions?.canEditClass,
})[type];

const canDelete = (type) => ({
    academicYears: props.permissions?.canDeleteAcademicYear,
    semesters: props.permissions?.canDeleteSemester,
    classes: props.permissions?.canDeleteClass,
})[type];

const stats = computed(() => ({
    years: props.academicYears.total,
    currentYear: props.academicYearOptions.find((year) => year.is_current)?.name || '-',
    semesters: props.semesters.total,
    classes: props.classes.data.filter((collegeClass) => collegeClass.is_active).length,
}));

const activePagination = computed(() => {
    if (activeTab.value === 'academicYears') return props.academicYears;
    if (activeTab.value === 'semesters') return props.semesters;
    return props.classes;
});

const currentForm = computed(() => {
    if (activeTab.value === 'academicYears') return yearForm;
    if (activeTab.value === 'semesters') return semesterForm;
    return classForm;
});

const modalOpen = ref(false);
const showingModal = computed(() => modalOpen.value);
const dateValue = (value) => value ? String(value).slice(0, 10) : '';

const resetYearForm = () => {
    yearForm.clearErrors();
    yearForm.id = null;
    yearForm.name = '';
    yearForm.starts_on = '';
    yearForm.ends_on = '';
    yearForm.is_current = false;
};

const resetSemesterForm = () => {
    semesterForm.clearErrors();
    semesterForm.id = null;
    semesterForm.academic_year_id = props.academicYearOptions.find((year) => year.is_current)?.id || props.academicYearOptions[0]?.id || '';
    semesterForm.name = '';
    semesterForm.sequence = 1;
    semesterForm.starts_on = '';
    semesterForm.ends_on = '';
    semesterForm.is_current = false;
};

const resetClassForm = () => {
    classForm.clearErrors();
    classForm.id = null;
    classForm.course_id = '';
    classForm.department_id = '';
    classForm.academic_year_id = props.academicYearOptions.find((year) => year.is_current)?.id || props.academicYearOptions[0]?.id || '';
    classForm.semester_id = props.semesterOptions.find((semester) => semester.is_current)?.id || '';
    classForm.class_lecturer_id = '';
    classForm.code = '';
    classForm.name = '';
    classForm.year_level = 1;
    classForm.capacity = '';
    classForm.is_active = true;
};

const openCreateModal = (tab = activeTab.value) => {
    activeTab.value = tab;
    if (tab === 'academicYears') resetYearForm();
    if (tab === 'semesters') resetSemesterForm();
    if (tab === 'classes') resetClassForm();
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetYearForm();
    resetSemesterForm();
    resetClassForm();
};

const editAcademicYear = (year) => {
    activeTab.value = 'academicYears';
    resetYearForm();
    yearForm.id = year.id;
    yearForm.name = year.name;
    yearForm.starts_on = dateValue(year.starts_on);
    yearForm.ends_on = dateValue(year.ends_on);
    yearForm.is_current = Boolean(year.is_current);
    modalOpen.value = true;
};

const editSemester = (semester) => {
    activeTab.value = 'semesters';
    resetSemesterForm();
    semesterForm.id = semester.id;
    semesterForm.academic_year_id = semester.academic_year_id;
    semesterForm.name = semester.name;
    semesterForm.sequence = semester.sequence;
    semesterForm.starts_on = dateValue(semester.starts_on);
    semesterForm.ends_on = dateValue(semester.ends_on);
    semesterForm.is_current = Boolean(semester.is_current);
    modalOpen.value = true;
};

const editClass = (collegeClass) => {
    activeTab.value = 'classes';
    resetClassForm();
    classForm.id = collegeClass.id;
    classForm.course_id = collegeClass.course_id;
    classForm.department_id = collegeClass.department_id;
    classForm.academic_year_id = collegeClass.academic_year_id;
    classForm.semester_id = collegeClass.semester_id || '';
    classForm.class_lecturer_id = collegeClass.class_lecturer_id || '';
    classForm.code = collegeClass.code;
    classForm.name = collegeClass.name;
    classForm.year_level = collegeClass.year_level;
    classForm.capacity = collegeClass.capacity || '';
    classForm.is_active = Boolean(collegeClass.is_active);
    modalOpen.value = true;
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };

    if (activeTab.value === 'academicYears') {
        yearForm.id
            ? yearForm.put(route('academics.settings.academic-years.update', yearForm.id), options)
            : yearForm.post(route('academics.settings.academic-years.store'), options);
    }

    if (activeTab.value === 'semesters') {
        semesterForm.id
            ? semesterForm.put(route('academics.settings.semesters.update', semesterForm.id), options)
            : semesterForm.post(route('academics.settings.semesters.store'), options);
    }

    if (activeTab.value === 'classes') {
        classForm.id
            ? classForm.put(route('academics.settings.classes.update', classForm.id), options)
            : classForm.post(route('academics.settings.classes.store'), options);
    }
};

const askDelete = (type, record) => {
    deleting.value = { type, record };
};

const closeDeleteModal = () => {
    deleting.value = null;
};

const confirmDelete = () => {
    if (!deleting.value) return;

    const routeName = {
        academicYears: 'academics.settings.academic-years.destroy',
        semesters: 'academics.settings.semesters.destroy',
        classes: 'academics.settings.classes.destroy',
    }[deleting.value.type];

    router.delete(route(routeName, deleting.value.record.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};

const selectCourse = () => {
    const course = props.courses.find((item) => item.id === classForm.course_id);
    if (course?.department_id) classForm.department_id = course.department_id;
};
</script>

<template>
    <AppLayout title="Academic Settings">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Academic Years</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.years }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Current Year</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.currentYear }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Semesters</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.semesters }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active Classes</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.classes }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <div class="inline-flex w-fit rounded-md border border-gray-200 bg-white p-1 dark:border-[#273044] dark:bg-[#11141b]">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="h-8 rounded-md px-3 text-xs font-semibold transition"
                    :class="activeTab === tab.key ? 'bg-violet-500 text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }} ({{ tab.count }})
                </button>
            </div>

            <button v-if="canAddCurrent" class="inline-flex h-8 w-fit items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openCreateModal()">
                <span class="text-base leading-none">+</span>
                Add {{ singularLabel }}
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="activeTab === 'academicYears' && academicYears.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Academic Year</th>
                        <th class="px-5 py-3">Dates</th>
                        <th class="px-5 py-3">Semesters</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="year in academicYears.data" :key="year.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ year.name }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ year.starts_on }} to {{ year.ends_on }}</td>
                        <td class="px-5 py-4 text-gray-500">{{ year.semesters_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="year.is_current ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400'">{{ year.is_current ? 'current' : 'archived' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="canEdit('academicYears')" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editAcademicYear(year)">Edit</button>
                            <button v-if="canDelete('academicYears')" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" type="button" @click="askDelete('academicYears', year)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table v-else-if="activeTab === 'semesters' && semesters.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Semester</th>
                        <th class="px-5 py-3">Academic Year</th>
                        <th class="px-5 py-3">Sequence</th>
                        <th class="px-5 py-3">Dates</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="semester in semesters.data" :key="semester.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ semester.name }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ semester.academic_year?.name || '-' }}</td>
                        <td class="px-5 py-4 text-gray-500">{{ semester.sequence }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ semester.starts_on }} to {{ semester.ends_on }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="semester.is_current ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400'">{{ semester.is_current ? 'current' : 'inactive' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="canEdit('semesters')" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editSemester(semester)">Edit</button>
                            <button v-if="canDelete('semesters')" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" type="button" @click="askDelete('semesters', semester)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table v-else-if="activeTab === 'classes' && classes.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Course</th>
                        <th class="px-5 py-3">Term</th>
                        <th class="px-5 py-3">Capacity</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="collegeClass in classes.data" :key="collegeClass.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ collegeClass.code }} - {{ collegeClass.name }}</p>
                            <p class="text-xs text-gray-500">Y{{ collegeClass.year_level }} / {{ collegeClass.students_count }} students</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ collegeClass.course?.code }} - {{ collegeClass.course?.name }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ collegeClass.academic_year?.name || '-' }} / {{ collegeClass.semester?.name || 'All semesters' }}</td>
                        <td class="px-5 py-4 text-gray-500">{{ collegeClass.capacity || '-' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="collegeClass.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">{{ collegeClass.is_active ? 'active' : 'inactive' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="canEdit('classes')" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editClass(collegeClass)">Edit</button>
                            <button v-if="canDelete('classes')" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 hover:border-red-400" type="button" @click="askDelete('classes', collegeClass)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No records found</p>
                <p class="mt-1 text-sm text-gray-500">Create a record for the selected tab.</p>
                <button v-if="canAddCurrent" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openCreateModal()">Add {{ singularLabel }}</button>
            </div>

            <div v-if="activePagination.links?.length" class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="activePagination.links" />
            </div>
        </div>

        <DialogModal :show="showingModal" max-width="2xl" @close="closeModal">
            <template #title>{{ currentForm.id ? 'Edit' : 'Create' }} {{ singularLabel }}</template>

            <template #content>
                <form id="academic-settings-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="save">
                    <template v-if="activeTab === 'academicYears'">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                            <input v-model="yearForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="2026/2027" required>
                            <p v-if="yearForm.errors.name" class="mt-1 text-xs text-red-400">{{ yearForm.errors.name }}</p>
                        </div>
                        <label class="flex items-center gap-2 pt-6 text-sm">
                            <input v-model="yearForm.is_current" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                            Current academic year
                        </label>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Starts on</label>
                            <input v-model="yearForm.starts_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ends on</label>
                            <input v-model="yearForm.ends_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <p v-if="yearForm.errors.ends_on" class="mt-1 text-xs text-red-400">{{ yearForm.errors.ends_on }}</p>
                        </div>
                    </template>

                    <template v-if="activeTab === 'semesters'">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic year</label>
                            <select v-model="semesterForm.academic_year_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                <option value="">Select year</option>
                                <option v-for="year in academicYearOptions" :key="year.id" :value="year.id">{{ year.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sequence</label>
                            <input v-model="semesterForm.sequence" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <p v-if="semesterForm.errors.sequence" class="mt-1 text-xs text-red-400">{{ semesterForm.errors.sequence }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                            <input v-model="semesterForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <label class="flex items-center gap-2 pt-6 text-sm">
                            <input v-model="semesterForm.is_current" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                            Current semester
                        </label>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Starts on</label>
                            <input v-model="semesterForm.starts_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ends on</label>
                            <input v-model="semesterForm.ends_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <p v-if="semesterForm.errors.ends_on" class="mt-1 text-xs text-red-400">{{ semesterForm.errors.ends_on }}</p>
                        </div>
                    </template>

                    <template v-if="activeTab === 'classes'">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                            <select v-model="classForm.course_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="selectCourse">
                                <option value="">Select course</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                            <select v-model="classForm.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                <option value="">Select department</option>
                                <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.code }} - {{ department.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic year</label>
                            <select v-model="classForm.academic_year_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                <option value="">Select year</option>
                                <option v-for="year in academicYearOptions" :key="year.id" :value="year.id">{{ year.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                            <select v-model="classForm.semester_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                <option value="">All semesters</option>
                                <option v-for="semester in semesterOptions" :key="semester.id" :value="semester.id">{{ semester.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Code</label>
                            <input v-model="classForm.code" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <p v-if="classForm.errors.code" class="mt-1 text-xs text-red-400">{{ classForm.errors.code }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</label>
                            <input v-model="classForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Year level</label>
                            <input v-model="classForm.year_level" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Capacity</label>
                            <input v-model="classForm.capacity" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Class lecturer</label>
                            <select v-model="classForm.class_lecturer_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                <option value="">None</option>
                                <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">{{ lecturer.title }} {{ lecturer.first_name }} {{ lecturer.last_name }}</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2 pt-6 text-sm">
                            <input v-model="classForm.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                            Active class
                        </label>
                    </template>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="academic-settings-form" type="submit" :disabled="currentForm.processing">
                    {{ currentForm.processing ? 'Saving...' : 'Save record' }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deleting)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete record</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deleting?.record?.code || deleting?.record?.name }}</span>?
                </p>
                <p class="mt-2">Linked enrollments, classes, or semester records may prevent deletion.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDelete">Delete record</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
