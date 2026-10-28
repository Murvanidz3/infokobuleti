{{-- Shown while staff browse the site as another user. --}}
@php($impersonation = app(\App\Services\Auth\Impersonation::class))
@if (auth()->check() && $impersonation->isActive())
    <div class="sticky top-0 z-[60] flex flex-wrap items-center justify-center gap-3 bg-coral-pressed px-4 py-2 text-center text-[13.5px] font-semibold text-white">
        <span>{{ __('auth.impersonation.notice', ['name' => auth()->user()->name ?? auth()->user()->phone]) }}</span>
        <form method="POST" action="{{ route('impersonation.leave') }}">
            @csrf
            <button type="submit" class="rounded-full bg-white px-3 py-1 text-navy">{{ __('auth.impersonation.leave') }}</button>
        </form>
    </div>
@endif
