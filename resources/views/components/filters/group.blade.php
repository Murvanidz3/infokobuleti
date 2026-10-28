@props(['title'])

<fieldset {{ $attributes->class(['border-t border-line py-5 first:border-t-0 first:pt-0']) }}>
    <legend class="float-left mb-3 w-full text-[14.5px] font-semibold text-navy">{{ $title }}</legend>
    <div class="clear-both">{{ $slot }}</div>
</fieldset>
