import { Fragment } from 'react';

const STEPS = [
    { key: 'upload', label: 'Upload File' },
    { key: 'map', label: 'Column Mapping' },
    { key: 'review', label: 'Review & Fix' },
    { key: 'progress', label: 'Import' },
];

export default function WizardSteps({ step }) {
    const currentKey = step === 'complete' ? 'progress' : step;
    const currentIndex = STEPS.findIndex((item) => item.key === currentKey);

    return (
        <div className="steps">
            {STEPS.map((item, index) => (
                <Fragment key={item.key}>
                    {index > 0 && (
                        <span className="step-chevron" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                <path d="M6 3L11 8L6 13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </span>
                    )}
                    <span className={index <= currentIndex ? 'step is-active' : 'step'}>
                        <span className="step-circle">
                            {index <= currentIndex && (
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                    <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            )}
                        </span>
                        {item.label}
                    </span>
                </Fragment>
            ))}
        </div>
    );
}
