<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
            <div class="text-base font-semibold text-gray-900 dark:text-white">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 text-end dark:border-[#232837] dark:bg-[#0c0f16]">
            <slot name="footer" />
        </div>
    </Modal>
</template>
