<script setup>
import { useForm } from '@inertiajs/vue3';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    label: String,
    importerKey: String,
    routePrefix: String,
    fields: Object,
    step: String,
});

const form = useForm({ file: null });

function onFileChange(e) {
    form.file = e.target.files[0];
}

function submit() {
    form.post(`/${props.routePrefix}/${props.importerKey}/upload`, { forceFormData: true });
}
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Import {{ label }}</h1>
        <p>Upload a CSV or Excel file. The first row must contain your column headers.</p>
        <p class="hint">Accepted formats: .csv, .xlsx, .xls</p>

        <p class="expected-columns-heading">Expected columns</p>
        <ul class="expected-columns">
            <li v-for="fieldLabel in fields" :key="fieldLabel">{{ fieldLabel }}</li>
        </ul>

        <form @submit.prevent="submit">
            <input type="file" accept=".csv,.txt,.xlsx,.xls" @change="onFileChange">
            <p v-if="form.errors.file" class="error">{{ form.errors.file }}</p>
            <button type="submit" :disabled="form.processing">Upload file</button>
        </form>
    </div>
</template>
