import { router, useForm } from '@inertiajs/react';
import WizardSteps from '../../Components/WizardSteps';

export default function Map({ importerKey, routePrefix, batch, fields, suggestedMapping, step }) {
    const { data, setData, post, processing } = useForm({ mapping: suggestedMapping || {} });

    function setMapping(heading, field) {
        setData('mapping', { ...data.mapping, [heading]: field });
    }

    function goToPrevious() {
        router.get(`/${routePrefix}/${importerKey}`);
    }

    function submit(e) {
        e.preventDefault();
        post(`/${routePrefix}/${importerKey}/${batch.id}/map`);
    }

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Map your columns</h1>
            <p>Match each column from your file to the correct field. Sample data is shown to help you confirm the match.</p>

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
                    <button type="button" className="btn-secondary" onClick={goToPrevious}>Previous</button>
                    <button type="submit" disabled={processing}>Continue</button>
                </div>
            </form>
        </div>
    );
}
