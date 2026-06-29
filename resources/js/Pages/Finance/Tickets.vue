<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    canAdd: Boolean,
    canEdit: Boolean,
    canDelete: Boolean,
    canManageRules: Boolean,
    canCheckIn: Boolean,
    isLearner: Boolean,
    authStudentId: Number,
    filters: Object,
    ticketDate: String,
    courses: Array,
    units: Array,
    students: Array,
    rules: Object,
    ticketRules: Array,
    tickets: Object,
    summary: Object,
    recentCheckins: Array,
});

const filter = reactive({
    search: props.filters.search || '',
    course_id: props.filters.course_id || '',
    student_id: props.filters.student_id || '',
    status: props.filters.status || '',
});

const ruleForm = useForm({
    id: null,
    target_type: 'course',
    course_id: '',
    unit_id: '',
    name: '',
    session_type: 'theoretical',
    lesson_count: 1,
    lessons_per_ticket: 1,
    pricing_type: 'fixed_amount',
    pricing_value: '',
    description: '',
    is_active: true,
});

const ticketForm = useForm({
    student_id: props.authStudentId || '',
    target_key: '',
    issued_on: props.ticketDate,
    notes: '',
});

const showingRuleModal = ref(false);
const showingTicketModal = ref(false);
const deletingRule = ref(null);
const activeTab = ref('tickets');
const scanInput = ref(null);
const cameraVideo = ref(null);
const recentCheckins = ref(props.recentCheckins || []);
const scan = reactive({
    value: '',
    processing: false,
    result: null,
});
const camera = reactive({
    active: false,
    starting: false,
    error: '',
});
const courseSelect = reactive({ isOpen: false, search: '' });
const studentSelect = reactive({ isOpen: false, search: '' });
let cameraStream = null;
let cameraFrame = null;
let qrDetector = null;
let lastCameraScan = '';
let lastCameraScanAt = 0;

const money = (value) => `KES ${Number(value || 0).toLocaleString()}`;
const sentence = (value) => String(value || '').replaceAll('_', ' ');
const courseLabel = (course) => course ? `${course.code} - ${course.name}` : '';
const unitLabel = (unit) => unit ? `${unit.code} - ${unit.name}` : '';

const selectedCourse = computed(() => props.courses.find((course) => Number(course.id) === Number(ruleForm.course_id)));
const selectedUnit = computed(() => props.units.find((unit) => Number(unit.id) === Number(ruleForm.unit_id)));
const selectedStudent = computed(() => props.students.find((student) => Number(student.id) === Number(ticketForm.student_id)));
const selectedStudentLabel = computed(() => selectedStudent.value ? `${selectedStudent.value.admission_number} - ${selectedStudent.value.name}` : 'Select learner');
const selectedRuleTargetLabel = computed(() => {
    if (ruleForm.target_type === 'unit') return selectedUnit.value ? unitLabel(selectedUnit.value) : 'Select unit';

    return selectedCourse.value ? courseLabel(selectedCourse.value) : (ruleForm.target_type === 'subcourse' ? 'Select subcourse' : 'Select course');
});
const studentTargets = computed(() => selectedStudent.value?.ticket_targets || []);
const selectedTarget = computed(() => studentTargets.value.find((target) => target.key === ticketForm.target_key) || (studentTargets.value.length === 1 ? studentTargets.value[0] : null));
const needsTargetChoice = computed(() => studentTargets.value.length > 1);
const canIssueSelectedTarget = computed(() => selectedTarget.value && Number(selectedTarget.value.paid || 0) >= Number(selectedTarget.value.required_total || 0));

const filteredRuleTargets = computed(() => {
    const search = courseSelect.search.toLowerCase();
    const items = ruleForm.target_type === 'unit'
        ? props.units
        : props.courses.filter((course) => ruleForm.target_type === 'subcourse' ? course.parent_course_id : !course.parent_course_id);

    return items.filter((item) => !search || [item.code, item.name, item.course?.name, item.parent_course?.name]
        .some((value) => String(value || '').toLowerCase().includes(search)));
});

