<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded bg-leaf px-5 py-2.5 font-semibold text-white transition hover:bg-forest focus:outline-none focus:ring-2 focus:ring-leaf focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
