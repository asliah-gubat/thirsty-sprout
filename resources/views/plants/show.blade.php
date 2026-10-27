@extends('layouts.sprout')
@section('title', $plant->name)

@section('content')
<a href="{{ route('garden') }}" class="text-sm underline">Back to My Garden</a>

<div class="grid md:grid-cols-2 gap-6 mt-3">
    <article data-plant="{{ $plant->id }}" data-state="{{ json_encode($plant->toState()) }}" class="rounded-xl bg-white p-5 space-y-4">
        <canvas data-canvas width="512" height="512" class="w-full max-w-[320px] mx-auto block"></canvas>
        <div class="text-center">
            <h1 data-name class="font-display font-extrabold text-3xl"></h1>
            <p class="text-soil"><span data-type></span> · <span data-stage></span> · <span data-mood class="font-semibold"></span></p>
        </div>
        @include('plants._bars')
        @include('plants._care')
        <div class="flex gap-3 flex-wrap text-sm pt-2">
            @if ($isMobile)
                <a href="{{ route('ar', ['plant' => $plant->id]) }}" class="bg-leaf text-white font-semibold rounded px-4 py-2">View in AR</a>
            @else
                <span class="text-soil">Open this site on your phone to view it in AR.</span>
            @endif
            <button type="button" id="share-map" class="border border-forest rounded px-4 py-2 font-semibold">{{ $plant->latitude ? 'Update Map Location' : 'Share on Community Map' }}</button>
            <form method="POST" action="{{ route('plants.destroy', $plant) }}" onsubmit="return confirm('Remove {{ e($plant->name) }}?')">@csrf @method('DELETE')
                <button class="border border-red-700 text-red-700 rounded px-4 py-2 font-semibold">Remove</button>
            </form>
        </div>
    </article>

    <section class="space-y-5">
        <div class="rounded-xl bg-white p-5">
            <h2 class="font-display font-bold text-xl mb-2">About This Plant</h2>
            @if ($plant->description)
                <p class="whitespace-pre-line">{{ $plant->description }}</p>
            @endif
            <form method="POST" action="{{ route('plants.description', $plant) }}" class="mt-3 space-y-2">
                @csrf
                @method('PATCH')
                <label for="description" class="block text-sm font-semibold">Edit plant information</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" class="w-full rounded border border-forest/30 px-3 py-2">{{ old('description', $plant->description) }}</textarea>
                @error('description')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button class="bg-leaf text-white font-semibold rounded px-4 py-2">Save information</button>
            </form>
        </div>

        <div class="rounded-xl bg-white p-5">
            <h2 class="font-display font-bold text-xl mb-3">Growth Stages</h2>
            <div data-plant-steps class="flex flex-wrap gap-2">
                @foreach (config('sprout.stages') as $i => $stage)
                    <span data-step="{{ $i }}" class="rounded-full border border-forest/20 px-3 py-1 text-sm bg-white">{{ $stage['name'] }} · {{ $stage['xp'] }} XP</span>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl bg-white p-5">
            <h2 class="font-display font-bold text-xl mb-2">Recognition Photo</h2>
            @if ($plant->photoUrl())
                <img src="{{ $plant->photoUrl() }}" alt="Recognition photo of {{ $plant->name }}" class="w-40 rounded border border-forest/20">
                <p class="text-sm text-soil mt-2">The AR camera can find {{ $plant->name }} by this view.</p>
                <button type="button" id="forget-photo" class="mt-2 text-sm underline">Forget this photo</button>
            @else
                <p class="text-sm text-soil">Not taught yet. On your phone, open the AR camera and choose "Teach the camera this plant".</p>
            @endif
        </div>

        <div class="rounded-xl bg-white p-5">
            <h2 class="font-display font-bold text-xl mb-3">Care History</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($logs as $log)
                    <li class="flex justify-between gap-3 border-t border-forest/10 pt-2 first:border-0 first:pt-0">
                        <span>
                            @switch($log->action)
                                @case('adopted') Adopted @break
                                @case('water') Watered @break
                                @case('feed') Fed @break
                                @case('decorate') Decorated @break
                                @case('play') Played in AR @break
                                @case('grew') {{ $log->note }} @break
                            @endswitch
                            @if ($log->xp_gain > 0) <span class="text-leaf font-semibold">+{{ $log->xp_gain }} XP</span> @endif
                        </span>
                        <span class="text-soil shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="text-soil">No activity yet.</li>
                @endforelse
            </ul>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
window.GARDEN_CFG = { careUrl: "{{ url('/plants/__ID__/care') }}" };
</script>
<script src="{{ asset('js/garden.js') }}?v={{ filemtime(public_path('js/garden.js')) }}"></script>
<script>
// the stage chips live outside the card, so repaint them whenever the card changes
const card = document.querySelector('[data-plant]');
const chips = document.querySelectorAll('[data-step]');
function paintSteps() {
  const stage = JSON.parse(card.dataset.state).stage;
  chips.forEach((el) => {
    const on = parseInt(el.dataset.step, 10) <= stage;
    el.classList.toggle('bg-leaf', on); el.classList.toggle('text-white', on); el.classList.toggle('bg-white', !on);
  });
}
paintSteps();
new MutationObserver(paintSteps).observe(card, { attributes: true, attributeFilter: ['data-state'] });

document.getElementById('forget-photo')?.addEventListener('click', async () => {
  await Sprout.send("{{ route('plants.target.destroy', $plant) }}", null, 'DELETE');
  location.reload();
});

document.getElementById('share-map').addEventListener('click', () => {
  if (!navigator.geolocation) return alert('This browser cannot share a location.');
  navigator.geolocation.getCurrentPosition(async (p) => {
    const { ok } = await Sprout.send("{{ route('plants.location', $plant) }}", { latitude: p.coords.latitude, longitude: p.coords.longitude }, 'PATCH');
    alert(ok ? 'Your plant is now on the community map.' : 'Could not save the location.');
  }, () => alert('Location was blocked. Allow it in your browser settings.'));
});
</script>
@endpush