const filteredStudents = computed(() => {
    const search = studentSelect.search.toLowerCase();

    return props.students.filter((student) => !search || [student.admission_number, student.name]
        .some((value) => String(value || '').toLowerCase().includes(search)));
});

const stats = computed(() => [
    { label: 'Active Rules', value: props.summary.active_rules },
    { label: 'Issued Tickets', value: props.summary.issued_tickets },
    { label: 'Downloaded', value: props.summary.downloaded_tickets },
    { label: 'Checked In Today', value: props.summary.checked_in_today },
]);

watch(filter, () => router.get(route('finance.tickets.index'), filter, { preserveState: true, replace: true }), { deep: true });

watch(() => ticketForm.student_id, () => {
    ticketForm.target_key = studentTargets.value.length === 1 ? studentTargets.value[0].key : '';
});

watch(activeTab, (tab) => {
    if (tab !== 'checkin') stopCamera();
});

const resetRuleForm = () => {
    ruleForm.clearErrors();
    ruleForm.id = null;
    ruleForm.target_type = 'course';
    ruleForm.course_id = '';
    ruleForm.unit_id = '';
    ruleForm.name = '';
    ruleForm.session_type = 'theoretical';
    ruleForm.lesson_count = 1;
    ruleForm.lessons_per_ticket = 1;
    ruleForm.pricing_type = 'fixed_amount';
    ruleForm.pricing_value = '';
    ruleForm.description = '';
    ruleForm.is_active = true;
    courseSelect.search = '';
    courseSelect.isOpen = false;
};

const openRuleModal = () => {
    resetRuleForm();
    showingRuleModal.value = true;
};

const editRule = (rule) => {
    resetRuleForm();
    ruleForm.id = rule.id;
    ruleForm.target_type = rule.target_type || 'course';
    ruleForm.course_id = rule.course_id;
    ruleForm.unit_id = rule.unit_id || '';
    ruleForm.name = rule.name;
    ruleForm.session_type = rule.session_type;
    ruleForm.lesson_count = rule.lesson_count;
    ruleForm.lessons_per_ticket = rule.lessons_per_ticket;
    ruleForm.pricing_type = rule.pricing_type;
    ruleForm.pricing_value = rule.pricing_value;
    ruleForm.description = rule.description || '';
    ruleForm.is_active = Boolean(rule.is_active);
    showingRuleModal.value = true;
};

const closeRuleModal = () => {
    showingRuleModal.value = false;
    resetRuleForm();
};

const saveRule = () => {
    const options = { preserveScroll: true, onSuccess: closeRuleModal };
    ruleForm.id ? ruleForm.put(route('finance.tickets.rules.update', ruleForm.id), options) : ruleForm.post(route('finance.tickets.rules.store'), options);
};

const resetTicketForm = () => {
    ticketForm.clearErrors();
    ticketForm.student_id = props.authStudentId || '';
    ticketForm.target_key = '';
    ticketForm.issued_on = props.ticketDate;
    ticketForm.notes = '';
    studentSelect.search = '';
    studentSelect.isOpen = false;
};

const openTicketModal = () => {
    resetTicketForm();
    if (props.authStudentId) {
        ticketForm.student_id = props.authStudentId;
        ticketForm.target_key = studentTargets.value.length === 1 ? studentTargets.value[0].key : '';
    }
    showingTicketModal.value = true;
};

const closeTicketModal = () => {
    showingTicketModal.value = false;
    resetTicketForm();
};

const issueTicket = () => {
    ticketForm.post(route('finance.tickets.issue'), {
        preserveScroll: true,
        onSuccess: closeTicketModal,
    });
};

const toggleCourseSelect = () => {
    courseSelect.isOpen = !courseSelect.isOpen;
    if (courseSelect.isOpen) courseSelect.search = '';
};

const selectCourse = (course) => {
    if (ruleForm.target_type === 'unit') {
        ruleForm.unit_id = course.id;
        ruleForm.course_id = course.course_id;
    } else {
        ruleForm.course_id = course.id;
        ruleForm.unit_id = '';
    }

    courseSelect.isOpen = false;
    courseSelect.search = '';
};

