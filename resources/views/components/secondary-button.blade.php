<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded border border-forest/30 bg-paper px-5 py-2.5 font-semibold text-forest shadow-sm transition hover:bg-forest/10 focus:outline-none focus:ring-2 focus:ring-leaf focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
