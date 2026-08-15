<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    label: String,
    importerKey: String,
    routePrefix: String,
    fields: Object,
    step: String,
    activeBatch: Object,
});

const form = useForm({ file: null });
const actionError = ref(null);
const startingOver = ref(false);
const continuing = ref(false);

function onFileChange(e) {
    form.file = e.target.files[0];
}

function submit() {
    actionError.value = null;
    form.post(`/${props.routePrefix}/${props.importerKey}/upload`, {
        forceFormData: true,
        onError: (errs) => {
            if (!errs.file) {
                actionError.value = 'Something went wrong uploading your file. Please try again.';
            }
        },
    });
}

function startOver() {
    actionError.value = null;
    startingOver.value = true;
    router.delete(`/${props.routePrefix}/${props.importerKey}/${props.activeBatch.id}/cancel`, {
        onError: () => {
            actionError.value = 'Could not start over. Please try again.';
            startingOver.value = false;
        },
    });
}

function continueImport() {
    actionError.value = null;
    continuing.value = true;
    router.get(props.activeBatch.resumeUrl, {}, {
        onError: () => {
            actionError.value = 'Could not continue your import. Please try again.';
            continuing.value = false;
        },
    });
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

        <p v-if="actionError" class="banner-error">{{ actionError }}</p>

        <div v-if="activeBatch" class="notice">
            <p>You already uploaded <strong>{{ activeBatch.filename }}</strong>. Continue where you left off, or start over to upload a different file.</p>
            <div class="actions">
                <button
                    v-if="activeBatch.cancelable"
                    type="button"
                    :class="startingOver ? 'btn-secondary is-loading' : 'btn-secondary'"
                    :disabled="startingOver || continuing"
                    @click="startOver"
                >
                    <span v-if="startingOver" class="spinner" aria-hidden="true"></span>
                    {{ startingOver ? 'Starting over…' : 'Start over' }}
                </button>
                <button
                    type="button"
                    :class="continuing ? 'is-loading' : ''"
                    :disabled="startingOver || continuing"
                    @click="continueImport"
                >
                    <span v-if="continuing" class="spinner" aria-hidden="true"></span>
                    {{ continuing ? 'Continuing…' : 'Continue' }}
                </button>
            </div>
        </div>
        <form v-else @submit.prevent="submit">
            <input type="file" accept=".csv,.txt,.xlsx,.xls" @change="onFileChange">
            <p v-if="form.errors.file" class="error">{{ form.errors.file }}</p>
            <button type="submit" :class="form.processing ? 'is-loading' : ''" :disabled="form.processing">
                <span v-if="form.processing" class="spinner" aria-hidden="true"></span>
                {{ form.processing ? 'Uploading…' : 'Upload file' }}
            </button>
        </form>
    </div>
</template>
