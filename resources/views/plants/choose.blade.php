@extends('layouts.sprout')
@section('title', 'Choose a plant')

@section('content')
<h1 class="font-display font-extrabold text-3xl mb-1">Choose your plant</h1>
<p class="text-soil mb-5">You can adopt more later from your garden.</p>

<form method="POST" action="{{ route('plants.store') }}" class="space-y-6">
    @csrf
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach (config('sprout.plants') as $key => $p)
            @php
                $preview = ['stage' => 3, 'mood' => 'happy', 'personality' => $p['personality'], 'decoration' => 'none',
                            'art' => ['leaf' => $p['leaf'], 'bloom' => $p['bloom'], 'pot' => $p['pot']]];
            @endphp
            <label class="cursor-pointer">
                <input type="radio" name="plant_type" value="{{ $key }}" class="peer sr-only" required @checked(old('plant_type', 'narra') === $key)>
                <div class="rounded-xl bg-white p-3 text-center border-2 border-transparent peer-checked:border-leaf peer-focus-visible:ring-2 peer-focus-visible:ring-water">
                    <canvas width="256" height="256" data-preview="{{ json_encode($preview) }}" class="w-full"></canvas>
                    <p class="font-display font-bold">{{ $p['label'] }}</p>
                    <p class="text-xs text-soil">{{ ucfirst($p['personality']) }} · {{ $p['blurb'] }}</p>
                </div>
            </label>
        @endforeach
    </div>

    <div class="max-w-md space-y-3">
        <div>
            <label for="name" class="block font-semibold mb-1">Name your plant</label>
            <input id="name" name="name" maxlength="30" value="{{ old('name') }}" required class="w-full rounded border border-forest/30 px-3 py-2">
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" id="share" class="mt-1">
            <span>Show this plant on the community map. This shares your current location.</span>
        </label>
        <p id="geo-note" class="text-sm text-soil"></p>
        <input type="hidden" name="latitude" id="lat">
        <input type="hidden" name="longitude" id="lng">

        <button class="bg-leaf text-white font-semibold rounded px-6 py-3">Plant it</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-preview]').forEach((c) => PlantArt.draw(c, JSON.parse(c.dataset.preview)));

const share = document.getElementById('share');
share.addEventListener('change', () => {
  const note = document.getElementById('geo-note');
  const lat = document.getElementById('lat'), lng = document.getElementById('lng');
  if (!share.checked) { lat.value = ''; lng.value = ''; note.textContent = ''; return; }
  note.textContent = 'Finding your location…';
  if (!navigator.geolocation) { share.checked = false; note.textContent = 'This browser cannot share a location.'; return; }
  navigator.geolocation.getCurrentPosition(
    (p) => { lat.value = p.coords.latitude.toFixed(6); lng.value = p.coords.longitude.toFixed(6); note.textContent = 'Location saved for the map.'; },
    () => { share.checked = false; note.textContent = 'Location was blocked. Allow it in your browser settings to appear on the map.'; },
    { timeout: 15000 }
  );
});
</script>
@endpush
