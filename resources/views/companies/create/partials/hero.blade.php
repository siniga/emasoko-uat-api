@php
    if ($selfRegistration ?? false) {
        $steps = [
            1 => ['title' => 'Information', 'meta' => 'Business profile'],
            2 => ['title' => 'Documents', 'meta' => 'Compliance files'],
            3 => ['title' => 'Representative', 'meta' => 'Contact person'],
            4 => ['title' => 'Review', 'meta' => 'Final review'],
        ];

        $displayCurrentStep = match ($currentStep) {
            4 => 3,
            5 => 4,
            default => $currentStep,
        };
    } else {
        $steps = [
            1 => ['title' => 'Information', 'meta' => 'Business profile'],
            2 => ['title' => 'Documents', 'meta' => 'Compliance files'],
            3 => ['title' => 'Accounts', 'meta' => 'Collection setup'],
            4 => ['title' => 'Representative', 'meta' => 'Authorized contact'],
            5 => ['title' => 'Preview', 'meta' => 'Final review'],
        ];

        $displayCurrentStep = $currentStep;
    }
@endphp

<div class="sp-shell">
    <div class="card sp-hero mb-2">
        <div class="sp-hero__body">
            <div class="sp-stepper">
                @foreach ($steps as $number => $step)
                    @php
                        $state = $number < $displayCurrentStep ? 'done' : ($number === $displayCurrentStep ? 'current' : 'upcoming');
                    @endphp
                    <div
                        class="sp-step {{ $state === 'done' ? 'sp-step--done' : '' }} {{ $state === 'current' ? 'sp-step--current' : '' }}">
                        <span class="sp-step__number">{{ $number }}</span>
                        <div>
                            <span class="sp-step__title">{{ $step['title'] }}</span>
                            <span class="sp-step__meta">{{ $step['meta'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
