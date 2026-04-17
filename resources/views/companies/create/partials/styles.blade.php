<style>
    .sp-shell {
        --sp-primary: #153567;
        --sp-border: #dbe5f3;
        --sp-text: #274160;
        --sp-muted: #6b7c93;
        --sp-surface: #f8fbff;
    }

    .sp-hero {
        background: linear-gradient(135deg, #153567 0%, #1e4a8b 100%);
        border: 0;
        border-radius: 6px;
        overflow: hidden;
    }

    .sp-hero__body {
        padding: 0.64rem 1.2rem;
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
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0.7rem 0 0.35rem;
    }

    .sp-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0;
        max-width: 780px;
        font-size: 0.88rem;
    }

    .sp-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 0.95rem;
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
        gap: 0.55rem;
        margin-top: 0rem;
    }

    .sp-step {
        padding: 0.75rem 0.85rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.84);
        min-height: auto;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .sp-step--done {
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
    }

    .sp-step--current {
        background: #fff;
        color: var(--sp-text);
        box-shadow: 0 10px 24px rgba(11, 28, 55, 0.16);
    }

    .sp-step__number {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.18);
        color: inherit;
        flex-shrink: 0;
    }

    .sp-step--current .sp-step__number {
        background: var(--sp-primary);
        color: #fff;
    }

    .sp-step__title {
        display: block;
        font-weight: 700;
        margin-bottom: 0.08rem;
        font-size: 0.86rem;
    }

    .sp-step__meta {
        display: block;
        font-size: 0.74rem;
        opacity: 0.9;
    }

    .sp-card {
        border: 1px solid var(--sp-border);
        border-radius: 18px;
        box-shadow: 0 14px 32px rgba(25, 52, 93, 0.07);
    }

    .sp-card__body {
        padding: 1.15rem 1.0rem;
    }

    .sp-section {
        border: 0;
        border-radius: 0;
    }

    .sp-section__title {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        color: #153567 !important;
        margin-bottom: 0.5rem;
    }

    .sp-section__hint {
        color: var(--sp-muted);
        font-size: 0.82rem;
        margin-bottom: 0.8rem;
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
        padding-top: 0.85rem;
        margin-top: 0.2rem;
        border-top: 1px solid #edf2f8;
    }

    .sp-actionbar__meta {
        color: var(--sp-muted);
        font-size: 0.84rem;
    }

    .sp-table {
        border-radius: 14px;
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

    .sp-shell .form-control,
    .sp-shell .select2-container .select2-selection--single,
    .sp-shell textarea.form-control {
        min-height: 38px;
        border-radius: 10px;
        border-color: #d7e3f4;
        box-shadow: none;
    }

    .sp-shell textarea.form-control {
        min-height: 38px;
    }

    .sp-shell .form-control:focus,
    .sp-shell .select2-container .select2-selection--single:focus,
    .sp-shell textarea.form-control:focus {
        border-color: #9cb8df;
        box-shadow: 0 0 0 0.15rem rgba(21, 53, 103, 0.08);
    }

    .sp-shell .select2-container--default .select2-selection--single {
        height: 38px;
        padding-top: 4px;
    }

    .sp-shell .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }

    .sp-shell .btn {
        border-radius: 10px;
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
