@extends('layouts.sprout')
@section('title', 'My garden')

@section('content')
<div class="flex items-end justify-between flex-wrap gap-3 mb-6">
    <div>
        <h1 class="font-display font-extrabold text-3xl">My garden</h1>
        <p class="text-soil">Care for your plants here, or meet them through the AR camera.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        @if ($isMobile)
            <a href="{{ route('ar') }}" class="bg-leaf text-white font-semibold rounded px-4 py-2">Open AR camera</a>
        @else
            <span class="rounded border border-forest/30 px-4 py-2 text-sm">AR camera: open this site on your phone</span>
        @endif
        <a href="{{ route('plants.choose') }}" class="border border-forest rounded px-4 py-2 font-semibold">Adopt another plant</a>
    </div>
</div>

@if ($plants->isEmpty())
    <div class="rounded-lg bg-white p-8 text-center">
        <p class="font-display font-bold text-xl">Your garden is empty</p>
        <p class="text-soil mt-1">Choose your first plant to get started.</p>
        <a href="{{ route('plants.choose') }}" class="inline-block mt-4 bg-leaf text-white font-semibold rounded px-5 py-2">Choose a plant</a>
    </div>
@else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($plants as $plant)
            <article data-plant="{{ $plant->id }}" data-state="{{ json_encode($plant->toState()) }}" class="rounded-xl bg-white p-4 space-y-3">
                <canvas data-canvas width="384" height="384" class="w-full max-w-[220px] mx-auto block"></canvas>
                <div class="text-center">
                    <a href="{{ route('plants.show', $plant) }}" data-name class="font-display font-bold text-lg hover:underline"></a>
                    <p class="text-sm text-soil"><span data-type></span> · <span data-stage></span> · <span data-mood class="font-semibold"></span></p>
                </div>
                @include('plants._bars')
                @include('plants._care')
                <div class="flex gap-2 text-sm">
                    <a href="{{ route('plants.show', $plant) }}" class="underline">Progress</a>
                    @if ($isMobile) <a href="{{ route('ar', ['plant' => $plant->id]) }}" class="underline font-semibold">View in AR</a> @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
window.GARDEN_CFG = { careUrl: "{{ url('/plants/__ID__/care') }}", stateUrl: "{{ route('garden.state') }}" };
</script>
<script src="{{ asset('js/garden.js') }}?v={{ filemtime(public_path('js/garden.js')) }}"></script>
@endpush
