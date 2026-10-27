<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Thirsty Sprout')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { display: ['"Bricolage Grotesque"', 'sans-serif'], sans: ['Figtree', 'sans-serif'] },
                colors: { forest: '#16301F', leaf: '#3F8F4A', paper: '#F2F6EE', water: '#2F86B0', sun: '#E3A82B', soil: '#7A4E2D' },
            } },
        };
    </script>
    @stack('head')
</head>
<body class="bg-paper text-forest font-sans antialiased min-h-screen">
    @php $onMobile = preg_match('/Mobi|Android|iPhone|iPad/i', request()->userAgent() ?? ''); @endphp
    <header class="bg-forest text-paper">
        <nav class="max-w-6xl mx-auto px-4 h-[52px] flex items-center gap-4">
            <a href="{{ route('home') }}" class="font-display font-extrabold text-xl tracking-tight">Thirsty Sprout</a>
            <div class="flex gap-4 text-sm ml-2">
                @auth
                    <a href="{{ route('garden') }}" class="hover:underline">My Garden</a>
                    @if ($onMobile)<a href="{{ route('ar') }}" class="hover:underline">AR Camera</a>@endif
                @endauth
                <a href="{{ route('map') }}" class="hover:underline">Community Map</a>
            </div>
            <div class="ml-auto flex items-center gap-3 text-sm">
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="bg-paper/10 hover:bg-paper/20 rounded px-3 py-1">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:underline">Log In</a>
                    <a href="{{ route('register') }}" class="bg-leaf rounded px-3 py-1 font-semibold">Sign Up</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @if (session('status') && ! $__env->hasSection('inline-status'))
            <div class="mb-4 rounded border border-leaf/40 bg-leaf/10 px-4 py-2 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any() && ! $__env->hasSection('inline-errors'))
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>

    <p id="toast" class="hidden fixed bottom-4 inset-x-4 mx-auto max-w-md text-center bg-forest text-paper rounded px-4 py-3 text-sm shadow-lg z-50"></p>

    <script src="{{ asset('js/sprout.js') }}?v={{ filemtime(public_path('js/sprout.js')) }}"></script>
    <script src="{{ asset('js/plant-art.js') }}?v={{ filemtime(public_path('js/plant-art.js')) }}"></script>
    @stack('scripts')
</body>
</html>
