import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import WizardSteps from '../../Components/WizardSteps';

export default function Upload({ label, importerKey, routePrefix, fields, step, activeBatch }) {
    const { setData, post, processing, errors } = useForm({ file: null });
    const [actionError, setActionError] = useState(null);
    const [startingOver, setStartingOver] = useState(false);
    const [continuing, setContinuing] = useState(false);

    function submit(e) {
        e.preventDefault();
        setActionError(null);
        post(`/${routePrefix}/${importerKey}/upload`, {
            forceFormData: true,
            onError: (errs) => {
                if (!errs.file) {
                    setActionError('Something went wrong uploading your file. Please try again.');
                }
            },
        });
    }

    function startOver() {
        setActionError(null);
        setStartingOver(true);
        router.delete(`/${routePrefix}/${importerKey}/${activeBatch.id}/cancel`, {
            onError: () => {
                setActionError('Could not start over. Please try again.');
                setStartingOver(false);
            },
        });
    }

    function continueImport() {
        setActionError(null);
        setContinuing(true);
        router.get(activeBatch.resumeUrl, {}, {
            onError: () => {
                setActionError('Could not continue your import. Please try again.');
                setContinuing(false);
            },
        });
    }

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Import {label}</h1>
            <p>Upload a CSV or Excel file. The first row must contain your column headers.</p>
            <p className="hint">Accepted formats: .csv, .xlsx, .xls</p>

            <p className="expected-columns-heading">Expected columns</p>
            <ul className="expected-columns">
                {Object.values(fields).map((fieldLabel) => <li key={fieldLabel}>{fieldLabel}</li>)}
            </ul>

            {actionError && <p className="banner-error">{actionError}</p>}

            {activeBatch ? (
                <div className="notice">
                    <p>You already uploaded <strong>{activeBatch.filename}</strong>. Continue where you left off, or start over to upload a different file.</p>
                    <div className="actions">
                        {activeBatch.cancelable && (
                            <button
                                type="button"
                                className={startingOver ? 'btn-secondary is-loading' : 'btn-secondary'}
                                onClick={startOver}
                                disabled={startingOver || continuing}
                            >
                                {startingOver && <span className="spinner" aria-hidden="true" />}
                                {startingOver ? 'Starting over…' : 'Start over'}
                            </button>
                        )}
                        <button
                            type="button"
                            className={continuing ? 'is-loading' : ''}
                            onClick={continueImport}
                            disabled={startingOver || continuing}
                        >
                            {continuing && <span className="spinner" aria-hidden="true" />}
                            {continuing ? 'Continuing…' : 'Continue'}
                        </button>
                    </div>
                </div>
            ) : (
                <form onSubmit={submit}>
                    <input
                        type="file"
                        accept=".csv,.txt,.xlsx,.xls"
                        onChange={(e) => setData('file', e.target.files[0])}
                    />
                    {errors.file && <p className="error">{errors.file}</p>}
                    <button type="submit" className={processing ? 'is-loading' : ''} disabled={processing}>
                        {processing && <span className="spinner" aria-hidden="true" />}
                        {processing ? 'Uploading…' : 'Upload file'}
                    </button>
                </form>
            )}
        </div>
    );
}
