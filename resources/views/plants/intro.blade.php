@extends('layouts.sprout')
@section('title', 'How it works')

@section('content')
<div class="max-w-xl mx-auto">
    <div id="slides">
        <section data-slide class="rounded-2xl bg-white p-8 text-center">
            <p class="text-6xl">🌱</p>
            <h1 class="font-display font-extrabold text-3xl mt-3">Choose a plant</h1>
            <p class="mt-2">Pick a Philippine tree or flower. Each one has its own personality and colors.</p>
        </section>
        <section data-slide class="hidden rounded-2xl bg-white p-8 text-center">
            <p class="text-6xl">💧</p>
            <h1 class="font-display font-extrabold text-3xl mt-3">Care for it</h1>
            <p class="mt-2">Plants get thirsty and hungry over time. Water and feed them, and give them a ribbon, flower, sunglasses or star. Every kind act earns XP.</p>
        </section>
        <section data-slide class="hidden rounded-2xl bg-white p-8 text-center">
            <p class="text-6xl">📱</p>
            <h1 class="font-display font-extrabold text-3xl mt-3">See it in AR</h1>
            <p class="mt-2">On your phone, open the AR camera. Tap the screen to place your plant in the room, or point at your printed Sprout Card to make it stand on the card. Tap the plant to play with it.</p>
        </section>
        <section data-slide class="hidden rounded-2xl bg-white p-8 text-center">
            <p class="text-6xl">🌳</p>
            <h1 class="font-display font-extrabold text-3xl mt-3">Watch it grow</h1>
            <p class="mt-2">XP turns a seed into a sprout, a sapling, a young tree and finally a mature tree. Share it on the community map to show off your green spot.</p>
        </section>
    </div>

    <div class="mt-5 flex items-center justify-between">
        <button id="back" class="px-4 py-2 rounded border border-forest invisible">Back</button>
        <div id="dots" class="flex gap-2"></div>
        <button id="next" class="px-4 py-2 rounded bg-leaf text-white font-semibold">Next</button>
    </div>
    <p class="text-center mt-4"><a href="{{ route('plants.choose') }}" class="text-sm underline">Skip to choose a plant</a></p>
</div>
@endsection

@push('scripts')
<script>
const slides = [...document.querySelectorAll('[data-slide]')];
const dots = document.getElementById('dots');
let i = 0;
slides.forEach(() => { const d = document.createElement('span'); d.className = 'w-2.5 h-2.5 rounded-full bg-forest/20'; dots.append(d); });

function show(n) {
  i = n;
  slides.forEach((s, k) => s.classList.toggle('hidden', k !== i));
  [...dots.children].forEach((d, k) => { d.classList.toggle('bg-leaf', k === i); d.classList.toggle('bg-forest/20', k !== i); });
  document.getElementById('back').classList.toggle('invisible', i === 0);
  document.getElementById('next').textContent = i === slides.length - 1 ? 'Choose my plant' : 'Next';
}
document.getElementById('back').addEventListener('click', () => show(i - 1));
document.getElementById('next').addEventListener('click', () => {
  if (i === slides.length - 1) location.href = "{{ route('plants.choose') }}"; else show(i + 1);
});
show(0);
</script>
@endpush
