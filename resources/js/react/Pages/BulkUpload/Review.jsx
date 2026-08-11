import { router } from '@inertiajs/react';
import { useState } from 'react';
import WizardSteps from '../../Components/WizardSteps';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

const VALID_ROW_STATUSES = ['valid', 'imported'];

function StatusIcon({ status }) {
    if (VALID_ROW_STATUSES.includes(status)) {
        return (
            <span className="status-icon is-valid" role="img" aria-label="Valid">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                    <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </span>
        );
    }

    if (status === 'pending') {
        return <span className="status-icon is-neutral" role="img" aria-label="Pending" />;
    }

    return (
        <span className="status-icon is-invalid" role="img" aria-label="Invalid">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                <path d="M8 4.5V9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <circle cx="8" cy="11.5" r="1" fill="currentColor" />
            </svg>
        </span>
    );
}

export default function Review({ importerKey, routePrefix, batch, fields, rows, errorsOnly, step }) {
    const [saving, setSaving] = useState(null);

    function goToPrevious() {
        router.get(`/${routePrefix}/${importerKey}/${batch.id}/map`);
    }

    function toggleErrorsOnly() {
        router.get(
            `/${routePrefix}/${importerKey}/${batch.id}/review`,
            { errors_only: errorsOnly ? undefined : 1 },
            { preserveState: true },
        );
    }

    async function updateCell(rowId, field, value) {
        setSaving(`${rowId}-${field}`);

        await fetch(`/${routePrefix}/${importerKey}/${batch.id}/rows/${rowId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ field, value }),
        });

        setSaving(null);
        router.reload({ only: ['rows', 'batch'] });
    }

    function commit() {
        router.post(`/${routePrefix}/${importerKey}/${batch.id}/commit`);
    }

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Review your data</h1>
            <p>{batch.valid_rows} of {batch.total_rows} rows are ready to import.</p>

            <label>
                <input type="checkbox" checked={!!errorsOnly} onChange={toggleErrorsOnly} /> View errors only
            </label>

            <table>
                <thead>
                    <tr>
                        <th className="status-column">Status</th>
                        {Object.entries(fields).map(([field, fieldLabel]) => <th key={field}>{fieldLabel}</th>)}
                    </tr>
                </thead>
                <tbody>
                    {rows.data.map((row) => (
                        <tr key={row.id}>
                            <td className="status-column">
                                <StatusIcon status={row.status} />
                            </td>
                            {Object.keys(fields).map((field) => (
                                <td key={field} className={row.errors?.[field] ? 'cell-invalid' : ''}>
                                    <input
                                        type="text"
                                        defaultValue={row.mapped_data?.[field] ?? ''}
                                        disabled={saving === `${row.id}-${field}`}
                                        onBlur={(e) => updateCell(row.id, field, e.target.value)}
                                    />
                                    {row.errors?.[field] && <small className="error">{row.errors[field].join(' ')}</small>}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="pagination">
                {rows.prev_page_url && <button type="button" onClick={() => router.get(rows.prev_page_url)}>Previous page</button>}
                {rows.next_page_url && <button type="button" onClick={() => router.get(rows.next_page_url)}>Next page</button>}
            </div>

            <div className="actions">
                <button type="button" className="btn-secondary" onClick={goToPrevious}>Previous</button>
                <button type="button" onClick={commit}>Import valid rows</button>
            </div>
        </div>
    );
}
