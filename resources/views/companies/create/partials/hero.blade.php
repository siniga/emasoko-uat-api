@php
    $steps = [
        1 => ['title' => 'Information', 'meta' => 'Business profile and address'],
        2 => ['title' => 'Documents', 'meta' => 'Compliance file uploads'],
        3 => ['title' => 'Accounts', 'meta' => 'Collection setup'],
        4 => ['title' => 'Representative', 'meta' => 'Authorized contact'],
        5 => ['title' => 'Preview', 'meta' => 'Final review and submit'],
    ];
@endphp

<div class="sp-shell">
    <div class="card sp-hero mb-2">
        <div class="sp-hero__body">
            <div class="sp-stepper">
                @foreach ($steps as $number => $step)
                    @php
                        $state = $number < $currentStep ? 'done' : ($number === $currentStep ? 'current' : 'upcoming');
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
