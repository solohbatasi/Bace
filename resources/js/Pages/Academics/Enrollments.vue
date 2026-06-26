<script setup>
import { computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    canManage: Boolean,
    student: Object,
    registrations: Object,
    currentYear: Object,
    semesters: Array,
    units: Array,
    classes: Array,
});

const currentSemester = computed(() => props.semesters.find((semester) => semester.is_current) || props.semesters[0]);
const form = useForm({
    semester_id: currentSemester.value?.id || '',
    academic_year_id: props.currentYear?.id || currentSemester.value?.academic_year_id || '',
    unit_ids: [],
});
const transferForm = useForm({ class_id: '', unit_ids: [], notes: '' });
const scoreForms = reactive({});

const register = () => form.post(route('academics.enrollments.register'), { preserveScroll: true, onSuccess: () => form.reset('unit_ids') });
const approve = (registration) => router.post(route('academics.enrollments.approve', registration.id), {}, { preserveScroll: true });
const drop = (registration) => router.post(route('academics.enrollments.drop', registration.id), { notes: 'Dropped by administrator' }, { preserveScroll: true });
const transfer = (registration) => transferForm.post(route('academics.enrollments.transfer', registration.id), { preserveScroll: true });
const scoreForm = (registration) => {
    if (!scoreForms[registration.id]) {
        scoreForms[registration.id] = {
            course_score: registration.course_score ?? '',
            course_grade: registration.course_grade ?? '',
        };
    }

    return scoreForms[registration.id];
};
const saveScore = (registration) => router.post(route('academics.enrollments.score', registration.id), scoreForm(registration), { preserveScroll: true });
</script>

<template>
    <AppLayout title="Enrollment Management">
        <div class="grid gap-4 lg:grid-cols-[360px_1fr]">
            <div class="space-y-4">
                <form v-if="student" class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]" @submit.prevent="register">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Register Semester</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ student.course?.code }} - {{ student.course?.name }}</p>
                    <div class="mt-3 space-y-3">
                        <select v-model="form.semester_id" class="w-full rounded-md border-gray-200 text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" required>
                            <option v-for="semester in semesters" :key="semester.id" :value="semester.id">{{ semester.name }}</option>
                        </select>
                        <div class="max-h-72 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                            <label v-for="unit in units" :key="unit.id" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="form.unit_ids" type="checkbox" :value="unit.id" class="mt-1 rounded border-gray-300 text-violet-500">
                                <span>
                                    <span class="block font-medium">{{ unit.code }} - {{ unit.name }}</span>
                                    <span class="text-xs text-gray-500">{{ unit.credit_hours }} credit hours</span>
                                </span>
                            </label>
                        </div>
                        <button class="h-9 w-full rounded-md bg-violet-500 text-sm font-semibold text-white">Submit registration</button>
                    </div>
                </form>

                <form v-if="canManage" class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]" @submit.prevent>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Transfer Setup</h2>
                    <div class="mt-3 space-y-3">
                        <select v-model="transferForm.class_id" class="w-full rounded-md border-gray-200 text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]">
                            <option value="">Target class</option>
                            <option v-for="collegeClass in classes" :key="collegeClass.id" :value="collegeClass.id">{{ collegeClass.code }} - {{ collegeClass.name }}</option>
                        </select>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                            <label v-for="unit in units" :key="unit.id" class="flex gap-2 text-sm">
                                <input v-model="transferForm.unit_ids" type="checkbox" :value="unit.id" class="rounded border-gray-300 text-blue-500">
                                {{ unit.code }} - {{ unit.name }}
                            </label>
                        </div>
                        <textarea v-model="transferForm.notes" rows="2" class="w-full rounded-md border-gray-200 text-sm dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Transfer notes" />
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-[#273044] dark:bg-[#11141b]">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <thead class="bg-gray-50 text-left text-[11px] uppercase text-gray-500 dark:bg-[#171b25]">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Term</th>
                            <th class="px-4 py-3">Units / Score</th>
                            <th class="px-4 py-3">Status</th>
                            <th v-if="canManage" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1f2b]">
                        <tr v-for="registration in registrations.data" :key="registration.id">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ registration.student?.admission_number }}</p>
                                <p class="text-xs text-gray-500">{{ registration.student?.first_name }} {{ registration.student?.last_name }}</p>
                            </td>
                            <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ registration.academic_year?.name }} / {{ registration.semester?.name }}</td>
                            <td class="px-4 py-4">
                                <div v-if="registration.enrollments.length" class="flex flex-wrap gap-1">
                                    <span v-for="enrollment in registration.enrollments" :key="enrollment.id" class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-[#1a1f2b] dark:text-gray-300">{{ enrollment.unit?.code }}</span>
                                </div>
                                <div v-else>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ registration.course?.code }} - {{ registration.course?.name }}</p>
                                    <form v-if="canManage" class="mt-2 flex flex-wrap items-center gap-2" @submit.prevent="saveScore(registration)">
                                        <input v-model="scoreForm(registration).course_score" type="number" min="0" max="100" step="0.01" class="h-8 w-24 rounded-md border-gray-200 text-xs dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Score">
                                        <input v-model="scoreForm(registration).course_grade" class="h-8 w-24 rounded-md border-gray-200 text-xs dark:border-[#2a3040] dark:bg-[#0c0f16]" placeholder="Grade">
                                        <button class="h-8 rounded-md border border-violet-500/40 px-2.5 text-xs font-semibold text-violet-600 dark:text-violet-300" type="submit">Save</button>
                                    </form>
                                    <p v-else class="text-xs text-gray-500">
                                        Score {{ registration.course_score ?? '-' }} / Grade {{ registration.course_grade ?? '-' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize" :class="registration.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : registration.status === 'pending' ? 'bg-amber-500/10 text-amber-400' : 'bg-gray-500/10 text-gray-400'">{{ registration.status }}</span>
                            </td>
                            <td v-if="canManage" class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button class="rounded-md border border-emerald-500/30 px-2.5 py-1.5 text-xs text-emerald-500" @click="approve(registration)">Approve</button>
                                    <button class="rounded-md border border-amber-500/30 px-2.5 py-1.5 text-xs text-amber-500" @click="drop(registration)">Drop</button>
                                    <button class="rounded-md border border-blue-500/30 px-2.5 py-1.5 text-xs text-blue-500" @click="transfer(registration)">Transfer</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                    <Pagination :links="registrations.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
