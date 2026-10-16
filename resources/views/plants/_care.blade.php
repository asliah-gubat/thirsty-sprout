{{-- Care buttons shared by the garden card and the progress page --}}
<div class="flex flex-wrap items-center gap-2">
    <button type="button" data-care="water" class="bg-water text-white font-semibold rounded px-3 py-1.5 text-sm">Water</button>
    <button type="button" data-care="feed" class="bg-leaf text-white font-semibold rounded px-3 py-1.5 text-sm">Feed</button>
    <label class="text-sm flex items-center gap-1">
        <span class="sr-only">Decoration</span>
        <select data-decorate class="rounded border border-forest/30 bg-white px-2 py-1.5 text-sm">
            @foreach (config('sprout.decorations') as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
        </select>
    </label>
</div>
