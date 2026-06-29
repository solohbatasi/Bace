<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    canManage: Boolean,
    canAdd: Boolean,
    canEdit: Boolean,
    canDelete: Boolean,
    filters: Object,
    paymentDate: String,
    students: Array,
    payments: Object,
    summary: Object,
});

const page = usePage();
const organisation = computed(() => page.props.organisation || {});

const filter = reactive({
    search: props.filters.search || '',
    student_id: props.filters.student_id || '',
    course_id: props.filters.course_id || '',
});

const paymentForm = useForm({
    id: null,
    student_id: '',
    course_id: '',
    payment_reference: '',
    payment_date: props.paymentDate,
    method: 'cash',
    amount: '',
    allocations: [],
    status: 'confirmed',
    notes: '',
});

const showingPaymentModal = ref(false);
const deletingPayment = ref(null);
const studentSelect = reactive({
    isOpen: false,
    search: '',
});

const selectedStudent = computed(() => props.students.find((student) => Number(student.id) === Number(paymentForm.student_id)));
const selectedCourse = computed(() => selectedStudent.value?.courses.find((course) => Number(course.id) === Number(paymentForm.course_id)));
const canSplitPayment = computed(() => !paymentForm.id && (selectedStudent.value?.courses.length || 0) > 1);
const allocationTotal = computed(() => paymentForm.allocations.reduce((total, allocation) => total + Number(allocation.amount || 0), 0));
const allocationRemaining = computed(() => Number(paymentForm.amount || 0) - allocationTotal.value);
const selectedStudentLabel = computed(() => selectedStudent.value ? `${selectedStudent.value.admission_number} - ${selectedStudent.value.name}` : 'Select learner');
const filteredStudents = computed(() => {
    const search = studentSelect.search.toLowerCase();

    return props.students.filter((student) => [
        student.admission_number,
        student.name,
    ].some((value) => String(value || '').toLowerCase().includes(search)));
});
const filterCourses = computed(() => {
    if (!filter.student_id) {
        return props.students
            .flatMap((student) => student.courses)
            .filter((course, index, courses) => courses.findIndex((item) => item.id === course.id) === index);
    }

    return props.students.find((student) => Number(student.id) === Number(filter.student_id))?.courses || [];
});

const stats = computed(() => ({
    totalPaid: props.summary.total_paid,
    todayPaid: props.summary.today_paid,
    pending: props.payments.data.filter((payment) => payment.status === 'pending').length,
    records: props.summary.payments_count,
}));

