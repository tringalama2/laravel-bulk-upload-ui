<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    importerKey: String,
    routePrefix: String,
    batch: Object,
    step: String,
});

const percentage = ref(0);
const error = ref(null);
let cancelled = false;

async function poll() {
    try {
        const response = await fetch(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/progress`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const json = await response.json();

        if (cancelled) {
            return;
        }

        percentage.value = json.percentage;
        error.value = null;

        if (json.complete) {
            router.visit(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/complete`);

            return;
        }
    } catch (e) {
        if (cancelled) {
            return;
        }

        error.value = "We're having trouble checking your import status. Retrying…";
    }

    if (!cancelled) {
        setTimeout(poll, 1500);
    }
}

onMounted(poll);
onUnmounted(() => {
    cancelled = true;
});
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Importing…</h1>
        <progress :value="percentage" max="100" />
        <p>{{ percentage }}%</p>
        <p v-if="error" class="error">{{ error }}</p>
    </div>
</template>
