@extends('layouts.sprout')
@section('title', 'Community map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>.pin{font-size:26px;line-height:1;filter:drop-shadow(0 1px 2px rgba(0,0,0,.4))}</style>
@endpush

@section('content')
<div class="flex items-end justify-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="font-display font-extrabold text-3xl">Community map</h1>
        <p class="text-soil text-sm">Plants that neighbours chose to share. Share yours from its progress page.</p>
    </div>
    <p id="summary" class="text-sm font-semibold"></p>
</div>
<div id="map" class="rounded-lg overflow-hidden border border-forest/20" style="height:70vh"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const FACES = { happy: '😊', needy: '😟', wilting: '🥀' };
const map = L.map('map').setView([14.5764, 121.0851], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

fetch("{{ route('map.data') }}", { headers: { Accept: 'application/json' } })
  .then((r) => r.json())
  .then((plants) => {
    plants.forEach((p) => {
      L.circle([p.lat, p.lng], { radius: 70, stroke: false, fillOpacity: 0.25, fillColor: '#3F8F4A' }).addTo(map);
      const icon = L.divIcon({ className: '', html: '<div class="pin">' + FACES[p.mood] + '</div>', iconSize: [28, 28], iconAnchor: [14, 14] });
      const box = document.createElement('div');
      const b = document.createElement('strong'); b.textContent = p.name;
      const line = document.createElement('div'); line.textContent = p.type + ' · ' + p.stage;
      const by = document.createElement('div'); by.textContent = 'by ' + p.owner;
      box.append(b, line, by);
      L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(box);
    });
    document.getElementById('summary').textContent = plants.length + ' shared plants';
    if (plants.length) map.fitBounds(L.latLngBounds(plants.map((p) => [p.lat, p.lng])).pad(0.2));
  });
</script>
@endpush
