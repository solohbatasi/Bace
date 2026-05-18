<template>
    <div class="flex justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                <slot name="title" />
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template>
