import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import WizardSteps from '../../Components/WizardSteps';

export default function Progress({ importerKey, routePrefix, batch, step }) {
    const [state, setState] = useState({ percentage: 0, complete: false, error: null });

    useEffect(() => {
        let cancelled = false;

        async function poll() {
            try {
                const response = await fetch(`/${routePrefix}/${importerKey}/${batch.id}/progress`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const json = await response.json();

                if (cancelled) {
                    return;
                }

                setState({ percentage: json.percentage, complete: json.complete, error: null });

                if (json.complete) {
                    router.visit(`/${routePrefix}/${importerKey}/${batch.id}/complete`);

                    return;
                }
            } catch (error) {
                if (cancelled) {
                    return;
                }

                setState((prev) => ({ ...prev, error: "We're having trouble checking your import status. Retrying…" }));
            }

            if (!cancelled) {
                setTimeout(poll, 1500);
            }
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
            {state.error && <p className="error">{state.error}</p>}
        </div>
    );
}
