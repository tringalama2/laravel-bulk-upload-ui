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
    const [cellErrors, setCellErrors] = useState({});
    const [goingBack, setGoingBack] = useState(false);
    const [committing, setCommitting] = useState(false);
    const [actionError, setActionError] = useState(null);

    function goToPrevious() {
        setActionError(null);
        setGoingBack(true);
        router.get(`/${routePrefix}/${importerKey}/${batch.id}/map`, {}, {
            onError: () => {
                setActionError('Could not go back. Please try again.');
                setGoingBack(false);
            },
        });
    }

    function toggleErrorsOnly() {
        router.get(
            `/${routePrefix}/${importerKey}/${batch.id}/review`,
            { errors_only: errorsOnly ? undefined : 1 },
            { preserveState: true },
        );
    }

    async function updateCell(rowId, field, value) {
        const key = `${rowId}-${field}`;
        setSaving(key);
        setCellErrors((prev) => ({ ...prev, [key]: null }));

        try {
            const response = await fetch(`/${routePrefix}/${importerKey}/${batch.id}/rows/${rowId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ field, value }),
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            router.reload({ only: ['rows', 'batch'] });
        } catch (error) {
            setCellErrors((prev) => ({ ...prev, [key]: 'Could not save this change. Please try again.' }));
        } finally {
            setSaving(null);
        }
    }

    function commit() {
        setActionError(null);
        setCommitting(true);
        router.post(`/${routePrefix}/${importerKey}/${batch.id}/commit`, {}, {
            onError: () => {
                setActionError('Could not start the import. Please try again.');
                setCommitting(false);
            },
        });
    }

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Review your data</h1>
            <p>{batch.valid_rows} of {batch.total_rows} rows are ready to import.</p>

            {actionError && <p className="banner-error">{actionError}</p>}

            <label>
                <input type="checkbox" checked={!!errorsOnly} onChange={toggleErrorsOnly} /> View errors only
            </label>

            <div className="pagination-bar">
                <span className="pagination-info">
                    {rows.total > 0
                        ? `${rows.from}–${rows.to} of ${rows.total} ${errorsOnly ? 'errors' : 'rows'}`
                        : `0 ${errorsOnly ? 'errors' : 'rows'}`}
                </span>
                <div className="pagination-controls">
                    <button
                        type="button"
                        className="btn-secondary pagination-btn"
                        disabled={!rows.prev_page_url}
                        onClick={() => router.get(rows.prev_page_url)}
                    >
                        ‹ Prev
                    </button>
                    <button
                        type="button"
                        className="btn-secondary pagination-btn"
                        disabled={!rows.next_page_url}
                        onClick={() => router.get(rows.next_page_url)}
                    >
                        Next ›
                    </button>
                </div>
            </div>

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
                                    {cellErrors[`${row.id}-${field}`] && <small className="error">{cellErrors[`${row.id}-${field}`]}</small>}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="actions">
                <button
                    type="button"
                    className={goingBack ? 'btn-secondary is-loading' : 'btn-secondary'}
                    onClick={goToPrevious}
                    disabled={goingBack || committing}
                >
                    {goingBack && <span className="spinner" aria-hidden="true" />}
                    {goingBack ? 'Going back…' : 'Previous'}
                </button>
                <button type="button" className={committing ? 'is-loading' : ''} onClick={commit} disabled={committing || goingBack}>
                    {committing && <span className="spinner" aria-hidden="true" />}
                    {committing ? 'Starting import…' : 'Import valid rows'}
                </button>
            </div>
        </div>
    );
}
