<script setup>
import { router, useForm } from '@inertiajs/vue3';
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

function sampleFor(heading) {
    const firstRow = (props.batch.sample_rows || [])[0];
    return firstRow ? firstRow[heading] : '';
}

function goToPrevious() {
    router.get(`/${props.routePrefix}/${props.importerKey}`);
}

function submit() {
    form.post(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/map`);
}
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Map your columns</h1>
        <p>Match each column from your file to the correct field. Sample data is shown to help you confirm the match.</p>

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
                <button type="button" class="btn-secondary" @click="goToPrevious">Previous</button>
                <button type="submit" :disabled="form.processing">Continue</button>
            </div>
        </form>
    </div>
</template>
