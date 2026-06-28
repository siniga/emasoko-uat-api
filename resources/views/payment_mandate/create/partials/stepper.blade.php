@php
    $currentStep = $currentStep ?? 1;
    $steps = [
        1 => ['label' => 'Channel and Customer', 'hint' => 'Choose the account and payer details'],
        2 => ['label' => 'Deduction Terms', 'hint' => 'Define amount, frequency, and schedule'],
        3 => ['label' => 'Review', 'hint' => 'Confirm details before consent'],
    ];
@endphp

<div class="sp-stepper sp-stepper--3">
    @foreach ($steps as $number => $step)
        <div
            class="sp-step {{ $number < $currentStep ? 'sp-step--done' : '' }} {{ $number === $currentStep ? 'sp-step--current' : '' }}">
            <span class="sp-step__number">{{ $number }}</span>
            <div>
                <span class="sp-step__title">{{ $step['label'] }}</span>
                <span class="sp-step__meta">{{ $step['hint'] }}</span>
            </div>
        </div>
    @endforeach
</div>
