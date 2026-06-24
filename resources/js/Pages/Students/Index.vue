<!-- resources/js/Pages/Students/Index.vue -->
<script setup>
import { computed, reactive, ref, watch, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { onClickOutside } from '@vueuse/core';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    students: Object,
    filters: Object,
    statuses: Array,
    courses: Array,
    classes: Array,
    departments: Array,
    academicYears: Array,
    semesters: Array,
});

const filter = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    course_id: props.filters.course_id || '',
    class_id: props.filters.class_id || '',
});

const studentForm = useForm({
    id: null,
    department_id: '',
    course_id: '',
    course_fee: '',
    class_id: '',
    academic_year_id: props.academicYears.find((year) => year.is_current)?.id || props.academicYears[0]?.id || '',
    semester_id: props.semesters.find((semester) => semester.is_current)?.id || props.semesters[0]?.id || '',
    admission_number: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    gender: '',
    date_of_birth: '',
    email: '',
    phone: '',
    photo: null,
    photo_preview: null,
    address: '',
    guardian_name: '',
    guardian_relationship: '',
    guardian_phone: '',
    guardian_email: '',
    guardian_address: '',
    admitted_on: '',
    status: 'active',
    academic_histories: [],
    additional_courses: [],
});

// Searchable select states
const searchableSelects = reactive({
    department: { isOpen: false, search: '' },
    course: { isOpen: false, search: '' },
    class: { isOpen: false, search: '' },
});

// Refs for click outside detection
const departmentDropdown = ref(null);
const courseDropdown = ref(null);
const classDropdown = ref(null);

// Click outside handlers
onClickOutside(departmentDropdown, () => {
    searchableSelects.department.isOpen = false;
});
onClickOutside(courseDropdown, () => {
    searchableSelects.course.isOpen = false;
});
onClickOutside(classDropdown, () => {
    searchableSelects.class.isOpen = false;
});

// Computed filtered items
const filteredDepartments = computed(() => {
    const search = searchableSelects.department.search.toLowerCase();
    if (!search) return props.departments;
    return props.departments.filter(dept =>
        dept.name.toLowerCase().includes(search) ||
        dept.code.toLowerCase().includes(search)
    );
});

const filteredCourses = computed(() => {
    const search = searchableSelects.course.search.toLowerCase();
    const courses = coursesForDepartment(studentForm.department_id);
    if (!search) return courses;
    return courses.filter(course =>
        course.name.toLowerCase().includes(search) ||
        course.code.toLowerCase().includes(search)
    );
});

const filteredClasses = computed(() => {
    const search = searchableSelects.class.search.toLowerCase();
    const classes = classesForCourse(studentForm.course_id);
    if (!search) return classes;
    return classes.filter(cls =>
        cls.name.toLowerCase().includes(search) ||
        cls.code.toLowerCase().includes(search)
    );
});

const filteredSemesters = computed(() => {
    if (!studentForm.academic_year_id) return props.semesters;
    return props.semesters.filter((semester) => semester.academic_year_id === studentForm.academic_year_id);
});

// Get selected item labels
const selectedDepartmentLabel = computed(() => {
    const dept = props.departments.find(d => d.id === studentForm.department_id);
    return dept ? `${dept.code} - ${dept.name}` : 'Select department';
});

const selectedCourseLabel = computed(() => {
    const course = props.courses.find(c => c.id === studentForm.course_id);
    return course ? `${course.code} - ${course.name}` : 'Select course';
});

const selectedCourse = computed(() => props.courses.find(c => c.id === studentForm.course_id));
const additionalCourseIds = computed(() => studentForm.additional_courses.map((course) => Number(course.course_id)).filter(Boolean));
const unavailableAdditionalCourseIds = computed(() => [
    Number(studentForm.course_id),
    ...currentRegisteredCourseIds.value,
    ...additionalCourseIds.value,
].filter(Boolean));

const selectedClassLabel = computed(() => {
    const cls = props.classes.find(c => c.id === studentForm.class_id);
    return cls ? `${cls.code} - ${cls.name}` : 'Select class';
});

const dateInputValue = (value) => {
    if (!value) return '';
    return String(value).slice(0, 10);
};

const money = (value) => `KES ${Number(value || 0).toLocaleString()}`;

