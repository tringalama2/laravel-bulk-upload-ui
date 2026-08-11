import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import WizardSteps from '../../Components/WizardSteps';

export default function Progress({ importerKey, routePrefix, batch, step }) {
    const [state, setState] = useState({ percentage: 0, complete: false });

    useEffect(() => {
        let cancelled = false;

        async function poll() {
            const response = await fetch(`/${routePrefix}/${importerKey}/${batch.id}/progress`, {
                headers: { Accept: 'application/json' },
            });
            const json = await response.json();

            if (cancelled) {
                return;
            }

            setState({ percentage: json.percentage, complete: json.complete });

            if (json.complete) {
                router.visit(`/${routePrefix}/${importerKey}/${batch.id}/complete`);

                return;
            }

            setTimeout(poll, 1500);
        }

        poll();

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <div className="bulk-upload-wizard">
            <WizardSteps step={step} />
            <h1>Importing…</h1>
            <progress value={state.percentage} max="100" />
            <p>{state.percentage}%</p>
        </div>
    );
}
