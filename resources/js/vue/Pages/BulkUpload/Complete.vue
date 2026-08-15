<script setup>
import { Link } from '@inertiajs/vue3';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    importerKey: String,
    routePrefix: String,
    batch: Object,
    fields: Object,
    skippedRows: Array,
    step: String,
});
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Import complete</h1>
        <p>{{ batch.imported_rows }} row(s) imported, {{ batch.skipped_rows }} row(s) skipped.</p>
        <p><Link :href="`/${props.routePrefix}/${props.importerKey}`">Import another file</Link></p>

        <table v-if="skippedRows?.length">
            <thead>
                <tr>
                    <th v-for="fieldLabel in Object.values(fields)" :key="fieldLabel">{{ fieldLabel }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in skippedRows" :key="row.id">
                    <td v-for="field in Object.keys(fields)" :key="field">{{ row.mapped_data?.[field] ?? '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