const coursesForDepartment = (departmentId) => {
    if (!departmentId) return props.courses;

    return props.courses.filter((course) => Number(course.department_id) === Number(departmentId));
};

const classesForCourse = (courseId) => props.classes.filter((cls) => {
    const matchesCourse = Number(cls.course_id) === Number(courseId);
    const matchesYear = !studentForm.academic_year_id || Number(cls.academic_year_id) === Number(studentForm.academic_year_id);

    return matchesCourse && matchesYear;
});

const courseFee = (courseId) => props.courses.find((course) => Number(course.id) === Number(courseId))?.fees ?? '';

const showingEnrollmentModal = ref(false);
const deletingStudent = ref(null);
const selectedUnits = ref([]);
const courseUnits = ref([]);
const loadingUnits = ref(false);
const isEditing = ref(false);
const currentRegisteredCourseIds = ref([]);
const showingGuardianInformation = ref(false);

// Stats
const stats = computed(() => ({
    total: props.students.total,
    active: props.students.data.filter(s => s.status === 'active').length,
    graduated: props.students.data.filter(s => s.status === 'graduated').length,
    deferred: props.students.data.filter(s => s.status === 'deferred').length,
}));

// Watch filters
watch(filter, () => router.get(route('students.index'), filter, {
    preserveState: true,
    replace: true
}), { deep: true });

// Reset forms
const resetStudentForm = () => {
    studentForm.clearErrors();
    studentForm.reset();
    studentForm.id = null;
    studentForm.status = 'active';
    studentForm.academic_year_id = props.academicYears.find((year) => year.is_current)?.id || props.academicYears[0]?.id || '';
    studentForm.semester_id = props.semesters.find((semester) => semester.is_current)?.id || props.semesters[0]?.id || '';
    studentForm.academic_histories = [];
    studentForm.additional_courses = [];
    studentForm.photo = null;
    studentForm.photo_preview = null;
    studentForm.course_fee = '';
    courseUnits.value = [];
    selectedUnits.value = [];
    isEditing.value = false;
    currentRegisteredCourseIds.value = [];
    showingGuardianInformation.value = false;

    // Reset searchable selects
    searchableSelects.department.search = '';
    searchableSelects.course.search = '';
    searchableSelects.class.search = '';
    searchableSelects.department.isOpen = false;
    searchableSelects.course.isOpen = false;
    searchableSelects.class.isOpen = false;
};

// Open enrollment modal
const openEnrollmentModal = () => {
    resetStudentForm();
    showingEnrollmentModal.value = true;
};

// Edit student
const editStudent = (student) => {
    resetStudentForm();
    isEditing.value = true;
    studentForm.id = student.id;
    studentForm.department_id = student.department_id;
    studentForm.course_id = student.course_id;
    studentForm.course_fee = student.course_fee ?? selectedCourse.value?.fees ?? '';
    studentForm.class_id = student.class_id;
    studentForm.academic_year_id = props.academicYears.find((year) => year.is_current)?.id || props.academicYears[0]?.id || '';
    studentForm.semester_id = props.semesters.find((semester) => semester.is_current)?.id || props.semesters[0]?.id || '';
    studentForm.admission_number = student.admission_number;
    studentForm.first_name = student.first_name;
    studentForm.middle_name = student.middle_name || '';
    studentForm.last_name = student.last_name;
    studentForm.gender = student.gender || '';
    studentForm.date_of_birth = dateInputValue(student.date_of_birth);
    studentForm.email = student.email || '';
    studentForm.phone = student.phone || '';
    studentForm.address = student.address || '';
    studentForm.guardian_name = student.guardian_name || '';
    studentForm.guardian_relationship = student.guardian_relationship || '';
    studentForm.guardian_phone = student.guardian_phone || '';
    studentForm.guardian_email = student.guardian_email || '';
    studentForm.guardian_address = student.guardian_address || '';
    showingGuardianInformation.value = Boolean(
        student.guardian_name ||
        student.guardian_relationship ||
        student.guardian_phone ||
        student.guardian_email ||
        student.guardian_address
    );
    studentForm.admitted_on = dateInputValue(student.admitted_on);
    studentForm.status = student.status;
    studentForm.photo_preview = student.photo_path ? `/storage/${student.photo_path}` : null;
    currentRegisteredCourseIds.value = (student.registered_courses || [])
        .map((course) => Number(course.id))
        .filter((courseId) => courseId && courseId !== Number(student.course_id));

    // Fetch course units for editing
    if (student.course_id) {
        fetchCourseUnits(student.course_id);
    }

    showingEnrollmentModal.value = true;
};

