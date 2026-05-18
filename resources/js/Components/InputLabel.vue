<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
