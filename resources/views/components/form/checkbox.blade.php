@props([
    'name',
    'value' => '1',
    'checked' => false,
])

<label class="inline-flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-navy">
    <input type="checkbox"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked(old($name) !== null ? in_array($value, (array) old($name)) : $checked)
           {{ $attributes->class(['size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral']) }}>
    <span>{{ $slot }}</span>
</label>
