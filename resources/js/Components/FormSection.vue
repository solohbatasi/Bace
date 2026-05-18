<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <section class="grid gap-4 lg:grid-cols-[280px_1fr]">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div>
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="border border-gray-200 bg-white px-5 py-5 dark:border-[#273044] dark:bg-[#11141b]"
                    :class="hasActions ? 'rounded-t-md' : 'rounded-md'"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex items-center justify-end rounded-b-md border-x border-b border-gray-200 bg-gray-50 px-5 py-4 text-end dark:border-[#273044] dark:bg-[#0c0f16]">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </section>
</template>
