<style>
    .sp-shell {
        --sp-primary: #153567;
        --sp-border: #dbe5f3;
        --sp-text: #274160;
        --sp-muted: #6b7c93;
    }

    .sp-hero {
        background: linear-gradient(135deg, #153567 0%, #1e4a8b 100%);
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(21, 53, 103, 0.16);
    }

    .sp-hero__body {
        padding: 1.5rem;
    }

    .sp-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .sp-title {
        color: #fff;
        font-size: 1.65rem;
        font-weight: 700;
        margin: 1rem 0 0.5rem;
    }

    .sp-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0;
        max-width: 780px;
    }

    .sp-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1.25rem;
    }

    .sp-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.82rem;
    }

    .sp-stepper {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.85rem;
        margin-top: 1.5rem;
    }

    .sp-step {
        padding: 1rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.82);
        min-height: 112px;
    }

    .sp-step--done {
        background: rgba(52, 195, 143, 0.18);
        color: #fff;
    }

    .sp-step--current {
        background: #fff;
        color: var(--sp-text);
        box-shadow: 0 10px 30px rgba(11, 28, 55, 0.2);
    }

    .sp-step__number {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-bottom: 0.8rem;
        background: rgba(255, 255, 255, 0.18);
        color: inherit;
    }

    .sp-step--current .sp-step__number {
        background: var(--sp-primary);
        color: #fff;
    }

    .sp-step__title {
        display: block;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .sp-step__meta {
        display: block;
        font-size: 0.82rem;
        opacity: 0.9;
    }

    .sp-card {
        border: 1px solid var(--sp-border);
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(25, 52, 93, 0.08);
    }

    .sp-card__body {
        padding: 1.5rem;
    }

    .sp-section {
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .sp-section__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--sp-primary);
        margin-bottom: 0.2rem;
    }

    .sp-section__hint {
        color: var(--sp-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .sp-form-message:empty {
        display: none;
    }

    .sp-form-message .alert {
        border-radius: 12px;
    }

    .sp-actionbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 1px solid #edf2f8;
    }

    .sp-actionbar__meta {
        color: var(--sp-muted);
        font-size: 0.9rem;
    }

    .sp-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e6edf7;
    }

    .sp-table thead th,
    .sp-table tr:first-child td {
        background: #edf4ff;
        color: var(--sp-primary);
        font-weight: 700;
        border-color: #dbe7fb;
    }

    @media (max-width: 991.98px) {
        .sp-stepper {
            grid-template-columns: 1fr;
        }

        .sp-actionbar {
            flex-direction: column;
            align-items: stretch;
        }

        .sp-actionbar .btn {
            width: 100%;
        }
    }
</style>
