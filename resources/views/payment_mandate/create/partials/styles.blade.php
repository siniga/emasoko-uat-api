<style>
    [x-cloak] {
        display: none !important;
    }

    .pmc-shell {
        --pmc-primary: #153567;
        --pmc-border: #dbe5f3;
        --pmc-text: #274160;
        --pmc-muted: #6b7c93;
        --pmc-surface: #f8fbff;
    }

    .pmc-hero {
        background: linear-gradient(135deg, #153567 0%, #1e4a8b 100%);
        border: 0;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 14px 34px rgba(21, 53, 103, 0.12);
        color: #fff;
        position: relative;
    }

    .pmc-hero::after {
        content: '';
        position: absolute;
        inset: auto -40px -70px auto;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
    }

    .pmc-card {
        border: 1px solid var(--pmc-border);
        border-radius: 6px;
        box-shadow: 0 14px 32px rgba(25, 52, 93, 0.07);
    }

    .pmc-hero .card-body,
    .pmc-card .card-body {
        padding: 1.15rem 1.2rem;
    }

    .pmc-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.8rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .pmc-title {
        color: #fff;
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0.7rem 0 0.35rem;
    }

    .pmc-subtitle {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.88rem;
        margin-bottom: 0;
        max-width: 780px;
    }

    .pmc-chip-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-top: 0.95rem;
    }

    .pmc-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff;
        border-radius: 999px;
        padding: 0.45rem 0.75rem;
        font-size: 0.82rem;
    }

    .pmc-stepper {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    .pmc-step {
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.84);
        padding: 0.75rem 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .pmc-step.is-complete {
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
    }

    .pmc-step.is-active {
        background: #fff;
        color: var(--pmc-text);
        box-shadow: 0 10px 24px rgba(11, 28, 55, 0.16);
    }

    .pmc-step__count {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        color: inherit;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pmc-step.is-active .pmc-step__count,
    .pmc-step.is-complete .pmc-step__count {
        background: var(--pmc-primary);
        color: #fff;
    }

    .pmc-step__label {
        display: block;
        font-weight: 700;
        margin-bottom: 0.08rem;
        font-size: 0.86rem;
    }

    .pmc-step__hint {
        display: block;
        font-size: 0.74rem;
        opacity: 0.9;
    }

    .pmc-section + .pmc-section {
        margin-top: 1rem;
    }

    .pmc-section__title {
        font-size: 14px;
        font-weight: 600;
        color: #153567 !important;
        margin-bottom: 0.2rem;
    }

    .pmc-section__hint {
        color: var(--pmc-muted);
        font-size: 0.82rem;
        margin-bottom: 0.8rem;
    }

    .pmc-field {
        padding: 0;
        height: auto;
        background: transparent;
        border: 0;
    }

    .pmc-field label {
        display: block;
        color: var(--pmc-primary);
        font-weight: 400;
        margin-bottom: 0.35rem;
        font-size: 0.84rem;
        line-height: 1.35;
    }

    .pmc-field label small {
        color: var(--pmc-muted);
        font-size: 0.78rem;
        font-weight: 400;
    }

    .pmc-field .form-control,
    .pmc-field .select2-container .select2-selection--single {
        border-radius: 10px;
        min-height: 38px;
        border-color: #d7e3f4;
        box-shadow: none;
    }

    .pmc-field .form-control:focus,
    .pmc-field .select2-container .select2-selection--single:focus {
        border-color: #9cb8df;
        box-shadow: 0 0 0 0.15rem rgba(21, 53, 103, 0.08);
    }

    .pmc-summary {
        border: 1px solid var(--pmc-border);
        border-radius: 6px;
        background: linear-gradient(180deg, #fff 0%, var(--pmc-surface) 100%);
        padding: 1rem 1.05rem;
        height: 100%;
    }

    .pmc-summary__label {
        color: var(--pmc-muted);
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
        text-transform: uppercase;
    }

    .pmc-summary__value {
        color: var(--pmc-primary);
        font-size: 1.2rem;
        font-weight: 700;
    }

    .pmc-table {
        border-radius: 6px;
        overflow: hidden;
        /* border: 1px solid #dfe8f4; */
        margin-bottom: 0;
    }

    .pmc-table thead th,
    .pmc-table thead td,
    .pmc-table th {
        background: #edf4ff;
        color: var(--pmc-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .pmc-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .pmc-note {
        border: 1px dashed #cfe0f4;
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        background: #fbfdff;
        color: var(--pmc-muted);
        font-size: 0.82rem;
        margin-bottom: 0.85rem;
    }

    .pmc-shell .row.g-3 {
        --bs-gutter-y: 0.8rem;
    }

    .pmc-shell .btn {
        border-radius: 10px;
    }

    .pmc-shell .select2-container .select2-selection--single {
        height: 38px;
        padding-top: 4px;
    }

    .pmc-shell .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }

    @media (max-width: 991px) {
        .pmc-stepper {
            grid-template-columns: 1fr;
        }
    }
</style>
