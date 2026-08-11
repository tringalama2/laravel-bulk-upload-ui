<script setup>
import { computed } from 'vue';

const STEPS = [
    { key: 'upload', label: 'Upload File' },
    { key: 'map', label: 'Column Mapping' },
    { key: 'review', label: 'Review & Fix' },
    { key: 'progress', label: 'Import' },
];

const props = defineProps({
    step: String,
});

const currentIndex = computed(() => {
    const currentKey = props.step === 'complete' ? 'progress' : props.step;

    return STEPS.findIndex((item) => item.key === currentKey);
});
</script>

<template>
    <div class="steps">
        <template v-for="(item, index) in STEPS" :key="item.key">
            <span v-if="index > 0" class="step-chevron" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span :class="index <= currentIndex ? 'step is-active' : 'step'">
                <span class="step-circle">
                    <svg v-if="index <= currentIndex" width="12" height="12" viewBox="0 0 16 16" fill="none">
                        <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ item.label }}
            </span>
        </template>
    </div>
</template>
