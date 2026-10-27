@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded border border-forest/30 bg-paper px-3 py-2 text-forest shadow-sm focus:border-leaf focus:ring-leaf']) }}>
