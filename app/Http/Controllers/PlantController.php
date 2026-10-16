<?php

namespace App\Http\Controllers;

use App\Models\CareLog;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PlantController extends Controller
{
    private function isMobile(Request $request): bool
    {
        return (bool) preg_match('/Mobi|Android|iPhone|iPad/i', $request->userAgent() ?? '');
    }

    private function ownPlant(Plant $plant): Plant
    {
        abort_unless($plant->user_id === Auth::id(), 403);

        return $plant;
    }

    // ---------- flow: Landing -> Start -> Intro -> Choose plant -> My garden ----------

    /** "Start" button on the landing page. */
    public function start(): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login')); // after login Laravel returns here
        }

        return $this->entry();
    }

    /** Breeze sends people here after login; route them into the flow. */
    public function dashboard(): RedirectResponse
    {
        return $this->entry();
    }

    private function entry(): RedirectResponse
    {
        return redirect()->route(Plant::where('user_id', Auth::id())->exists() ? 'garden' : 'intro');
    }

    public function intro(): View
    {
        return view('plants.intro');
    }

    public function choose(): View
    {
        return view('plants.choose');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:30'],
            'plant_type' => ['required', Rule::in(array_keys(config('sprout.plants')))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $plant = $request->user()->plants()->create($data + [
            'personality' => config("sprout.plants.{$data['plant_type']}.personality"),
            'hydration' => 100,
            'hunger' => 100,
            'xp' => 0,
            'decoration' => 'none',
            'state_updated_at' => now(),
        ]);

        CareLog::create(['plant_id' => $plant->id, 'action' => 'adopted', 'note' => 'Chose a ' . $plant->plant_type]);

        return redirect()->route('garden')->with('status', "{$plant->name} joined your garden!");
    }

    // ---------- My garden ----------

    public function garden(Request $request): View
    {
        $plants = $request->user()->plants()->latest()->get();

        return view('plants.garden', ['plants' => $plants, 'isMobile' => $this->isMobile($request)]);
    }

    /** Refreshed every 30 s by the garden page so thirst keeps moving. */
    public function gardenState(Request $request): JsonResponse
    {
        return response()->json($request->user()->plants->map->toState()->values());
    }

    // ---------- Plant progress ----------

    public function show(Request $request, Plant $plant): View
    {
        $this->ownPlant($plant);
        $logs = $plant->careLogs()->latest()->limit(15)->get();

        return view('plants.show', [
            'plant' => $plant,
            'logs' => $logs,
            'isMobile' => $this->isMobile($request),
        ]);
    }

    public function destroy(Plant $plant): RedirectResponse
    {
        $this->ownPlant($plant);
        $plant->forgetTarget();
        $plant->delete();

        return redirect()->route('garden')->with('status', 'Plant removed.');
    }

    public function location(Request $request, Plant $plant): JsonResponse
    {
        $this->ownPlant($plant);

        $plant->update($request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]));

        return response()->json(['ok' => true]);
    }

    // ---------- Recognition photo (taught from the AR camera) ----------

    public function storeTarget(Request $request, Plant $plant): JsonResponse
    {
        $this->ownPlant($plant);

        $request->validate([
            'target' => ['required', 'file', 'max:15360'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $dir = public_path('targets/user');
        $request->file('target')->move($dir, "plant-{$plant->id}.mind");
        if ($request->hasFile('photo')) {
            $request->file('photo')->move($dir, "plant-{$plant->id}.jpg");
        }

        return response()->json(['target' => $plant->targetUrl()]);
    }

    public function destroyTarget(Plant $plant): JsonResponse
    {
        $this->ownPlant($plant)->forgetTarget();

        return response()->json(['ok' => true]);
    }

    // ---------- Care: water, feed, decorate ----------

    public function care(Request $request, Plant $plant): JsonResponse
    {
        $this->ownPlant($plant);

        $data = $request->validate([
            'action' => ['required', Rule::in(['water', 'feed', 'decorate'])],
            'item' => ['nullable', Rule::in(array_keys(config('sprout.decorations')))],
        ]);

        $plant->settle();
        $before = $plant->stageIndex();
        $amount = config('sprout.care_amount');
        $gain = 0;

        switch ($data['action']) {
            case 'water':
                if ($plant->hydration >= 95) {
                    $message = "{$plant->name} is not thirsty right now.";
                    break;
                }
                $plant->hydration = min(100, $plant->hydration + $amount);
                $gain = config('sprout.xp.water');
                $message = "{$plant->name} enjoyed the water!";
                break;

            case 'feed':
                if ($plant->hunger >= 95) {
                    $message = "{$plant->name} is not hungry right now.";
                    break;
                }
                $plant->hunger = min(100, $plant->hunger + $amount);
                $gain = config('sprout.xp.feed');
                $message = "{$plant->name} loved the snack!";
                break;

            default: // decorate
                $item = $data['item'] ?? 'none';
                if ($item === $plant->decoration) {
                    $message = 'Already wearing that.';
                    break;
                }
                $plant->decoration = $item;
                $gain = $item === 'none' ? 0 : config('sprout.xp.decorate');
                $message = $item === 'none' ? 'Decoration removed.' : "{$plant->name} looks great!";
        }

        $plant->xp += $gain;
        $plant->save();

        if ($gain > 0) {
            CareLog::create(['plant_id' => $plant->id, 'action' => $data['action'], 'xp_gain' => $gain]);
        }

        return response()->json($this->stateAfter($plant, $before, $message, $gain));
    }

    // ---------- Interact (used by the AR camera when you tap the plant) ----------

    public function interact(Plant $plant): JsonResponse
    {
        $this->ownPlant($plant);

        $before = $plant->stageIndex();
        $gain = 0;
        $cool = config('sprout.play_cooldown_seconds');

        if (! $plant->last_played_at || $plant->last_played_at->diffInSeconds(now(), true) >= $cool) {
            $gain = config('sprout.xp.play');
            $plant->xp += $gain;
            $plant->last_played_at = now();
            $plant->save();
            CareLog::create(['plant_id' => $plant->id, 'action' => 'play', 'xp_gain' => $gain]);
        }

        $message = $gain ? "{$plant->name} giggles! +{$gain} XP" : "{$plant->name} is happy to see you.";

        return response()->json($this->stateAfter($plant, $before, $message, $gain));
    }

    private function stateAfter(Plant $plant, int $before, string $message, int $gain): array
    {
        $after = $plant->stageIndex();

        if ($after > $before) {
            CareLog::create([
                'plant_id' => $plant->id,
                'action' => 'grew',
                'note' => 'Grew into a ' . config("sprout.stages.$after.name"),
            ]);
        }

        return $plant->toState(['message' => $message, 'gain' => $gain, 'leveled_up' => $after > $before]);
    }

    // ---------- WebAR (phones only) ----------

    public function ar(Request $request): View|RedirectResponse
    {
        if (! $this->isMobile($request)) {
            return redirect()->route('garden')->with('status', 'The AR camera only works on a phone. Open this site on your phone to use it.');
        }

        $plants = $request->user()->plants()->get();
        if ($plants->isEmpty()) {
            return redirect()->route('intro');
        }

        return view('plants.ar', [
            'states' => $plants->map->toState()->values(),
            'selected' => (int) $request->query('plant', 0),
            'hasMarker' => file_exists(public_path('targets/sprout-card.mind')),
        ]);
    }

    // ---------- Community map ----------

    public function map(): View
    {
        return view('plants.map');
    }

    public function mapData(): JsonResponse
    {
        $plants = Plant::with('user:id,name')->whereNotNull('latitude')->get()->map(function (Plant $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'type' => config("sprout.plants.{$p->plant_type}.label", $p->plant_type),
                'stage' => config('sprout.stages.' . $p->stageIndex() . '.name'),
                'mood' => $p->mood(),
                'lat' => $p->latitude,
                'lng' => $p->longitude,
                'owner' => $p->user->name,
            ];
        });

        return response()->json($plants);
    }
}