const changeRuleTargetType = () => {
    ruleForm.course_id = '';
    ruleForm.unit_id = '';
    courseSelect.search = '';
    courseSelect.isOpen = false;
};

const toggleStudentSelect = () => {
    studentSelect.isOpen = !studentSelect.isOpen;
    if (studentSelect.isOpen) studentSelect.search = '';
};

const selectStudent = (student) => {
    ticketForm.student_id = student.id;
    studentSelect.isOpen = false;
    studentSelect.search = '';
};

const destroyRule = (rule) => {
    deletingRule.value = rule;
};

const closeDeleteRuleModal = () => {
    deletingRule.value = null;
};

const confirmDeleteRule = () => {
    if (!deletingRule.value) return;

    router.delete(route('finance.tickets.rules.destroy', deletingRule.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteRuleModal,
    });
};

const focusScanner = () => {
    requestAnimationFrame(() => scanInput.value?.focus());
};

const checkInTicket = async (fromCamera = false) => {
    if (!scan.value || scan.processing) return;

    scan.processing = true;
    scan.result = null;

    try {
        const response = await axios.post(route('finance.tickets.check-in'), { scan: scan.value });
        scan.result = response.data;
        recentCheckins.value = response.data.recentCheckins || recentCheckins.value;
    } catch (error) {
        scan.result = error.response?.data || { ok: false, message: 'Check-in failed.' };
    } finally {
        scan.value = '';
        scan.processing = false;
        if (!fromCamera) focusScanner();
    }
};

const stopCamera = () => {
    if (cameraFrame) cancelAnimationFrame(cameraFrame);
    cameraFrame = null;

    if (cameraStream) {
        cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = null;
    }

    if (cameraVideo.value) cameraVideo.value.srcObject = null;
    camera.active = false;
    camera.starting = false;
};

const scanCameraFrame = async () => {
    if (!camera.active || !cameraVideo.value || !qrDetector) return;

    try {
        if (cameraVideo.value.readyState >= 2 && !scan.processing) {
            const codes = await qrDetector.detect(cameraVideo.value);
            const value = codes[0]?.rawValue;
            const now = Date.now();

            if (value && (value !== lastCameraScan || now - lastCameraScanAt > 3000)) {
                lastCameraScan = value;
                lastCameraScanAt = now;
                scan.value = value;
                await checkInTicket(true);
            }
        }
    } catch (error) {
        camera.error = 'Camera scanner could not read this frame.';
    }

    cameraFrame = requestAnimationFrame(scanCameraFrame);
};

const startCamera = async () => {
    camera.error = '';

    if (!('BarcodeDetector' in window)) {
        camera.error = 'Camera QR scanning is not supported by this browser. Use Chrome or Edge, or use the scan input.';
        focusScanner();
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        camera.error = 'Camera access is not available in this browser.';
        focusScanner();
        return;
    }

    camera.starting = true;

    try {
        qrDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        });
        cameraVideo.value.srcObject = cameraStream;
        await cameraVideo.value.play();
        camera.active = true;
        scanCameraFrame();
    } catch (error) {
        stopCamera();
        camera.error = error?.name === 'NotAllowedError'
            ? 'Camera permission was denied.'
            : 'Could not start the camera scanner.';
    } finally {
        camera.starting = false;
    }
};

onBeforeUnmount(stopCamera);
</script>

