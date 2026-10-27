@extends('layouts.sprout')
@section('title', 'Community Map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .pin{display:flex;width:44px;height:44px;align-items:center;justify-content:center;border-radius:50%;background:#fff;font-size:30px;line-height:1;box-shadow:0 1px 4px rgba(0,0,0,.4)}
</style>
@endpush

@section('content')
<div class="flex items-end justify-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="font-display font-extrabold text-3xl">Community Map</h1>
        <p class="text-soil text-sm">Plants that neighbours chose to share. Share yours from its progress page.</p>
    </div>
    <p id="summary" class="text-sm font-semibold"></p>
</div>
<div id="map" class="rounded-lg overflow-hidden border border-forest/20" style="height:70vh"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const PLANT_MARKER = '🌿';
const PLANT_GROUP_RADIUS_METERS = 20;
const map = L.map('map').setView([14.5764, 121.0851], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

function distanceInMeters(a, b) {
  const radians = (degrees) => degrees * Math.PI / 180;
  const latitudeDelta = radians(b.lat - a.lat);
  const longitudeDelta = radians(b.lng - a.lng);
  const value = Math.sin(latitudeDelta / 2) ** 2
    + Math.cos(radians(a.lat)) * Math.cos(radians(b.lat)) * Math.sin(longitudeDelta / 2) ** 2;

  return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
}

fetch("{{ route('map.data') }}", { headers: { Accept: 'application/json' } })
  .then((r) => r.json())
  .then((plants) => {
    const groups = [];
    plants.forEach((p) => {
      L.circle([p.lat, p.lng], { radius: 70, stroke: false, fillOpacity: 0.25, fillColor: '#3F8F4A' }).addTo(map);
      let group = groups.find((plantsInGroup) => distanceInMeters(plantsInGroup[0], p) <= PLANT_GROUP_RADIUS_METERS);
      if (!group) {
        group = [];
        groups.push(group);
      }
      group.push(p);
    });

    groups.forEach((group) => {
      const icon = L.divIcon({ className: '', html: '<div class="pin">' + PLANT_MARKER + '</div>', iconSize: [44, 44], iconAnchor: [22, 22] });
      const box = document.createElement('div');
      box.style.maxHeight = '240px';
      box.style.overflowY = 'auto';
      group.forEach((p) => {
        const item = document.createElement('div');
        item.className = 'border-b border-forest/10 py-2 last:border-0';
        const b = document.createElement('strong'); b.textContent = p.name;
        const line = document.createElement('div'); line.textContent = p.type + ' · ' + p.stage;
        const by = document.createElement('div'); by.textContent = 'by ' + p.owner;
        item.append(b, line, by);
        box.append(item);
      });
      L.marker([group[0].lat, group[0].lng], { icon }).addTo(map).bindPopup(box);
    });

    document.getElementById('summary').textContent = plants.length + ' shared plants';
    if (plants.length) map.fitBounds(L.latLngBounds(plants.map((p) => [p.lat, p.lng])).pad(0.2));
  });
</script>
@endpush
