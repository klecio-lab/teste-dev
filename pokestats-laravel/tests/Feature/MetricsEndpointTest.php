<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/pokemons/metrics')->assertUnauthorized();
    }

    public function test_returns_ranking_with_default_parameters(): void
    {
        $this->actingAsUser();

        Pokemon::factory()->create(['name' => 'bulbasaur', 'hp' => 45]);
        Pokemon::factory()->create(['name' => 'blissey', 'hp' => 255]);
        Pokemon::factory()->create(['name' => 'charmander', 'hp' => 39]);

        $response = $this->getJson('/api/pokemons/metrics');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'blissey')
            ->assertJsonPath('data.0.hp', 255)
            ->assertJsonPath('data.2.name', 'charmander')
            ->assertJsonPath('data.2.hp', 39);

        $this->assertSame(['name', 'hp'], array_keys($response->json('data.0')));
    }

    public function test_orders_ascending(): void
    {
        $this->actingAsUser();

        Pokemon::factory()->create(['name' => 'blissey', 'hp' => 255]);
        Pokemon::factory()->create(['name' => 'shedinja', 'hp' => 1]);
        Pokemon::factory()->create(['name' => 'bulbasaur', 'hp' => 45]);

        $this->getJson('/api/pokemons/metrics?order=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'shedinja')
            ->assertJsonPath('data.2.name', 'blissey');
    }

    public function test_uses_custom_metric(): void
    {
        $this->actingAsUser();

        Pokemon::factory()->create(['name' => 'slowpoke', 'speed' => 15]);
        Pokemon::factory()->create(['name' => 'deoxys', 'speed' => 180]);

        $this->getJson('/api/pokemons/metrics?metric=speed')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'deoxys')
            ->assertJsonPath('data.0.speed', 180)
            ->assertJsonPath('data.1.name', 'slowpoke');
    }

    public function test_selects_custom_fields_and_always_includes_metric(): void
    {
        $this->actingAsUser();

        Pokemon::factory()->create(['name' => 'pikachu', 'speed' => 90, 'sprite_url' => 'https://img/25.png']);

        $response = $this->getJson('/api/pokemons/metrics?metric=speed&fields=name,sprite_url');

        $response->assertOk();

        $this->assertSame(['name', 'sprite_url', 'speed'], array_keys($response->json('data.0')));
    }

    public function test_rejects_invalid_metric(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/pokemons/metrics?metric=strength')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metric');
    }

    public function test_rejects_invalid_order_and_field(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/pokemons/metrics?order=sideways')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        $this->getJson('/api/pokemons/metrics?fields=name,password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fields.1');
    }

    public function test_paginates_results(): void
    {
        $this->actingAsUser();

        Pokemon::factory()->count(5)->sequence(
            ['name' => 'p1', 'hp' => 10],
            ['name' => 'p2', 'hp' => 20],
            ['name' => 'p3', 'hp' => 30],
            ['name' => 'p4', 'hp' => 40],
            ['name' => 'p5', 'hp' => 50],
        )->create();

        $this->getJson('/api/pokemons/metrics?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'p5')
            ->assertJsonPath('meta.last_page', 3);

        $this->getJson('/api/pokemons/metrics?per_page=2&page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'p1');
    }
}