<template>
    <AppLayout title="Lesson Tickets">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Access Control</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lesson Tickets</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <button v-if="canManageRules" class="inline-flex h-9 items-center gap-2 rounded-md border border-gray-200 px-3 text-sm font-semibold text-gray-600 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-300" type="button" @click="openRuleModal">
                    <span class="text-base leading-none">+</span>
                    Rule
                </button>
                <button v-if="canAdd" class="inline-flex h-9 items-center gap-2 rounded-md bg-violet-500 px-3 text-sm font-semibold text-white transition hover:bg-violet-400" type="button" @click="openTicketModal">
                    <span class="text-base leading-none">+</span>
                    {{ isLearner ? 'Generate Ticket' : 'Ticket' }}
                </button>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-4">
            <div v-for="item in stats" :key="item.label" class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs uppercase tracking-wider text-gray-500">{{ item.label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ item.value }}</p>
            </div>
        </div>

        <div class="mt-4 rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
            <div class="grid gap-2 md:grid-cols-4">
                <input v-model="filter.search" class="h-9 rounded-md border-gray-200 bg-white text-sm text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-gray-300" placeholder="Search tickets, learners, courses">
                <select v-model="filter.course_id" class="h-9 rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-gray-300">
                    <option value="">All courses</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ courseLabel(course) }}</option>
                </select>
                <select v-if="!isLearner" v-model="filter.student_id" class="h-9 rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-gray-300">
                    <option value="">All learners</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.admission_number }} - {{ student.name }}</option>
                </select>
                <select v-model="filter.status" class="h-9 rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-gray-300">
                    <option value="">All statuses</option>
                    <option value="issued">Issued</option>
                    <option value="downloaded">Downloaded</option>
                    <option value="checked_in">Checked in</option>
                </select>
            </div>
        </div>

        <div class="mt-4 border-b border-gray-200 dark:border-[#273044]">
            <div class="flex gap-1">
                <button
                    type="button"
                    class="rounded-t-md px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'tickets' ? 'bg-white text-violet-700 shadow-sm dark:bg-[#11141b] dark:text-violet-300' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    @click="activeTab = 'tickets'"
                >
                    Learner Tickets
                </button>
                <button
                    v-if="canCheckIn"
                    type="button"
                    class="rounded-t-md px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'checkin' ? 'bg-white text-violet-700 shadow-sm dark:bg-[#11141b] dark:text-violet-300' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    @click="activeTab = 'checkin'; focusScanner()"
                >
                    Check-in
                </button>
                <button
                    v-if="canManageRules"
                    type="button"
                    class="rounded-t-md px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'rules' ? 'bg-white text-violet-700 shadow-sm dark:bg-[#11141b] dark:text-violet-300' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    @click="activeTab = 'rules'"
                >
                    Ticket Rules
                </button>
            </div>
        </div>

        <div v-if="activeTab === 'tickets'" class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Issued</p>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Learner Tickets</h2>
            </div>

            <table v-if="tickets.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Ticket</th>
                        <th class="px-5 py-3">Learner</th>
                        <th class="px-5 py-3">Target</th>
                        <th class="px-5 py-3">Session</th>
                        <th class="px-5 py-3">Paid</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="ticket in tickets.data" :key="ticket.id">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ ticket.ticket_number }}</p>
                            <p class="text-xs text-gray-500">{{ ticket.issued_on }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 dark:text-white">{{ ticket.student?.first_name }} {{ ticket.student?.last_name }}</p>
                            <p class="text-xs text-gray-500">{{ ticket.student?.admission_number }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-gray-600 dark:text-gray-300">{{ ticket.target_label }}</p>
                            <p class="text-xs capitalize text-gray-500">{{ ticket.target_type }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ sentence(ticket.session_type) }} / {{ ticket.lesson_count }} lessons</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ money(ticket.amount_required) }}</p>
                            <p class="text-xs text-gray-500">Paid {{ money(ticket.amount_paid) }}</p>
                        </td>
                        <td class="px-5 py-4 capitalize text-gray-600 dark:text-gray-300">{{ ticket.status }}</td>
                        <td class="px-5 py-4 text-right">
                            <a class="rounded-md border border-violet-500/30 px-2.5 py-1.5 text-xs text-violet-600 transition hover:border-violet-400 dark:text-violet-300" :href="route('finance.tickets.download', ticket.id)" target="_blank">PDF</a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[180px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No learner tickets found</p>
                <button v-if="canAdd" class="mt-4 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openTicketModal">+ {{ isLearner ? 'Generate Ticket' : 'Issue Ticket' }}</button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="tickets.links" />
            </div>
        </div>

        <div v-if="activeTab === 'checkin' && canCheckIn" class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Scanner</p>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ticket Check-in</h2>
            </div>

            <div class="grid gap-4 p-5 lg:grid-cols-[1fr_420px]">
                <form class="space-y-3" @submit.prevent="checkInTicket(false)">
                    <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-950 dark:border-[#2a3040]">
                        <video
                            ref="cameraVideo"
                            class="aspect-video w-full bg-gray-950 object-cover"
                            muted
                            playsinline
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="!camera.active"
                            class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50"
                            type="button"
                            :disabled="camera.starting"
                            @click="startCamera"
                        >
                            {{ camera.starting ? 'Starting...' : 'Start camera' }}
                        </button>
                        <button
                            v-else
                            class="rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-300"
                            type="button"
                            @click="stopCamera"
                        >
                            Stop camera
                        </button>
                    </div>

                    <p v-if="camera.error" class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ camera.error }}
                    </p>

                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Scan ticket QR</label>
                    <input
                        ref="scanInput"
                        v-model="scan.value"
                        class="h-14 w-full rounded-md border-gray-200 bg-white text-lg font-semibold text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                        placeholder="Scan or paste ticket code"
                        autocomplete="off"
                    >
                    <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" type="submit" :disabled="scan.processing">
                        {{ scan.processing ? 'Checking...' : 'Check in' }}
                    </button>

                    <div v-if="scan.result" class="rounded-md border p-4" :class="scan.result.ok ? (scan.result.duplicate ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200' : 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200') : 'border-red-300 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200'">
                        <p class="text-lg font-bold">{{ scan.result.message }}</p>
                        <div v-if="scan.result.ticket" class="mt-2 text-sm">
                            <p>{{ scan.result.ticket.student?.admission_number }} - {{ scan.result.ticket.student?.name }}</p>
                            <p>{{ scan.result.ticket.target_label }}</p>
                            <p class="font-mono text-xs">{{ scan.result.ticket.ticket_number }}</p>
                        </div>
                    </div>
                </form>

                <div class="rounded-md border border-gray-200 dark:border-[#2a3040]">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-[#2a3040]">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Recent Check-ins</p>
                    </div>
                    <div class="max-h-[420px] divide-y divide-gray-100 overflow-auto dark:divide-[#1a1f2b]">
                        <div v-for="item in recentCheckins" :key="`${item.id}-${item.checked_in_at}`" class="px-4 py-3 text-sm">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ item.student?.admission_number }} - {{ item.student?.name }}</p>
                            <p class="text-xs text-gray-500">{{ item.target_label }}</p>
                            <p class="mt-1 font-mono text-[11px] text-gray-500">{{ item.ticket_number }} | {{ item.checked_in_at }}</p>
                        </div>
                        <p v-if="!recentCheckins.length" class="px-4 py-6 text-center text-sm text-gray-500">No check-ins yet</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'rules' && canManageRules" class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Rules</p>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ticket Rules</h2>
                </div>
            </div>

            <table v-if="rules.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Rule</th>
                        <th class="px-5 py-3">Target</th>
                        <th class="px-5 py-3">Session</th>
                        <th class="px-5 py-3">Lessons</th>
                        <th class="px-5 py-3">Price</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="rule in rules.data" :key="rule.id">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ rule.name }}</p>
                            <p class="text-xs text-gray-500">{{ rule.tickets_count }} issued</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-gray-600 dark:text-gray-300">{{ rule.target_label }}</p>
                            <p class="text-xs capitalize text-gray-500">{{ rule.target_type }}</p>
                        </td>
                        <td class="px-5 py-4 capitalize text-gray-600 dark:text-gray-300">{{ rule.session_type }}</td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ rule.lessons_per_ticket }} of {{ rule.lesson_count }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ money(rule.ticket_price) }}</p>
                            <p class="text-xs text-gray-500">{{ rule.pricing_type === 'percentage' ? `${rule.pricing_value}% of course fee` : 'Fixed amount' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="rule.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-[#252b3a]'">
                                {{ rule.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button v-if="canEdit" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editRule(rule)">Edit</button>
                            <button v-if="canDelete" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400" type="button" @click="destroyRule(rule)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[180px] flex-col items-center justify-center px-6 text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300">No ticket rules found</p>
                <button v-if="canManageRules" class="mt-4 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openRuleModal">+ Add Rule</button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="rules.links" />
            </div>
        </div>

        <DialogModal :show="showingRuleModal && canManageRules" max-width="2xl" @close="closeRuleModal">
            <template #title>{{ ruleForm.id ? 'Edit ticket rule' : 'Add ticket rule' }}</template>
            <template #content>
                <form id="ticket-rule-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="saveRule">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rule applies to</label>
                        <select v-model="ruleForm.target_type" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required @change="changeRuleTargetType">
                            <option value="course">Course</option>
                            <option value="subcourse">Subcourse</option>
                            <option value="unit">Unit</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ ruleForm.target_type === 'unit' ? 'Unit' : (ruleForm.target_type === 'subcourse' ? 'Subcourse' : 'Course') }}</label>
                        <div class="relative mt-1">
                            <button type="button" class="flex w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" @click="toggleCourseSelect">
                                <span class="truncate">{{ selectedRuleTargetLabel }}</span>
                                <span class="text-xs text-gray-400">v</span>
                            </button>
                            <div v-if="courseSelect.isOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                <div class="sticky top-0 border-b border-gray-200 bg-white p-2 dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input v-model="courseSelect.search" class="w-full rounded-md border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :placeholder="`Search ${ruleForm.target_type}`">
                                </div>
                                <button v-for="target in filteredRuleTargets" :key="`${ruleForm.target_type}-${target.id}`" type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#252b3a]" :class="Number(ruleForm.target_type === 'unit' ? ruleForm.unit_id : ruleForm.course_id) === Number(target.id) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300'" @click="selectCourse(target)">
                                    <span class="block font-semibold">{{ target.code }}</span>
                                    <span class="text-xs text-gray-500">{{ target.name }}</span>
                                    <span v-if="ruleForm.target_type === 'unit'" class="block text-[11px] text-gray-500">{{ courseLabel(target.course) }}</span>
                                </button>
                                <div v-if="!filteredRuleTargets.length" class="px-3 py-2 text-sm text-gray-500">No targets found</div>
                            </div>
                        </div>
                        <p v-if="ruleForm.errors.course_id" class="mt-1 text-xs text-red-400">{{ ruleForm.errors.course_id }}</p>
                        <p v-if="ruleForm.errors.unit_id" class="mt-1 text-xs text-red-400">{{ ruleForm.errors.unit_id }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rule name</label>
                        <input v-model="ruleForm.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="ruleForm.errors.name" class="mt-1 text-xs text-red-400">{{ ruleForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Session</label>
                        <select v-model="ruleForm.session_type" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="theoretical">Theoretical</option>
                            <option value="practical">Practical</option>
                            <option value="combined">Combined</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pricing rule</label>
                        <select v-model="ruleForm.pricing_type" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="fixed_amount">Amount</option>
                            <option value="percentage">Percentage of course fee</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total lessons</label>
                        <input v-model="ruleForm.lesson_count" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lessons per ticket</label>
                        <input v-model="ruleForm.lessons_per_ticket" type="number" min="1" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ ruleForm.pricing_type === 'percentage' ? 'Percentage' : 'Amount' }}</label>
                        <input v-model="ruleForm.pricing_value" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <label class="flex items-center gap-2 pt-6 text-sm">
                        <input v-model="ruleForm.is_active" type="checkbox" class="rounded border-[#2a3040] bg-[#090c11] text-violet-500 focus:ring-violet-500">
                        Active rule
                    </label>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Description</label>
                        <textarea v-model="ruleForm.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeRuleModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="ticket-rule-form" type="submit" :disabled="ruleForm.processing">
                    {{ ruleForm.processing ? 'Saving...' : 'Save rule' }}
                </button>
            </template>
        </DialogModal>

        <DialogModal :show="showingTicketModal" max-width="2xl" @close="closeTicketModal">
            <template #title>{{ isLearner ? 'Generate lesson ticket' : 'Issue lesson ticket' }}</template>
            <template #content>
                <form id="lesson-ticket-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="issueTicket">
                    <div v-if="!isLearner" class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Learner</label>
                        <div class="relative mt-1">
                            <button type="button" class="flex w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" @click="toggleStudentSelect">
                                <span class="truncate">{{ selectedStudentLabel }}</span>
                                <span class="text-xs text-gray-400">v</span>
                            </button>
                            <div v-if="studentSelect.isOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                <div class="sticky top-0 border-b border-gray-200 bg-white p-2 dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input v-model="studentSelect.search" class="w-full rounded-md border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Search admission no. or name">
                                </div>
                                <button v-for="student in filteredStudents" :key="student.id" type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#252b3a]" :class="Number(ticketForm.student_id) === Number(student.id) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300'" @click="selectStudent(student)">
                                    <span class="block font-semibold">{{ student.admission_number }}</span>
                                    <span class="text-xs text-gray-500">{{ student.name }}</span>
                                </button>
                                <div v-if="!filteredStudents.length" class="px-3 py-2 text-sm text-gray-500">No learners found</div>
                            </div>
                        </div>
                        <p v-if="ticketForm.errors.student_id" class="mt-1 text-xs text-red-400">{{ ticketForm.errors.student_id }}</p>
                    </div>
                    <div v-else class="md:col-span-2 rounded-md border border-gray-200 p-3 text-sm dark:border-[#2a3040]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Learner</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ selectedStudentLabel }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ticket target</label>
                        <select v-model="ticketForm.target_key" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!ticketForm.student_id || !needsTargetChoice" :required="needsTargetChoice">
                            <option value="">{{ ticketForm.student_id ? (needsTargetChoice ? 'Select course, subcourse, or unit' : 'Automatically selected') : 'Select learner first' }}</option>
                            <option v-for="target in studentTargets" :key="target.key" :value="target.key">{{ target.label }} - {{ target.rules_count }} rule{{ target.rules_count === 1 ? '' : 's' }}</option>
                        </select>
                        <p v-if="ticketForm.errors.target_key" class="mt-1 text-xs text-red-400">{{ ticketForm.errors.target_key }}</p>
                    </div>
                    <div v-if="selectedTarget" class="grid gap-2 rounded-md border border-gray-200 p-3 text-xs md:col-span-2 md:grid-cols-3 dark:border-[#2a3040]">
                        <div>
                            <p class="uppercase tracking-wider text-gray-500">Required</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ money(selectedTarget.required_total) }}</p>
                        </div>
                        <div>
                            <p class="uppercase tracking-wider text-gray-500">Paid</p>
                            <p class="mt-1 font-semibold" :class="canIssueSelectedTarget ? 'text-emerald-500' : 'text-amber-500'">{{ money(selectedTarget.paid) }}</p>
                        </div>
                        <div>
                            <p class="uppercase tracking-wider text-gray-500">Rules</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ selectedTarget.rules_count }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Issued on</label>
                        <input v-model="ticketForm.issued_on" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</label>
                        <textarea v-model="ticketForm.notes" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeTicketModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="lesson-ticket-form" type="submit" :disabled="ticketForm.processing">
                    {{ ticketForm.processing ? 'Saving...' : (isLearner ? 'Generate ticket' : 'Issue ticket') }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingRule)" max-width="md" @close="closeDeleteRuleModal">
            <template #title>Delete ticket rule</template>
            <template #content>
                <p>
                    Delete <span class="font-semibold text-gray-900 dark:text-white">{{ deletingRule?.name }}</span>?
                </p>
                <p class="mt-2">This removes the rule from active ticket setup.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeleteRuleModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeleteRule">Delete rule</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
