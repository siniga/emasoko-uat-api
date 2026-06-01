@php
    $currentStep = $currentStep ?? 1;
    $steps = [
        1 => ['label' => 'Channel & Customer', 'hint' => 'Choose the account and payer details'],
        2 => ['label' => 'Deduction Terms', 'hint' => 'Define amount, frequency, and schedule'],
        3 => ['label' => 'Review & Submit', 'hint' => 'Confirm details before consent'],
    ];
@endphp

<div class="pmc-stepper">
    @foreach ($steps as $number => $step)
        <div class="pmc-step {{ $number < $currentStep ? 'is-complete' : '' }} {{ $number === $currentStep ? 'is-active' : '' }}">
            <div class="pmc-step__count">{{ $number }}</div>
            <div>
                <span class="pmc-step__label">{{ $step['label'] }}</span>
                <span class="pmc-step__hint">{{ $step['hint'] }}</span>
            </div>
        </div>
    @endforeach
</div>
