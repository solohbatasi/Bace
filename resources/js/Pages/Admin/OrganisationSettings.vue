<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    setting: Object,
    defaultOperationHours: Array,
});

const logoInput = ref(null);
const logoPreview = ref(null);
const setting = computed(() => props.setting);

const cloneHours = (hours) => (hours?.length ? hours : props.defaultOperationHours).map((item) => ({
    day: item.day,
    is_open: Boolean(item.is_open),
    opens_at: item.opens_at || '',
    closes_at: item.closes_at || '',
}));

const form = useForm({
    name: props.setting.name || '',
    short_name: props.setting.short_name || '',
    logo: null,
    official_email: props.setting.official_email || '',
    contact_email: props.setting.contact_email || '',
    marketing_email: props.setting.marketing_email || '',
    primary_contact: props.setting.primary_contact || '',
    secondary_contact: props.setting.secondary_contact || '',
    location: props.setting.location || '',
    mission: props.setting.mission || '',
    vision: props.setting.vision || '',
    about: props.setting.about || '',
    description: props.setting.description || '',
    operation_hours: cloneHours(props.setting.operation_hours),
});

const currentLogo = computed(() => logoPreview.value || setting.value.logo_url);

const selectLogo = () => {
    logoInput.value?.click();
};

const handleLogo = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const removeSelectedLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (logoInput.value) logoInput.value.value = null;
};

const save = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'PUT',
        }))
        .post(route('admin.organisation-settings.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: removeSelectedLogo,
        });
};
</script>

<template>
    <AppLayout title="Organisation Settings">
        <form class="space-y-4" @submit.prevent="save">
            <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-16 items-center justify-center overflow-hidden rounded-md bg-violet-500/10 text-sm font-semibold text-violet-700 dark:text-violet-300">
                            <img v-if="currentLogo" :src="currentLogo" alt="Organisation logo" class="size-full object-cover">
                            <span v-else>{{ form.short_name || 'ORG' }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ form.name || 'Organisation' }}</p>
                            <p class="mt-1 text-sm font-medium text-violet-600 dark:text-violet-300">{{ form.short_name || 'No short name' }}</p>
                            <p class="mt-2 max-w-2xl text-sm text-gray-500">{{ form.about || 'No short about has been added yet.' }}</p>
                        </div>
                    </div>
                    <button class="inline-flex h-8 w-fit items-center rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Identity' }}
                    </button>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Organisation Name</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Short Name</label>
                        <input v-model="form.short_name" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.short_name" class="mt-1 text-xs text-red-400">{{ form.errors.short_name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="handleLogo">
                        <button class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-300 dark:hover:text-white" type="button" @click="selectLogo">Upload logo</button>
                        <button v-if="logoPreview" class="ml-2 text-xs text-red-400 hover:text-red-300" type="button" @click="removeSelectedLogo">Remove selected logo</button>
                        <p v-if="form.errors.logo" class="mt-1 text-xs text-red-400">{{ form.errors.logo }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Contact Information</h2>
                    <button class="h-8 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Contact' }}
                    </button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Official Email</label>
                        <input v-model="form.official_email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.official_email" class="mt-1 text-xs text-red-400">{{ form.errors.official_email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contact Email</label>
                        <input v-model="form.contact_email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-400">{{ form.errors.contact_email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Marketing Email</label>
                        <input v-model="form.marketing_email" type="email" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                        <p v-if="form.errors.marketing_email" class="mt-1 text-xs text-red-400">{{ form.errors.marketing_email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Primary Contact</label>
                        <input v-model="form.primary_contact" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Secondary Contact</label>
                        <input v-model="form.secondary_contact" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Location</label>
                        <input v-model="form.location" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white">
                    </div>
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Profile</h2>
                    <button class="h-8 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Profile' }}
                    </button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Mission</label>
                        <textarea v-model="form.mission" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Vision</label>
                        <textarea v-model="form.vision" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Small About</label>
                        <textarea v-model="form.about" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Business Description</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" />
                    </div>
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Days And Hours</h2>
                    <button class="h-8 rounded-md bg-violet-500 px-3 text-xs font-semibold text-white transition hover:bg-violet-400 disabled:opacity-50" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Hours' }}
                    </button>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div v-for="item in form.operation_hours" :key="item.day" class="rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.day }}</p>
                            <label class="flex items-center gap-2 text-xs text-gray-500">
                                <input v-model="item.is_open" type="checkbox" class="rounded border-[#2a3040] text-violet-500 focus:ring-violet-500">
                                Open
                            </label>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <input v-model="item.opens_at" type="time" class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!item.is_open">
                            <input v-model="item.closes_at" type="time" class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-50 dark:border-[#2a3040] dark:bg-[#0c0f16] dark:text-white" :disabled="!item.is_open">
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
