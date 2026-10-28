@props(['status'])

<div {{ $attributes->class(['border-b border-sun/30 bg-coral-tint']) }}>
    <div class="container-page flex items-center gap-3 py-3 text-[14px] text-navy">
        <x-icon name="eye" :size="18" class="shrink-0 text-coral-pressed" />
        <p>{{ __('site.preview', ['status' => $status]) }}</p>
    </div>
</div>