const money = (value) => `KES ${Number(value || 0).toLocaleString()}`;
const paymentTargetLabel = (course) => {
    if (!course) return 'Not assigned';

    return course.type === 'subcourse' || course.parent_course_id
        ? `${course.code} - ${course.name} (Subcourse)`
        : `${course.code} - ${course.name}`;
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

const paymentCourseSummary = (payment) => {
    const student = props.students.find((item) => Number(item.id) === Number(payment.student_id));
    const course = student?.courses.find((item) => Number(item.id) === Number(payment.course_id));

    return {
        paid: course?.paid ?? payment.amount,
        balance: course?.balance ?? 0,
    };
};

watch(filter, () => router.get(route('finance.payments.index'), filter, { preserveState: true, replace: true }), { deep: true });

watch(() => paymentForm.student_id, () => {
    paymentForm.course_id = '';
    paymentForm.amount = '';
    paymentForm.allocations = selectedStudent.value?.courses.map((course) => ({
        course_id: course.id,
        amount: '',
    })) || [];
});

watch(() => paymentForm.course_id, () => {
    if (selectedCourse.value && !paymentForm.id && !canSplitPayment.value) {
        paymentForm.amount = selectedCourse.value.balance || selectedCourse.value.fees || '';
    }
});

watch(() => filter.student_id, () => {
    filter.course_id = '';
});

const resetPaymentForm = () => {
    paymentForm.clearErrors();
    paymentForm.id = null;
    paymentForm.student_id = '';
    paymentForm.course_id = '';
    paymentForm.payment_reference = '';
    paymentForm.payment_date = props.paymentDate;
    paymentForm.method = 'cash';
    paymentForm.amount = '';
    paymentForm.allocations = [];
    paymentForm.status = 'confirmed';
    paymentForm.notes = '';
    studentSelect.search = '';
    studentSelect.isOpen = false;
};

const openPaymentModal = () => {
    resetPaymentForm();
    showingPaymentModal.value = true;
};

const editPayment = async (payment) => {
    resetPaymentForm();
    paymentForm.id = payment.id;
    paymentForm.student_id = payment.student_id;
    showingPaymentModal.value = true;

    await nextTick();
    paymentForm.course_id = payment.course_id;
    paymentForm.payment_reference = payment.payment_reference || '';
    paymentForm.payment_date = payment.payment_date;
    paymentForm.method = payment.method;
    paymentForm.amount = payment.amount;
    paymentForm.allocations = [];
    paymentForm.status = payment.status;
    paymentForm.notes = payment.notes || '';
};

const closePaymentModal = () => {
    showingPaymentModal.value = false;
    resetPaymentForm();
};

const savePayment = () => {
    const options = { preserveScroll: true, onSuccess: closePaymentModal };
    paymentForm.id ? paymentForm.put(route('finance.payments.update', paymentForm.id), options) : paymentForm.post(route('finance.payments.store'), options);
};

const splitCourse = (allocation) => selectedStudent.value?.courses.find((course) => Number(course.id) === Number(allocation.course_id));

const fillAllocationBalance = (allocation) => {
    allocation.amount = splitCourse(allocation)?.balance || '';
};

const destroyPayment = (payment) => {
    deletingPayment.value = payment;
};

const toggleStudentSelect = () => {
    studentSelect.isOpen = !studentSelect.isOpen;
    if (studentSelect.isOpen) {
        studentSelect.search = '';
    }
};

const selectStudent = (student) => {
    paymentForm.student_id = student.id;
    studentSelect.isOpen = false;
    studentSelect.search = '';
};

const closeDeletePaymentModal = () => {
    deletingPayment.value = null;
};

const confirmDeletePayment = () => {
    if (!deletingPayment.value) return;

    router.delete(route('finance.payments.destroy', deletingPayment.value.id), {
        preserveScroll: true,
        onSuccess: closeDeletePaymentModal,
    });
};

const printReceipt = (payment) => {
    const printWindow = window.open('', '_blank', 'width=820,height=900');

    if (!printWindow) {
        return;
    }

    const org = organisation.value;
    const studentName = `${payment.student?.first_name || ''} ${payment.student?.last_name || ''}`.trim();
    const courseName = payment.course ? paymentTargetLabel(payment.course) : 'Not assigned';
    const courseSummary = paymentCourseSummary(payment);
    const logo = org.logo_url
        ? `<img src="${escapeHtml(org.logo_url)}" alt="${escapeHtml(org.name || 'Logo')}" class="logo">`
        : `<div class="logo-fallback">${escapeHtml(org.short_name || org.name || 'ISP')}</div>`;

    printWindow.document.write(`
        <!doctype html>
        <html>
            <head>
                <title>Receipt ${escapeHtml(payment.payment_reference || payment.id)}</title>
                <style>
                    * { box-sizing: border-box; }
                    @page {
                        size: 80mm auto;
                        margin: 3mm;
                    }
                    body {
                        margin: 0;
                        background: #ffffff;
                        color: #000000;
                        font-family: "Courier New", Courier, monospace;
                        font-size: 11px;
                    }
                    .receipt {
                        width: 72mm;
                        margin: 0 auto;
                        background: #ffffff;
                        padding: 0;
                    }
                    .header {
                        text-align: center;
                    }
                    .logo-wrap {
                        display: flex;
                        justify-content: center;
                        margin-bottom: 5px;
                    }
                    .logo,
                    .logo-fallback {
                        width: 38px;
                        height: 38px;
                        object-fit: contain;
                    }
                    .logo-fallback {
                        display: grid;
                        place-items: center;
                        border: 1px solid #000000;
                        color: #000000;
                        font-size: 11px;
                        font-weight: 700;
                    }
                    h1,
                    h2,
                    p { margin: 0; }
                    .org-name {
                        font-size: 14px;
                        font-weight: 700;
                        text-transform: uppercase;
                    }
                    .org-meta {
                        margin-top: 3px;
                        font-size: 10px;
                        line-height: 1.35;
                    }
                    .line {
                        border-top: 1px dashed #000000;
                        margin: 7px 0;
                    }
                    .title {
                        font-size: 13px;
                        font-weight: 700;
                        text-align: center;
                        text-transform: uppercase;
                    }
                    .row {
                        display: flex;
                        justify-content: space-between;
                        gap: 8px;
                        line-height: 1.45;
                    }
                    .row span:first-child {
                        white-space: nowrap;
                    }
                    .row span:last-child {
                        text-align: right;
                        overflow-wrap: anywhere;
                    }
                    .block {
                        line-height: 1.45;
                    }
                    .items-header,
                    .item-row,
                    .total-row {
                        display: flex;
                        justify-content: space-between;
                        gap: 8px;
                    }
                    .items-header {
                        font-weight: 700;
                        text-transform: uppercase;
                    }
                    .item-name {
                        flex: 1;
                    }
                    .item-amount {
                        min-width: 24mm;
                        text-align: right;
                    }
                    .total-row {
                        font-size: 13px;
                        font-weight: 700;
                    }
                    .signature {
                        margin-top: 18px;
                        border-top: 1px dashed #000000;
                        padding-top: 5px;
                        text-align: center;
                    }
                    .thanks {
                        margin-top: 10px;
                        text-align: center;
                        font-weight: 700;
                    }
                    @media print {
                        .receipt {
                            width: 72mm;
                            margin: 0;
                        }
                    }
                </style>
            </head>
            <body>
                <main class="receipt">
                    <section class="header">
                        <div class="logo-wrap">
                            ${logo}
                        </div>
                        <h1 class="org-name">${escapeHtml(org.name || 'Organisation')}</h1>
                        <p class="org-meta">
                            ${escapeHtml(org.location || '')}<br>
                            Tel: ${escapeHtml(org.primary_contact || '')}<br>
                            Email: ${escapeHtml(org.official_email || '')}
                        </p>
                    </section>

                    <div class="line"></div>
                    <div class="title">Payment Receipt</div>
                    <div class="line"></div>

                    <section>
                        <div class="row"><span>Receipt No:</span><span>${escapeHtml(payment.payment_reference || `PAY-${payment.id}`)}</span></div>
                        <div class="row"><span>Date:</span><span>${escapeHtml(formatDate(payment.payment_date) || payment.payment_date)}</span></div>
                        <div class="row"><span>Method:</span><span>${escapeHtml(payment.method || '')}</span></div>
                        <div class="row"><span>Status:</span><span>${escapeHtml(payment.status || '')}</span></div>
                    </section>

                    <div class="line"></div>

                    <section class="block">
                        <p><strong>Student:</strong> ${escapeHtml(studentName || 'Not provided')}</p>
                        <p><strong>Admission:</strong> ${escapeHtml(payment.student?.admission_number || '')}</p>
                    </section>

                    <div class="line"></div>

                    <section>
                        <div class="items-header">
                            <span>Item</span>
                            <span>Amount</span>
                        </div>
                        <div class="item-row">
                            <span class="item-name">${escapeHtml(courseName)}</span>
                            <span class="item-amount">${escapeHtml(money(payment.amount))}</span>
                        </div>
                    </section>

                    <div class="line"></div>

                    <section>
                        <div class="total-row">
                            <span>This Payment</span>
                            <span>${escapeHtml(money(payment.amount))}</span>
                        </div>
                        <div class="row">
                            <span>Total Paid</span>
                            <span>${escapeHtml(money(courseSummary.paid))}</span>
                        </div>
                        <div class="row">
                            <span>Balance Left</span>
                            <span>${escapeHtml(money(courseSummary.balance))}</span>
                        </div>
                    </section>

                    <p class="signature">Received by</p>
                    <p class="thanks">Thank you</p>
                    <div class="line"></div>
                    <p class="org-meta">Printed: ${escapeHtml(new Date().toLocaleString())}</p>
                </main>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
    }, 250);
};

const exportCsv = () => {
    const rows = [
        ['Learner', 'Admission No.', 'Payment Target', 'Reference', 'Method', 'Status', 'Date', 'Amount'],
        ...props.payments.data.map((payment) => [
            `${payment.student?.first_name || ''} ${payment.student?.last_name || ''}`.trim(),
            payment.student?.admission_number || '',
            payment.course ? paymentTargetLabel(payment.course) : '',
            payment.payment_reference,
            payment.method,
            payment.status,
            payment.payment_date,
            payment.amount,
        ]),
    ];

    const csv = rows.map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'payments.csv';
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <AppLayout title="Payment Management">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Confirmed Paid</p>
                <p class="mt-2 text-3xl font-bold text-emerald-500">{{ money(stats.totalPaid) }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Paid Today</p>
                <p class="mt-2 text-3xl font-bold text-blue-500">{{ money(stats.todayPaid) }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending On Page</p>
                <p class="mt-2 text-3xl font-bold text-amber-500">{{ stats.pending }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Payment Records</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ Number(stats.records || 0).toLocaleString() }}</p>
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
                <button v-if="canAdd" class="inline-flex h-8 items-center gap-2 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400" type="button" @click="openPaymentModal">
                    <span class="text-base leading-none">+</span>
                    Add Payment
                </button>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <select v-model="filter.student_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All learners</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.admission_number }} - {{ student.name }}</option>
                </select>
                <select v-model="filter.course_id" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300">
                    <option value="">All targets</option>
                    <option v-for="course in filterCourses" :key="course.id" :value="course.id">{{ paymentTargetLabel(course) }}</option>
                </select>
                <input v-model="filter.search" class="h-8 rounded-md border-gray-200 bg-white text-xs text-gray-700 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#11141b] dark:text-gray-300 dark:placeholder:text-gray-600" placeholder="Search learner, ref...">
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
            <table v-if="payments.data.length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:bg-[#171b25]">
                    <tr>
                        <th class="px-5 py-3">Learner</th>
                        <th class="px-5 py-3">Payment Target</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                    <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-[#141925]">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ payment.student?.admission_number }}</p>
                            <p class="text-xs text-gray-500">{{ payment.student?.first_name }} {{ payment.student?.last_name }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-gray-600 dark:text-gray-300">{{ paymentTargetLabel(payment.course) }}</p>
                            <p v-if="payment.course?.parent_course_id" class="text-xs text-gray-500">Subcourse</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ payment.payment_reference }}</p>
                            <p class="text-xs capitalize text-gray-500">{{ payment.status }}</p>
                        </td>
                        <td class="px-5 py-4 capitalize text-gray-600 dark:text-gray-300">{{ payment.method }}</td>
                        <td class="px-5 py-4 text-gray-500">{{ payment.payment_date }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize" :class="payment.status === 'confirmed' ? 'bg-emerald-500/10 text-emerald-500' : payment.status === 'pending' ? 'bg-amber-500/10 text-amber-500' : 'bg-red-500/10 text-red-400'">
                                {{ payment.status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold text-emerald-500">{{ money(payment.amount) }}</td>
                        <td class="px-5 py-4 text-right">
                            <button class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 transition hover:border-gray-400 dark:border-[#2a3040] dark:text-gray-300" type="button" @click="printReceipt(payment)">Print</button>
                            <button v-if="canEdit" class="mr-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-blue-600 transition hover:border-blue-400 dark:border-[#2a3040] dark:text-blue-300" type="button" @click="editPayment(payment)">Edit</button>
                            <button v-if="canDelete" class="rounded-md border border-red-500/30 px-2.5 py-1.5 text-xs text-red-300 transition hover:border-red-400" type="button" @click="destroyPayment(payment)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-else class="flex min-h-[260px] flex-col items-center justify-center px-6 text-center">
                <div class="inline-flex size-12 items-center justify-center rounded-md bg-gray-100 text-gray-500 dark:bg-[#222738]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v10H4V7Zm0 3h16M7 14h4m5 0h1" />
                    </svg>
                </div>
                <p class="mt-4 font-semibold text-gray-700 dark:text-gray-300">No payments found</p>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Record a payment or adjust the filters to see finance records.</p>
                <button v-if="canAdd" class="mt-5 rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-400" type="button" @click="openPaymentModal">+ Add Payment</button>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                <Pagination :links="payments.links" />
            </div>
        </div>

        <DialogModal :show="showingPaymentModal" max-width="2xl" @close="closePaymentModal">
            <template #title>{{ paymentForm.id ? 'Edit payment' : 'Add payment' }}</template>

            <template #content>
                <form id="payment-form" class="grid gap-4 text-gray-700 md:grid-cols-2 dark:text-gray-300" @submit.prevent="savePayment">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Learner</label>
                        <div class="relative mt-1">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-900 focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                @click="toggleStudentSelect"
                            >
                                <span class="truncate">{{ selectedStudentLabel }}</span>
                                <svg class="size-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div v-if="studentSelect.isOpen" class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                <div class="sticky top-0 border-b border-gray-200 bg-white p-2 dark:border-[#2a3040] dark:bg-[#1a1f2b]">
                                    <input
                                        v-model="studentSelect.search"
                                        class="w-full rounded-md border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white"
                                        placeholder="Search admission no. or name"
                                        @keydown.esc="studentSelect.isOpen = false"
                                    >
                                </div>
                                <button
                                    v-for="student in filteredStudents"
                                    :key="student.id"
                                    type="button"
                                    class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-[#252b3a]"
                                    :class="Number(paymentForm.student_id) === Number(student.id) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-700 dark:text-gray-300'"
                                    @click="selectStudent(student)"
                                >
                                    <span class="block font-semibold">{{ student.admission_number }}</span>
                                    <span class="text-xs text-gray-500">{{ student.name }}</span>
                                </button>
                                <div v-if="!filteredStudents.length" class="px-3 py-2 text-sm text-gray-500">
                                    No learners found
                                </div>
                            </div>
                        </div>
                        <p v-if="paymentForm.errors.student_id" class="mt-1 text-xs text-red-400">{{ paymentForm.errors.student_id }}</p>
                    </div>
                    <div v-if="!canSplitPayment">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Payment target</label>
                        <select v-model="paymentForm.course_id" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-60 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!selectedStudent" required>
                            <option value="">{{ selectedStudent ? 'Select payment target' : 'Select learner first' }}</option>
                            <option v-for="course in selectedStudent?.courses || []" :key="course.id" :value="course.id">{{ paymentTargetLabel(course) }}</option>
                        </select>
                        <p v-if="paymentForm.errors.course_id" class="mt-1 text-xs text-red-400">{{ paymentForm.errors.course_id }}</p>
                    </div>
                    <div v-if="selectedCourse && !canSplitPayment" class="grid grid-cols-3 gap-2 rounded-md border border-gray-200 p-3 text-xs md:col-span-2 dark:border-[#2a3040]">
                        <div>
                            <p class="text-gray-500">Fees</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ money(selectedCourse.fees) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Paid</p>
                            <p class="mt-1 font-semibold text-emerald-500">{{ money(selectedCourse.paid) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Balance</p>
                            <p class="mt-1 font-semibold text-amber-500">{{ money(selectedCourse.balance) }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ canSplitPayment ? 'Total Amount Received' : 'Amount' }}</label>
                        <input v-model="paymentForm.amount" type="number" min="1" step="0.01" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                        <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-400">{{ paymentForm.errors.amount }}</p>
                    </div>
                    <div v-if="canSplitPayment" class="md:col-span-2 rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Split by Target</h3>
                                <p class="text-xs text-gray-500">Enter how much of this payment belongs to each course or subcourse.</p>
                            </div>
                            <div class="text-xs sm:text-right">
                                <p class="font-semibold text-emerald-500">Allocated {{ money(allocationTotal) }}</p>
                                <p :class="Math.abs(allocationRemaining) < 0.01 ? 'text-gray-500' : 'text-amber-500'">
                                    Remaining {{ money(allocationRemaining) }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 space-y-2">
                            <div v-for="allocation in paymentForm.allocations" :key="allocation.course_id" class="grid gap-2 rounded-md bg-gray-50 p-3 text-sm md:grid-cols-[1fr_140px_auto] md:items-center dark:bg-[#151a25]">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">{{ paymentTargetLabel(splitCourse(allocation)) }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ splitCourse(allocation)?.code }} |
                                        Fee {{ money(splitCourse(allocation)?.fees) }} |
                                        Paid {{ money(splitCourse(allocation)?.paid) }} |
                                        Balance {{ money(splitCourse(allocation)?.balance) }}
                                    </p>
                                </div>
                                <input v-model="allocation.amount" type="number" min="0" step="0.01" class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Amount">
                                <button type="button" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 transition hover:border-violet-400 dark:border-[#2a3040] dark:text-gray-300" @click="fillAllocationBalance(allocation)">
                                    Balance
                                </button>
                            </div>
                        </div>
                        <p v-if="paymentForm.errors.allocations" class="mt-2 text-xs text-red-400">{{ paymentForm.errors.allocations }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Payment date</label>
                        <input v-model="paymentForm.payment_date" type="date" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Method</label>
                        <select v-model="paymentForm.method" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                        <select v-model="paymentForm.status" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" required>
                            <option value="confirmed">Confirmed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</label>
                        <input v-model="paymentForm.payment_reference" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" placeholder="Auto generated if empty">
                        <p v-if="paymentForm.errors.payment_reference" class="mt-1 text-xs text-red-400">{{ paymentForm.errors.payment_reference }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</label>
                        <textarea v-model="paymentForm.notes" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </form>
            </template>

            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closePaymentModal">Cancel</button>
                <button class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" form="payment-form" type="submit" :disabled="paymentForm.processing">
                    {{ paymentForm.processing ? 'Saving...' : (paymentForm.id ? 'Update payment' : 'Save payment') }}
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="Boolean(deletingPayment)" max-width="md" @close="closeDeletePaymentModal">
            <template #title>Delete payment</template>
            <template #content>
                <p>
                    Delete payment <span class="font-semibold text-gray-900 dark:text-white">{{ deletingPayment?.payment_reference }}</span>?
                </p>
                <p class="mt-2">This removes the payment from active finance records.</p>
            </template>
            <template #footer>
                <button class="mr-2 rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-600 transition hover:text-gray-900 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="closeDeletePaymentModal">Cancel</button>
                <button class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400" type="button" @click="confirmDeletePayment">Delete payment</button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