// Close modal
const closeEnrollmentModal = () => {
    showingEnrollmentModal.value = false;
    resetStudentForm();
};

// Photo handling
const handlePhotoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        studentForm.photo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            studentForm.photo_preview = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Remove photo
const removePhoto = () => {
    studentForm.photo = null;
    studentForm.photo_preview = null;
    const fileInput = document.querySelector('#photo-input');
    if (fileInput) fileInput.value = '';
};

// Fetch course units
const fetchCourseUnits = async (courseId) => {
    if (!courseId) {
        courseUnits.value = [];
        selectedUnits.value = [];
        return;
    }

    loadingUnits.value = true;
    try {
        const response = await axios.get(route('api.courses.units', courseId));
        courseUnits.value = response.data;
        // Auto-select all units by default
        selectedUnits.value = response.data.map(unit => unit.id);
    } catch (error) {
        console.error('Error fetching course units:', error);
    } finally {
        loadingUnits.value = false;
    }
};

// Watch course_id changes for unit loading
watch(() => studentForm.course_id, (newCourseId) => {
    const course = props.courses.find(c => c.id === newCourseId);
    if (!isEditing.value || !studentForm.course_fee) {
        studentForm.course_fee = course?.fees ?? '';
    }

    if (!classesForCourse(newCourseId).some((cls) => Number(cls.id) === Number(studentForm.class_id))) {
        studentForm.class_id = '';
    }

    if (newCourseId && !isEditing.value) {
        fetchCourseUnits(newCourseId);
    } else if (!newCourseId) {
        courseUnits.value = [];
        selectedUnits.value = [];
        studentForm.course_fee = '';
    }
});

watch(() => studentForm.academic_year_id, () => {
    if (!filteredSemesters.value.some((semester) => semester.id === studentForm.semester_id)) {
        studentForm.semester_id = filteredSemesters.value[0]?.id || '';
    }

    if (!classesForCourse(studentForm.course_id).some((cls) => Number(cls.id) === Number(studentForm.class_id))) {
        studentForm.class_id = '';
    }
});

// Select handlers
const selectDepartment = (dept) => {
    studentForm.department_id = dept.id;
    studentForm.course_id = '';
    studentForm.class_id = '';
    studentForm.course_fee = '';
    courseUnits.value = [];
    selectedUnits.value = [];
    searchableSelects.department.isOpen = false;
    searchableSelects.department.search = '';
};

const selectCourse = (course) => {
    studentForm.course_id = course.id;
    studentForm.course_fee = course.fees ?? '';
    searchableSelects.course.isOpen = false;
    searchableSelects.course.search = '';
};

const selectClass = (cls) => {
    studentForm.class_id = cls.id;
    searchableSelects.class.isOpen = false;
    searchableSelects.class.search = '';
};

const addAdditionalCourse = () => {
    studentForm.additional_courses.push({
        department_id: '',
        course_id: '',
        class_id: '',
        course_fee: '',
        units: [],
        available_units: [],
        loading_units: false,
    });
};

const removeAdditionalCourse = (index) => {
    studentForm.additional_courses.splice(index, 1);
};

const handleAdditionalCourseChange = (courseRegistration) => {
    courseRegistration.class_id = '';
    courseRegistration.course_fee = courseFee(courseRegistration.course_id);
    fetchAdditionalCourseUnits(courseRegistration);
};

const handleAdditionalDepartmentChange = (courseRegistration) => {
    courseRegistration.course_id = '';
    courseRegistration.class_id = '';
    courseRegistration.course_fee = '';
    courseRegistration.units = [];
    courseRegistration.available_units = [];
};

const fetchAdditionalCourseUnits = async (courseRegistration) => {
    if (!courseRegistration.course_id) {
        courseRegistration.units = [];
        courseRegistration.available_units = [];
        return;
    }

    courseRegistration.loading_units = true;
    try {
        const response = await axios.get(route('api.courses.units', courseRegistration.course_id));
        courseRegistration.available_units = response.data;
        courseRegistration.units = response.data.map(unit => unit.id);
    } catch (error) {
        console.error('Error fetching additional course units:', error);
    } finally {
        courseRegistration.loading_units = false;
    }
};

