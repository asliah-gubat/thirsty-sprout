@extends('layouts.sprout')
@section('title', 'AR camera')



@section('content')
<div id="ar-root" class="fixed inset-x-0 bottom-0 top-[52px] bg-black overflow-hidden">
    {{-- the camera and 3D canvas are added here by ar.js --}}
    <div id="stage" class="absolute inset-0"></div>

    {{-- Start screen --}}
    <div id="intro" class="absolute inset-0 z-30 overflow-y-auto bg-forest/95 text-paper p-6">
        <div class="max-w-sm mx-auto text-center">
            <h1 class="font-display font-extrabold text-3xl">Meet your plants</h1>
            <p class="mt-2 text-sm">Allow the camera when your browser asks.</p>

            <label class="block mt-4 text-left text-sm">Which plant?
                <select id="intro-plant" class="mt-1 w-full rounded bg-paper text-forest px-3 py-2"></select>
            </label>

            <button id="go-place" class="mt-5 w-full bg-leaf text-white font-semibold rounded px-6 py-3">Place my plant</button>
            <p id="place-note" class="mt-1 text-xs text-paper/70"></p>
            <button id="go-basic" class="mt-1 text-xs underline text-paper/70">Use the basic camera instead</button>

            <button id="go-photo" class="mt-5 w-full border border-paper rounded px-6 py-3 font-semibold disabled:opacity-40">Find my plant</button>
            <p id="photo-note" class="mt-1 text-xs text-paper/70"></p>

            @if ($hasMarker)
                <button id="go-tag" class="mt-4 w-full border border-paper/50 rounded px-6 py-2 text-sm">Use Sprout Tag sticker</button>
            @endif

            <button id="go-teach" class="mt-5 w-full bg-sun text-forest font-semibold rounded px-6 py-3">Teach the camera this plant</button>
            <p class="mt-1 text-xs text-paper/70">Take a photo of the plant and its pot once. Later, "Find my plant" recognizes it.</p>

            <p id="start-error" class="mt-4 text-sm text-sun"></p>
            <p id="boot-msg" class="hidden mt-4 text-sm bg-red-900/60 rounded p-2 break-words"></p>
        </div>
    </div>

    {{-- Teach screen --}}
    <div id="teach" class="hidden absolute inset-x-0 bottom-0 z-30 bg-paper text-forest rounded-t-2xl p-4 shadow-2xl">
        <p id="teach-msg" class="text-sm text-center"></p>
        <div class="grid grid-cols-2 gap-2 mt-3">
            <button id="teach-capture" class="col-span-2 bg-leaf text-white font-semibold rounded py-2">Capture</button>
            <button id="teach-retake" class="hidden border border-forest font-semibold rounded py-2">Retake</button>
            <button id="teach-save" class="hidden bg-leaf text-white font-semibold rounded py-2">Save</button>
            <button id="teach-cancel" class="col-span-2 text-sm underline py-1">Cancel</button>
        </div>
    </div>

    <p id="hint-wrap" class="absolute top-3 inset-x-0 z-20 text-center pointer-events-none hidden"><span id="hint" class="bg-black/60 text-white text-sm rounded-full px-4 py-1 inline-block"></span></p>
    <button id="btn-exit" class="hidden absolute top-3 right-3 z-30 bg-black/60 text-white text-xs rounded-full px-3 py-1.5">Exit AR</button>
    <p id="toast" class="hidden absolute top-14 inset-x-4 z-40 text-center bg-forest text-paper rounded px-4 py-2 text-sm"></p>

    {{-- Care panel --}}
    <div id="panel" class="hidden absolute inset-x-0 bottom-0 z-20 bg-paper text-forest rounded-t-2xl p-3 shadow-2xl">
        <div class="flex items-center gap-2">
            <button id="prev" class="px-3 py-2 rounded border border-forest/30" aria-label="Previous plant">◀</button>
            <div class="flex-1 min-w-0 text-center">
                <p id="hud-name" class="font-display font-bold truncate"></p>
                <p id="hud-stage" class="text-xs text-soil"></p>
            </div>
            <button id="next" class="px-3 py-2 rounded border border-forest/30" aria-label="Next plant">▶</button>
        </div>
        <div class="grid grid-cols-3 gap-2 mt-2">
            <div class="h-2 rounded bg-forest/10 overflow-hidden"><div id="hud-hyd" class="h-full bg-water"></div></div>
            <div class="h-2 rounded bg-forest/10 overflow-hidden"><div id="hud-hung" class="h-full" style="background:#7A9E3A"></div></div>
            <div class="h-2 rounded bg-forest/10 overflow-hidden"><div id="hud-xp" class="h-full bg-sun"></div></div>
        </div>
        <p class="grid grid-cols-3 gap-2 text-[11px] text-soil text-center"><span>Water</span><span>Food</span><span>Growth</span></p>
        <div class="grid grid-cols-3 gap-2 mt-2">
            <button id="btn-water" class="bg-water text-white font-semibold rounded py-2 text-sm">Water</button>
            <button id="btn-feed" class="bg-leaf text-white font-semibold rounded py-2 text-sm">Feed</button>
            <button id="btn-decor" class="border border-forest font-semibold rounded py-2 text-sm">Decorate</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.SPROUT_AR = {
  plants: @json($states),
  selected: {{ $selected }},
  careUrl: "{{ url('/plants/__ID__/care') }}",
  interactUrl: "{{ url('/plants/__ID__/interact') }}",
  targetUrl: "{{ url('/plants/__ID__/target') }}",
  stateUrl: "{{ route('garden.state') }}",
  tagUrl: "{{ asset('targets/sprout-card.mind') }}",
  decorations: @json(config('sprout.decorations')),
};
</script>
<script>
// Shows problems on screen (phones have no console), and loads ar.js in a way older browsers understand.
window.__arBoot = { ready: false };
function bootMsg(text) {
  const el = document.getElementById('boot-msg');
  if (el) { el.textContent = text; el.classList.remove('hidden'); }
}
window.addEventListener('error', (e) => bootMsg('Script error: ' + (e.message || e.filename || 'unknown')));
window.addEventListener('unhandledrejection', (e) => bootMsg('Error: ' + (e.reason && e.reason.message || e.reason)));
setTimeout(() => {
  if (!window.__arBoot.ready) bootMsg('The AR engine did not start. Open this page in Chrome (Android) or Safari (iPhone) and check your internet connection.');
}, 6000);

