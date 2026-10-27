<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plant_chooser_renders_an_optional_information_textbox(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('plants.choose'));

        $response->assertOk()
            ->assertSeeText('About your plant')
            ->assertSee('<textarea id="description" name="description"', false);
    }

    public function test_plant_information_is_saved_when_adopting_a_plant(): void
    {
        $user = User::factory()->create();
        $description = "A narra sapling from my grandmother's garden.";

        $response = $this->actingAs($user)->post(route('plants.store'), [
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => $description,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('garden'));
        $this->assertDatabaseHas('plants', [
            'user_id' => $user->id,
            'description' => $description,
        ]);
    }

    public function test_plant_information_is_displayed_on_the_plant_profile(): void
    {
        $user = User::factory()->create();
        $description = "A narra sapling from my grandmother's garden.";
        $plant = $user->plants()->create([
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => $description,
            'personality' => 'cheerful',
        ]);

        $response = $this->actingAs($user)->get(route('plants.show', $plant));

        $response->assertOk()->assertSeeText($description);
    }

    public function test_plant_information_can_be_updated_from_the_profile(): void
    {
        $user = User::factory()->create();
        $plant = $user->plants()->create([
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => 'An older description.',
            'personality' => 'cheerful',
        ]);
        $description = "A narra sapling from my grandmother's garden.";

        $response = $this->actingAs($user)->patch(route('plants.description', $plant), [
            'description' => $description,
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('plants.show', $plant))
            ->assertSessionHas('status', 'Plant information updated.');
        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'description' => $description,
        ]);
    }

    public function test_users_cannot_update_another_users_plant_information(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plant = $owner->plants()->create([
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => 'The owner’s notes.',
            'personality' => 'cheerful',
        ]);

        $response = $this->actingAs($otherUser)->patch(route('plants.description', $plant), [
            'description' => 'Unauthorized edit.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'description' => 'The owner’s notes.',
        ]);
    }

    public function test_plant_information_update_rejects_text_that_exceeds_the_maximum_length(): void
    {
        $user = User::factory()->create();
        $plant = $user->plants()->create([
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => 'Original information.',
            'personality' => 'cheerful',
        ]);

        $response = $this->actingAs($user)->from(route('plants.show', $plant))->patch(route('plants.description', $plant), [
            'description' => str_repeat('a', 2001),
        ]);

        $response->assertSessionHasErrors('description')->assertRedirect(route('plants.show', $plant));
        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'description' => 'Original information.',
        ]);
    }

    public function test_plant_information_is_rejected_when_it_exceeds_the_maximum_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('plants.choose'))->post(route('plants.store'), [
            'name' => 'Narra',
            'plant_type' => 'narra',
            'description' => str_repeat('a', 2001),
        ]);

        $response->assertSessionHasErrors('description')->assertRedirect(route('plants.choose'));
        $this->assertDatabaseCount('plants', 0);
    }
}