const additionalCoursePayload = () => studentForm.additional_courses.map((courseRegistration) => ({
    department_id: courseRegistration.department_id,
    course_id: courseRegistration.course_id,
    class_id: courseRegistration.class_id,
    course_fee: courseRegistration.course_fee,
    units: courseRegistration.units || [],
}));

// Toggle dropdowns
const toggleDepartment = () => {
    searchableSelects.department.isOpen = !searchableSelects.department.isOpen;
    if (searchableSelects.department.isOpen) {
        searchableSelects.course.isOpen = false;
        searchableSelects.class.isOpen = false;
        nextTick(() => {
            const input = document.querySelector('#department-search');
            if (input) input.focus();
        });
    }
};

const toggleCourse = () => {
    searchableSelects.course.isOpen = !searchableSelects.course.isOpen;
    if (searchableSelects.course.isOpen) {
        searchableSelects.department.isOpen = false;
        searchableSelects.class.isOpen = false;
        nextTick(() => {
            const input = document.querySelector('#course-search');
            if (input) input.focus();
        });
    }
};

const toggleClass = () => {
    searchableSelects.class.isOpen = !searchableSelects.class.isOpen;
    if (searchableSelects.class.isOpen) {
        searchableSelects.department.isOpen = false;
        searchableSelects.course.isOpen = false;
        nextTick(() => {
            const input = document.querySelector('#class-search');
            if (input) input.focus();
        });
    }
};

// Save/Update student
const saveStudent = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeEnrollmentModal();
        },
    };

    const formData = new FormData();
    Object.keys(studentForm).forEach(key => {
        if (key === 'photo' && studentForm.photo instanceof File) {
            formData.append('photo', studentForm.photo);
        } else if (key === 'academic_histories') {
            formData.append('academic_histories', JSON.stringify(studentForm.academic_histories));
        } else if (key === 'additional_courses') {
            formData.append('additional_courses', JSON.stringify(additionalCoursePayload()));
        } else if (key !== 'photo_preview' && key !== 'id') {
            formData.append(key, studentForm[key] ?? '');
        }
    });

    // Add selected units for enrollment
    if (!isEditing.value) {
        formData.append('units', JSON.stringify(selectedUnits.value));
    }

    if (studentForm.id) {
        formData.append('_method', 'PUT');
        router.post(route('students.update', studentForm.id), formData, options);
    } else {
        router.post(route('students.enroll'), formData, options);
    }
};

// Delete student
const destroyStudent = (student) => {
    deletingStudent.value = student;
};

const closeDeleteModal = () => {
    deletingStudent.value = null;
};