(function () {
  const imports = {
    three: 'https://unpkg.com/three@0.147.0/build/three.module.js',
    'three/addons/': 'https://unpkg.com/three@0.147.0/examples/jsm/',
    'mindar-image-three': 'https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-three.prod.js',
  };
  const arJs = "{{ asset('js/ar.js') }}?v={{ filemtime(public_path('js/ar.js')) }}";
  const nativeMaps = window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('importmap');

  function add(type, text, src) {
    const s = document.createElement('script');
    s.type = type;
    if (text) s.textContent = text;
    if (src) s.src = src;
    document.head.append(s);
    return s;
  }

  if (nativeMaps) {
    add('importmap', JSON.stringify({ imports }));
    add('module', '', arJs);
  } else {
    // older browsers (some phone-brand browsers): use a small polyfill for import maps
    window.esmsInitOptions = { shimMode: true };
    const shim = add('text/javascript', '', 'https://cdn.jsdelivr.net/npm/es-module-shims@1.10.0/dist/es-module-shims.js');
    shim.onload = () => {
      add('importmap-shim', JSON.stringify({ imports }));
      add('module-shim', '', arJs);
    };
    shim.onerror = () => bootMsg('Could not download a needed helper. Check your internet connection.');
  }
})();
</script>
@endpush
