<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    protected $fillable = [
        'user_id', 'name', 'plant_type', 'description', 'personality', 'hydration', 'hunger', 'xp',
        'decoration', 'state_updated_at', 'last_played_at', 'latitude', 'longitude',
    ];

    protected $casts = [
        'hydration' => 'integer',
        'hunger' => 'integer',
        'xp' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'state_updated_at' => 'datetime',
        'last_played_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careLogs(): HasMany
    {
        return $this->hasMany(CareLog::class);
    }

    // ---------- thirst & hunger (calculated on demand, no background worker needed) ----------

    private function decayed(int $stored, string $key): int
    {
        $hours = max(0, $this->state_updated_at->diffInSeconds(now(), true)) / 3600;
        $lost = $hours * config("sprout.decay_per_hour.$key") * config('sprout.decay_speed');

        return max(0, (int) round($stored - $lost));
    }

    public function currentHydration(): int
    {
        return $this->decayed($this->hydration, 'hydration');
    }

    public function currentHunger(): int
    {
        return $this->decayed($this->hunger, 'hunger');
    }

    /** Save today's values as the new baseline. Call before changing hydration/hunger. */
    public function settle(): void
    {
        $this->hydration = $this->currentHydration();
        $this->hunger = $this->currentHunger();
        $this->state_updated_at = now();
    }

    /** happy (60+), needy (30-59), wilting (below 30), based on the lowest of water/food */
    public function mood(): string
    {
        $lowest = min($this->currentHydration(), $this->currentHunger());

        if ($lowest >= 60) {
            return 'happy';
        }

        return $lowest >= 30 ? 'needy' : 'wilting';
    }

    // ---------- recognition photo (compiled MindAR target taught from the AR camera) ----------

    private function userFile(string $ext): string
    {
        return public_path("targets/user/plant-{$this->id}.$ext");
    }

    public function targetUrl(): ?string
    {
        $file = $this->userFile('mind');

        return file_exists($file) ? asset("targets/user/plant-{$this->id}.mind").'?v='.filemtime($file) : null;
    }

    public function photoUrl(): ?string
    {
        $file = $this->userFile('jpg');

        return file_exists($file) ? asset("targets/user/plant-{$this->id}.jpg").'?v='.filemtime($file) : null;
    }

    public function forgetTarget(): void
    {
        foreach (['mind', 'jpg'] as $ext) {
            $file = $this->userFile($ext);
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    // ---------- growth ----------

    public function stageIndex(): int
    {
        $index = 0;
        foreach (config('sprout.stages') as $i => $stage) {
            if ($this->xp >= $stage['xp']) {
                $index = $i;
            }
        }

        return $index;
    }

    public function toState(array $extra = []): array
    {
        $stages = config('sprout.stages');
        $i = $this->stageIndex();
        $current = $stages[$i]['xp'];
        $next = $stages[$i + 1]['xp'] ?? null;
        $type = config("sprout.plants.{$this->plant_type}");

        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->plant_type,
            'type_label' => $type['label'] ?? $this->plant_type,
            'personality' => $this->personality,
            'stage' => $i,
            'stage_name' => $stages[$i]['name'],
            'mood' => $this->mood(),
            'hydration' => $this->currentHydration(),
            'hunger' => $this->currentHunger(),
            'xp' => $this->xp,
            'next_xp' => $next,
            'xp_pct' => $next ? (int) round(($this->xp - $current) / ($next - $current) * 100) : 100,
            'decoration' => $this->decoration,
            'target' => $this->targetUrl(),
            'art' => ['leaf' => $type['leaf'] ?? '#4C9A3F', 'bloom' => $type['bloom'] ?? '#F2C230', 'pot' => $type['pot'] ?? '#C8794A'],
        ], $extra);
    }
}