const confirmDelete = () => {
    if (!deletingStudent.value) return;

    router.delete(route('students.destroy', deletingStudent.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};

// Add academic history
const addAcademicHistory = () => {
    studentForm.academic_histories.push({
        institution_name: '',
        qualification: '',
        grade: '',
        started_on: '',
        completed_on: '',
        notes: '',
    });
};

const removeAcademicHistory = (index) => {
    studentForm.academic_histories.splice(index, 1);
};

// Export CSV
const exportCsv = () => {
    const rows = [
        ['Admission No.', 'Full Name', 'Department', 'Course', 'Class', 'Status', 'Email', 'Phone', 'Admitted On'],
        ...props.students.data.map((student) => [
            student.admission_number,
            `${student.first_name} ${student.middle_name || ''} ${student.last_name}`.trim(),
            student.department?.name || '',
            student.course?.name || '',
            student.class?.name || '',
            student.status,
            student.email || '',
            student.phone || '',
            student.admitted_on,
        ]),
    ];

    const csv = rows.map(row =>
        row.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(',')
    ).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'students.csv';
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <AppLayout title="Student Management">
        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Students</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ stats.active }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Graduated</p>
                <p class="mt-2 text-3xl font-bold text-blue-400">{{ stats.graduated }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Deferred</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">{{ stats.deferred }}</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 flex flex-col justify-between gap-3 xl:flex-row">
            <div class="flex flex-wrap gap-2">
                <button
                    class="inline-flex h-8 items-center gap-2 rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
                    type="button"
                    @click="exportCsv"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16" />
                    </svg>
                    Export CSV
                </button>
                <button
                    class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400"
                    type="button"
                    @click="openEnrollmentModal"
                >
                    <span class="text-base leading-none">+</span>
                    Enroll Student
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-2 sm:flex-row">
                <select
                    v-model="filter.status"
                    class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300"
                >
                    <option value="">All Status</option>
                    <option v-for="status in statuses" :key="status" :value="status">
                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </option>
                </select>
                <select
                    v-model="filter.course_id"
                    class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300"
                >
                    <option value="">All Courses</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.code }} - {{ course.name }}
                    </option>
                </select>
                <select
                    v-model="filter.class_id"
                    class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300"
                >
                    <option value="">All Classes</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                        {{ cls.code }} - {{ cls.name }}
                    </option>
                </select>
                <input
                    v-model="filter.search"
                    class="h-8 w-48 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600"
                    placeholder="Search by name, admission..."
                />
            </div>
        </div>

        <!-- Student Table -->
        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="students.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Admission</th>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Course</th>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3">Payments</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="student in students.data" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ student.admission_number }}</p>
                            <p class="text-xs text-gray-500">{{ student.admitted_on }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div v-if="student.photo_path" class="size-8 overflow-hidden rounded-full">
                                    <img :src="`/storage/${student.photo_path}`" :alt="student.first_name" class="size-full object-cover">
                                </div>
                                <div v-else class="size-8 rounded-full bg-gray-200 dark:bg-[#2a3040]"></div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ student.first_name }} {{ student.middle_name || '' }} {{ student.last_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ student.email || 'No email' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ student.department?.name || '-' }}</p>
                            <p class="text-xs text-gray-500">{{ student.department?.code || '' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div v-if="student.registered_courses?.length" class="space-y-1">
                                <div v-for="course in student.registered_courses" :key="course.id">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ course.name }}
                                        <span v-if="course.primary" class="text-[10px] font-semibold uppercase text-violet-500">Primary</span>
                                    </p>
                                    <p class="text-xs text-gray-500">{{ course.code }}</p>
                                </div>
                            </div>
                            <template v-else>
                                <p class="font-medium text-gray-700 dark:text-gray-300">{{ student.course?.name || '-' }}</p>
                                <p class="text-xs text-gray-500">{{ student.course?.code || '' }}</p>
                            </template>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                            {{ student.class?.name || 'Not assigned' }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-amber-500">{{ money(student.payment_summary?.remaining) }}</p>
                            <p class="text-xs text-gray-500">Paid {{ money(student.payment_summary?.paid) }} of {{ money(student.payment_summary?.fee) }}</p>
                            <p v-if="student.payment_summary?.courses_count > 1" class="text-[11px] text-gray-400">
                                Across {{ student.payment_summary.courses_count }} courses
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="rounded-md px-2 py-1 text-xs font-semibold"
                                :class="{
                                    'bg-emerald-500/10 text-emerald-400': student.status === 'active',
                                    'bg-blue-500/10 text-blue-400': student.status === 'graduated',
                                    'bg-amber-500/10 text-amber-400': student.status === 'deferred',
                                    'bg-red-500/10 text-red-400': student.status === 'suspended' || student.status === 'expelled',
                                }"
                            >
                                - {{ student.status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button
                                class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300"
                                type="button"
                                @click="editStudent(student)"
                            >
                                Edit
                            </button>
                            <button
                                class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400"
                                type="button"
                                @click="destroyStudent(student)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <div class="inline-flex size-12 items-center justify-center rounded-md bg-gray-100 text-gray-500 dark:bg-[#222738]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <p class="mt-4 font-semibold text-gray-700 dark:text-gray-300">No students found</p>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Enroll a student or adjust the filters to see records.</p>
                <button
                    class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400"
                    type="button"
                    @click="openEnrollmentModal"
                >
                    + Enroll Student
                </button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="students.links" />
            </div>
        </div>

        <!-- Enrollment/Edit Modal -->
        <DialogModal :show="showingEnrollmentModal" max-width="4xl" @close="closeEnrollmentModal">
            <template #title>{{ isEditing ? 'Edit Student' : 'Enroll Student' }}</template>

            <template #content>
                <form id="enrollment-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="saveStudent">
                    <!-- Personal Information with Photo -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Personal Information</h3>
                            <div class="flex items-center gap-3">
                                <div v-if="studentForm.photo_preview" class="flex items-center gap-2">
                                    <img :src="studentForm.photo_preview" alt="Preview" class="size-12 rounded-full object-cover border-2 border-violet-500">
                                    <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="removePhoto">Remove</button>
                                </div>
                                <label class="cursor-pointer rounded-md bg-violet-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-400">
                                    {{ studentForm.photo_preview ? 'Change Photo' : 'Add Photo' }}
                                    <input id="photo-input" type="file" accept="image/*" class="hidden" @change="handlePhotoUpload">
                                </label>
                            </div>
                        </div>
                        <div class="mt-2 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">First Name</label>
                                <input v-model="studentForm.first_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Middle Name</label>
                                <input v-model="studentForm.middle_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Last Name</label>
                                <input v-model="studentForm.last_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gender</label>
                                <select v-model="studentForm.gender" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Date of Birth</label>
                                <input v-model="studentForm.date_of_birth" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</label>
                                <input v-model="studentForm.email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Phone</label>
                                <input v-model="studentForm.phone" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Address</label>
                                <textarea v-model="studentForm.address" rows="2" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Details with Searchable Selects -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Enrollment Details</h3>
                        <div class="mt-2 grid gap-4 md:grid-cols-3">
                            <!-- Department - Searchable Select -->
                            <div ref="departmentDropdown" class="relative">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                                <div
                                    class="mt-1 w-full cursor-pointer rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                    @click="toggleDepartment"
                                >
                                    {{ selectedDepartmentLabel }}
                                    <span class="float-right">▼</span>
                                </div>
                                <div v-if="searchableSelects.department.isOpen" class="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        id="department-search"
                                        v-model="searchableSelects.department.search"
                                        type="text"
                                        class="sticky top-0 w-full border-b border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none dark:border-[#2a3040] dark:bg-[#1a1f2b] dark:text-white"
                                        placeholder="Search department..."
                                        @click.stop
                                    >
                                    <div
                                        v-for="dept in filteredDepartments"
                                        :key="dept.id"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-[#2a3040]"
                                        @click="selectDepartment(dept)"
                                    >
                                        <span class="font-medium">{{ dept.code }}</span>
                                        <span class="text-gray-500"> - {{ dept.name }}</span>
                                    </div>
                                    <div v-if="!filteredDepartments.length" class="px-3 py-2 text-sm text-gray-500">
                                        No departments found
                                    </div>
                                </div>
                            </div>

                            <!-- Course - Searchable Select -->
                            <div ref="courseDropdown" class="relative">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                                <div
                                    class="mt-1 w-full cursor-pointer rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                    @click="toggleCourse"
                                >
                                    {{ selectedCourseLabel }}
                                    <span class="float-right">▼</span>
                                </div>
                                <div v-if="searchableSelects.course.isOpen" class="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        id="course-search"
                                        v-model="searchableSelects.course.search"
                                        type="text"
                                        class="sticky top-0 w-full border-b border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none dark:border-[#2a3040] dark:bg-[#1a1f2b] dark:text-white"
                                        placeholder="Search course..."
                                        @click.stop
                                    >
                                    <div
                                        v-for="course in filteredCourses"
                                        :key="course.id"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-[#2a3040]"
                                        @click="selectCourse(course)"
                                    >
                                        <span class="font-medium">{{ course.code }}</span>
                                        <span class="text-gray-500"> - {{ course.name }}</span>
                                    </div>
                                    <div v-if="!filteredCourses.length" class="px-3 py-2 text-sm text-gray-500">
                                        No courses found
                                    </div>
                                </div>
                            </div>

                            <!-- Class - Searchable Select -->
                            <div ref="classDropdown" class="relative">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Class</label>
                                <div
                                    class="mt-1 w-full cursor-pointer rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                    @click="toggleClass"
                                >
                                    {{ selectedClassLabel }}
                                    <span class="float-right">▼</span>
                                </div>
                                <div v-if="searchableSelects.class.isOpen" class="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        id="class-search"
                                        v-model="searchableSelects.class.search"
                                        type="text"
                                        class="sticky top-0 w-full border-b border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none dark:border-[#2a3040] dark:bg-[#1a1f2b] dark:text-white"
                                        placeholder="Search class..."
                                        @click.stop
                                    >
                                    <div
                                        v-for="cls in filteredClasses"
                                        :key="cls.id"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-[#2a3040]"
                                        @click="selectClass(cls)"
                                    >
                                        <span class="font-medium">{{ cls.code }}</span>
                                        <span class="text-gray-500"> - {{ cls.name }}</span>
                                    </div>
                                    <div v-if="!filteredClasses.length" class="px-3 py-2 text-sm text-gray-500">
                                        No classes found
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Year</label>
                                <select v-model="studentForm.academic_year_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                    <option value="">Select academic year</option>
                                    <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                                </select>
                                <p v-if="studentForm.errors.academic_year_id" class="mt-1 text-xs text-red-400">{{ studentForm.errors.academic_year_id }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Semester</label>
                                <select v-model="studentForm.semester_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                    <option value="">Select semester</option>
                                    <option v-for="semester in filteredSemesters" :key="semester.id" :value="semester.id">{{ semester.name }}</option>
                                </select>
                                <p v-if="studentForm.errors.semester_id" class="mt-1 text-xs text-red-400">{{ studentForm.errors.semester_id }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course Amount</label>
                                <input v-model="studentForm.course_fee" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :placeholder="selectedCourse ? `Default: ${selectedCourse.fees}` : 'Select course first'">
                                <p v-if="studentForm.errors.course_fee" class="mt-1 text-xs text-red-400">{{ studentForm.errors.course_fee }}</p>
                            </div>
                            <div class="md:col-span-3 rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Additional Courses</h4>
                                        <p class="text-xs text-gray-500">Register the same learner in another department or course for this academic year and semester.</p>
                                    </div>
                                    <button type="button" class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-medium text-violet-600 transition hover:border-violet-400 dark:border-[#2a3040] dark:text-violet-300" @click="addAdditionalCourse">
                                        + Add Course
                                    </button>
                                </div>
                                <div v-if="isEditing && currentRegisteredCourseIds.length" class="mt-3 rounded-md bg-gray-50 p-3 text-xs text-gray-500 dark:bg-[#151a25]">
                                    <p class="font-semibold uppercase tracking-wider text-gray-500">Already registered</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="course in studentForm.id ? students.data.find((item) => item.id === studentForm.id)?.registered_courses || [] : []" :key="course.id" class="rounded-md border border-gray-200 px-2 py-1 dark:border-[#2a3040]">
                                            {{ course.code }} - {{ course.name }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="studentForm.additional_courses.length" class="mt-3 space-y-3">
                                    <div v-for="(courseRegistration, index) in studentForm.additional_courses" :key="index" class="grid gap-3 rounded-md bg-gray-50 p-3 md:grid-cols-4 dark:bg-[#151a25]">
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</label>
                                            <select v-model="courseRegistration.department_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="handleAdditionalDepartmentChange(courseRegistration)">
                                                <option value="">Select department</option>
                                                <option v-for="department in departments" :key="department.id" :value="department.id">
                                                    {{ department.code }} - {{ department.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course</label>
                                            <select v-model="courseRegistration.course_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!courseRegistration.department_id" required @change="handleAdditionalCourseChange(courseRegistration)">
                                                <option value="">{{ courseRegistration.department_id ? 'Select course' : 'Select department first' }}</option>
                                                <option
                                                    v-for="course in coursesForDepartment(courseRegistration.department_id)"
                                                    :key="course.id"
                                                    :value="course.id"
                                                    :disabled="unavailableAdditionalCourseIds.includes(Number(course.id)) && Number(course.id) !== Number(courseRegistration.course_id)"
                                                >
                                                    {{ course.code }} - {{ course.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Class</label>
                                            <select v-model="courseRegistration.class_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!courseRegistration.course_id" required>
                                                <option value="">{{ courseRegistration.course_id ? 'Select class' : 'Select course first' }}</option>
                                                <option v-for="cls in classesForCourse(courseRegistration.course_id)" :key="cls.id" :value="cls.id">
                                                    {{ cls.code }} - {{ cls.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Course Amount</label>
                                            <div class="mt-1 flex gap-2">
                                                <input v-model="courseRegistration.course_fee" type="number" min="0" step="0.01" class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :placeholder="courseRegistration.course_id ? `Default: ${courseFee(courseRegistration.course_id)}` : 'Select course first'">
                                                <button type="button" class="rounded-md border border-red-500/30 px-2.5 text-xs text-red-400 transition hover:border-red-400" @click="removeAdditionalCourse(index)">Remove</button>
                                            </div>
                                        </div>
                                        <div class="md:col-span-4">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Units</label>
                                                <span class="text-xs text-gray-500">{{ courseRegistration.units?.length || 0 }} selected</span>
                                            </div>
                                            <div v-if="courseRegistration.loading_units" class="mt-2 text-xs text-gray-500">Loading units...</div>
                                            <div v-else-if="courseRegistration.available_units?.length" class="mt-2 grid gap-2 md:grid-cols-2">
                                                <label v-for="unit in courseRegistration.available_units" :key="unit.id" class="flex items-center gap-2 text-sm">
                                                    <input
                                                        v-model="courseRegistration.units"
                                                        type="checkbox"
                                                        :value="unit.id"
                                                        class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500"
                                                    >
                                                    <span class="font-medium">{{ unit.code }}</span>
                                                    <span class="text-gray-500">{{ unit.name }}</span>
                                                    <span class="text-xs text-gray-400">({{ unit.credit_hours }} hrs)</span>
                                                </label>
                                            </div>
                                            <p v-else class="mt-2 text-xs text-gray-500">
                                                {{ courseRegistration.course_id ? 'No active units found for this course.' : 'Select a course to load units.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Admission Number</label>
                                <input v-model="studentForm.admission_number" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Auto-generate if empty">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Admitted On</label>
                                <input v-model="studentForm.admitted_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                                <select v-model="studentForm.status" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                    <option v-for="status in statuses" :key="status" :value="status">
                                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Course Units -->
                    <div v-if="courseUnits.length && !isEditing" class="md:col-span-2">
                        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Course Units</h3>
                        <p class="text-xs text-gray-500">Select the units the student will be enrolled in (default: all units)</p>
                        <div v-if="loadingUnits" class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" />
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-linecap="round" />
                            </svg>
                            Loading units...
                        </div>
                        <div v-else class="mt-2 grid gap-2 md:grid-cols-2">
                            <label v-for="unit in courseUnits" :key="unit.id" class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="selectedUnits"
                                    type="checkbox"
                                    :value="unit.id"
                                    class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500"
                                >
                                <span class="font-medium">{{ unit.code }}</span>
                                <span class="text-gray-500">{{ unit.name }}</span>
                                <span class="text-xs text-gray-400">({{ unit.credit_hours }} hrs)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Guardian Information -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Guardian Information</h3>
                            <button type="button" class="text-xs text-violet-500 hover:text-violet-400" @click="showingGuardianInformation = !showingGuardianInformation">
                                {{ showingGuardianInformation ? 'Minimize' : 'Expand' }}
                            </button>
                        </div>
                        <div v-if="showingGuardianInformation" class="mt-2 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Guardian Name</label>
                                <input v-model="studentForm.guardian_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Relationship</label>
                                <input v-model="studentForm.guardian_relationship" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Guardian Phone</label>
                                <input v-model="studentForm.guardian_phone" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Guardian Email</label>
                                <input v-model="studentForm.guardian_email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Guardian Address</label>
                                <textarea v-model="studentForm.guardian_address" rows="2" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Academic History -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Academic History</h3>
                            <button type="button" class="text-xs text-violet-500 hover:text-violet-400" @click="addAcademicHistory">+ Add</button>
                        </div>
                        <div v-for="(history, index) in studentForm.academic_histories" :key="index" class="mt-2 rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Institution</label>
                                    <input v-model="history.institution_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Qualification</label>
                                    <input v-model="history.qualification" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grade</label>
                                    <input v-model="history.grade" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Started On</label>
                                    <input v-model="history.started_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Completed On</label>
                                    <input v-model="history.completed_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="removeAcademicHistory(index)">Remove</button>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</label>
                                    <input v-model="history.notes" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeEnrollmentModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="enrollment-form" type="submit" :disabled="studentForm.processing">
                    {{ studentForm.processing ? 'Saving...' : (isEditing ? 'Update Student' : 'Enroll Student') }}
                </button>
            </template>
        </DialogModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="Boolean(deletingStudent)" max-width="md" @close="closeDeleteModal">
            <template #title>Delete Student</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingStudent?.admission_number }} - {{ deletingStudent?.first_name }} {{ deletingStudent?.last_name }}</span>?
                </p>
                <p class="mt-2">This removes the student from active academic records.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDelete">Delete Student</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
