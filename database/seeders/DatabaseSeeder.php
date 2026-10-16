<?php

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo login: test@example.com / password
        $demo = User::factory()->create(['name' => 'Test Sprout', 'email' => 'test@example.com']);

        foreach ([['Lola Leaf', 'narra', 120, 'ribbon'], ['Binhi', 'sampaguita', 10, 'none'], ['Tatay Oak', 'mahogany', 320, 'sunglasses']] as [$name, $type, $xp, $decor]) {
            Plant::create([
                'user_id' => $demo->id, 'name' => $name, 'plant_type' => $type,
                'personality' => config("sprout.plants.$type.personality"),
                'hydration' => fake()->numberBetween(45, 100), 'hunger' => fake()->numberBetween(45, 100),
                'xp' => $xp, 'decoration' => $decor, 'state_updated_at' => now(),
            ]);
        }

        // Neighbours with plants shared on the community map (around Pasig City)
        $names = ['Sprouty', 'Dahon', 'Luntian', 'Tala', 'Ugat', 'Sinag', 'Bayani', 'Hardin'];
        foreach (User::factory(4)->create() as $user) {
            for ($i = 0; $i < 2; $i++) {
                $type = fake()->randomElement(array_keys(config('sprout.plants')));
                Plant::create([
                    'user_id' => $user->id, 'name' => fake()->randomElement($names), 'plant_type' => $type,
                    'personality' => config("sprout.plants.$type.personality"),
                    'hydration' => fake()->numberBetween(30, 100), 'hunger' => fake()->numberBetween(30, 100),
                    'xp' => fake()->randomElement([0, 40, 120, 300, 520]), 'decoration' => 'none',
                    'state_updated_at' => now(),
                    'latitude' => 14.5764 + fake()->randomFloat(5, -0.02, 0.02),
                    'longitude' => 121.0851 + fake()->randomFloat(5, -0.02, 0.02),
                ]);
            }
        }
    }
}
