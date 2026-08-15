import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import WizardSteps from '../../Components/WizardSteps';

export default function Map({ importerKey, routePrefix, batch, fields, suggestedMapping, step }) {
    const { data, setData, post, processing, errors } = useForm({ mapping: suggestedMapping || {} });
    const [actionError, setActionError] = useState(null);
    const [goingBack, setGoingBack] = useState(false);

    function setMapping(heading, field) {
        setData('mapping', { ...data.mapping, [heading]: field });
    }

    function goToPrevious() {
        setActionError(null);
        setGoingBack(true);
        router.get(`/${routePrefix}/${importerKey}`, {}, {
            onError: () => {
                setActionError('Could not go back. Please try again.');
                setGoingBack(false);
            },
        });
    }

    function submit(e) {
        e.preventDefault();
        setActionError(null);
        post(`/${routePrefix}/${importerKey}/${batch.id}/map`, {
            onError: (errs) => {
                if (!Object.keys(errs).length) {
                    setActionError('Something went wrong saving your column mapping. Please try again.');
                }
            },
        });
    }

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Map your columns</h1>
            <p>Match each column from your file to the correct field. Sample data is shown to help you confirm the match.</p>

            {actionError && <p className="banner-error">{actionError}</p>}
            {errors.mapping && <p className="banner-error">{errors.mapping}</p>}

            <form onSubmit={submit}>
                <table>
                    <thead>
                        <tr>
                            <th>File column</th>
                            <th>Sample data</th>
                            <th>Maps to</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(batch.headings || []).map((heading) => (
                            <tr key={heading}>
                                <td>{heading}</td>
                                <td>{(batch.sample_rows || [])[0]?.[heading]}</td>
                                <td>
                                    <select
                                        value={data.mapping[heading] || ''}
                                        onChange={(e) => setMapping(heading, e.target.value)}
                                    >
                                        <option value="">—</option>
                                        {Object.entries(fields).map(([field, fieldLabel]) => (
                                            <option key={field} value={field}>{fieldLabel}</option>
                                        ))}
                                    </select>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                <div className="actions">
                    <button
                        type="button"
                        className={goingBack ? 'btn-secondary is-loading' : 'btn-secondary'}
                        onClick={goToPrevious}
                        disabled={goingBack || processing}
                    >
                        {goingBack && <span className="spinner" aria-hidden="true" />}
                        {goingBack ? 'Going back…' : 'Previous'}
                    </button>
                    <button type="submit" className={processing ? 'is-loading' : ''} disabled={processing || goingBack}>
                        {processing && <span className="spinner" aria-hidden="true" />}
                        {processing ? 'Continuing…' : 'Continue'}
                    </button>
                </div>
            </form>
        </div>
    );
}
