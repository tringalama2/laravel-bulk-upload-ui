import { Link } from '@inertiajs/react';
import WizardSteps from '../../Components/WizardSteps';

export default function Complete({ importerKey, routePrefix, batch, fields, skippedRows, step }) {
    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Import complete</h1>
            <p>{batch.imported_rows} row(s) imported, {batch.skipped_rows} row(s) skipped.</p>
            <p><Link href={`/${routePrefix}/${importerKey}`}>Import another file</Link></p>

            {skippedRows?.length > 0 && (
                <table>
                    <thead>
                        <tr>
                            {Object.values(fields).map((fieldLabel) => <th key={fieldLabel}>{fieldLabel}</th>)}
                        </tr>
                    </thead>
                    <tbody>
                        {skippedRows.map((row) => (
                            <tr key={row.id}>
                                {Object.keys(fields).map((field) => (
                                    <td key={field}>{row.mapped_data?.[field] ?? ''}</td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
