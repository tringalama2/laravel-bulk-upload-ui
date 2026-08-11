<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import WizardSteps from '../../Components/WizardSteps.vue';

const props = defineProps({
    importerKey: String,
    routePrefix: String,
    batch: Object,
    fields: Object,
    rows: Object,
    errorsOnly: Boolean,
    step: String,
});

const saving = ref(null);

const VALID_ROW_STATUSES = ['valid', 'imported'];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

function goToPrevious() {
    router.get(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/map`);
}

function toggleErrorsOnly() {
    router.get(
        `/${props.routePrefix}/${props.importerKey}/${props.batch.id}/review`,
        { errors_only: props.errorsOnly ? undefined : 1 },
        { preserveState: true },
    );
}

async function updateCell(rowId, field, value) {
    saving.value = `${rowId}-${field}`;

    await fetch(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/rows/${rowId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify({ field, value }),
    });

    saving.value = null;
    router.reload({ only: ['rows', 'batch'] });
}

function commit() {
    router.post(`/${props.routePrefix}/${props.importerKey}/${props.batch.id}/commit`);
}
</script>

<template>
    <div class="bulk-upload-wizard">
        <WizardSteps :step="step" />
        <h1>Review your data</h1>
        <p>{{ batch.valid_rows }} of {{ batch.total_rows }} rows are ready to import.</p>

        <label>
            <input type="checkbox" :checked="errorsOnly" @change="toggleErrorsOnly"> View errors only
        </label>

        <table>
            <thead>
                <tr>
                    <th class="status-column">Status</th>
                    <th v-for="(fieldLabel, field) in fields" :key="field">{{ fieldLabel }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows.data" :key="row.id">
                    <td class="status-column">
                        <span v-if="VALID_ROW_STATUSES.includes(row.status)" class="status-icon is-valid" role="img" aria-label="Valid">
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span v-else-if="row.status === 'pending'" class="status-icon is-neutral" role="img" aria-label="Pending"></span>
                        <span v-else class="status-icon is-invalid" role="img" aria-label="Invalid">
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                <path d="M8 4.5V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                <circle cx="8" cy="11.5" r="1" fill="currentColor" />
                            </svg>
                        </span>
                    </td>
                    <td v-for="(fieldLabel, field) in fields" :key="field" :class="{ 'cell-invalid': row.errors?.[field] }">
                        <input
                            type="text"
                            :value="row.mapped_data?.[field] ?? ''"
                            :disabled="saving === `${row.id}-${field}`"
                            @blur="updateCell(row.id, field, $event.target.value)"
                        >
                        <small v-if="row.errors?.[field]" class="error">{{ row.errors[field].join(' ') }}</small>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="pagination">
            <button v-if="rows.prev_page_url" type="button" @click="router.get(rows.prev_page_url)">Previous page</button>
            <button v-if="rows.next_page_url" type="button" @click="router.get(rows.next_page_url)">Next page</button>
        </div>

        <div class="actions">
            <button type="button" class="btn-secondary" @click="goToPrevious">Previous</button>
            <button type="button" @click="commit">Import valid rows</button>
        </div>
    </div>
</template>
