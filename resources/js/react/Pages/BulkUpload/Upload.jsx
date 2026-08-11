import { useForm } from '@inertiajs/react';
import WizardSteps from '../../Components/WizardSteps';

export default function Upload({ label, importerKey, routePrefix, fields, step }) {
    const { setData, post, processing, errors } = useForm({ file: null });

    function submit(e) {
        e.preventDefault();
        post(`/${routePrefix}/${importerKey}/upload`, { forceFormData: true });
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

            <form onSubmit={submit}>
                <input
                    type="file"
                    accept=".csv,.txt,.xlsx,.xls"
                    onChange={(e) => setData('file', e.target.files[0])}
                />
                {errors.file && <p className="error">{errors.file}</p>}
                <button type="submit" disabled={processing}>Upload file</button>
            </form>
        </div>
    );
}
