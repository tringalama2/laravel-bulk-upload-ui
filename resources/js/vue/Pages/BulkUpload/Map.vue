<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    importerKey: String,
    routePrefix: String,
    batch: Object,
    fields: Object,
    suggestedMapping: Object,
    step: String,
});

const form = useForm({ mapping: { ...(props.suggestedMapping || {}) } });
const actionError = ref(null);
const goingBack = ref(false);

function sampleFor(heading) {
    const firstRow = (props.batch.sample_rows || [])[0];
    return firstRow ? firstRow[heading] : '';
}

function goToPrevious() {
    actionError.value = null;
    goingBack.value = true;
    router.get(`/${props.routePrefix}/${props.importerKey}`, {}, {
        onError: () => {
            actionError.value = 'Could not go back. Please try again.';
            goingBack.value = false;
        },
    });
}

function submit() {
    actionError.value = null;
    form.post(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/map`, {
        onError: (errs) => {
            if (!Object.keys(errs).length) {
                actionError.value = 'Something went wrong saving your column mapping. Please try again.';
            }
        },
    });
}
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Map your columns</h1>
        <p>Match each column from your file to the correct field. Sample data is shown to help you confirm the match.</p>

        <p v-if="actionError" class="banner-error">{{ actionError }}</p>
        <p v-if="form.errors.mapping" class="banner-error">{{ form.errors.mapping }}</p>

        <form @submit.prevent="submit">
            <table>
                <thead>
                    <tr>
                        <th>File column</th>
                        <th>Sample data</th>
                        <th>Maps to</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="heading in batch.headings || []" :key="heading">
                        <td>{{ heading }}</td>
                        <td>{{ sampleFor(heading) }}</td>
                        <td>
                            <select v-model="form.mapping[heading]">
                                <option value="">—</option>
                                <option v-for="(fieldLabel, field) in fields" :key="field" :value="field">
                                    {{ fieldLabel }}
                                </option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="actions">
                <button
                    type="button"
                    :class="goingBack ? 'btn-secondary is-loading' : 'btn-secondary'"
                    :disabled="goingBack || form.processing"
                    @click="goToPrevious"
                >
                    <span v-if="goingBack" class="spinner" aria-hidden="true"></span>
                    {{ goingBack ? 'Going back…' : 'Previous' }}
                </button>
                <button type="submit" :class="form.processing ? 'is-loading' : ''" :disabled="form.processing || goingBack">
                    <span v-if="form.processing" class="spinner" aria-hidden="true"></span>
                    {{ form.processing ? 'Continuing…' : 'Continue' }}
                </button>
            </div>
        </form>
    </div>
</template>
