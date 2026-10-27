<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded border border-red-700 bg-red-700 px-5 py-2.5 font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
