@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-forest']) }}>
    {{ $value ?? $slot }}
</label>
