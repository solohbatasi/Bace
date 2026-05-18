<script setup>
import SectionTitle from './SectionTitle.vue';
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
            <div class="rounded-md border border-gray-200 bg-white px-5 py-5 dark:border-[#273044] dark:bg-[#11141b]">
                <slot name="content" />
            </div>
        </div>
    </section>
</template>
