{{-- Status and error messages carried over a redirect (sign-in flows, account pages). --}}
@php
    $status = session('status');
    $error = session('error');
@endphp

@if ($status || $error)
    <div {{ $attributes->class(['flex flex-col gap-2']) }}>
        @if ($error)
            <p role="alert" class="flex items-start gap-2.5 rounded-tile bg-coral-tint px-4 py-3 text-[14px] leading-normal font-medium text-coral-pressed">
                <x-icon name="alert" :size="18" class="mt-px shrink-0" />{{ $error }}
            </p>
        @endif
        @if ($status)
            <p role="status" class="flex items-start gap-2.5 rounded-tile bg-surface-alt px-4 py-3 text-[14px] leading-normal font-medium text-navy">
                <x-icon name="info" :size="18" class="mt-px shrink-0 text-secondary" />{{ $status }}
            </p>
        @endif
    </div>
@endif
