<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestPokemonsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingests_pokemons_from_pokeapi(): void
    {
        $this->fakePokeApi();

        $this->artisan('pokemon:ingest', ['--limit' => 2])
            ->expectsOutputToContain('Ingestão concluída: 2 importado(s)/atualizado(s), 0 falha(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('pokemons', [
            'external_id' => 1,
            'name' => 'bulbasaur',
            'hp' => 45,
            'special_attack' => 65,
            'sprite_url' => 'https://img/1.png',
        ]);
        $this->assertDatabaseHas('pokemons', [
            'external_id' => 2,
            'name' => 'ivysaur',
            'hp' => 60,
        ]);
        $this->assertSame(2, Pokemon::count());
    }

    public function test_is_idempotent_when_run_twice(): void
    {
        $this->fakePokeApi();

        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Pokemon::count());
    }

    public function test_tolerates_individual_failures(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*/pokemon?limit=1' => Http::response(['count' => 2]),
            '*/pokemon?limit=*' => Http::response([
                'count' => 2,
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                    ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
                ],
            ]),
            '*/pokemon/1/' => Http::response($this->pokemonPayload(1, 'bulbasaur', 45)),
            '*/pokemon/2/' => Http::response('Server Error', 500),
        ]);

        $this->artisan('pokemon:ingest', ['--limit' => 2])
            ->expectsOutputToContain('1 importado(s)/atualizado(s), 1 falha(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('pokemons', ['external_id' => 1]);
        $this->assertDatabaseMissing('pokemons', ['external_id' => 2]);
    }

    private function fakePokeApi(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*/pokemon?limit=1' => Http::response(['count' => 2]),
            '*/pokemon?limit=*' => Http::response([
                'count' => 2,
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                    ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
                ],
            ]),
            '*/pokemon/1/' => Http::response($this->pokemonPayload(1, 'bulbasaur', 45)),
            '*/pokemon/2/' => Http::response($this->pokemonPayload(2, 'ivysaur', 60)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pokemonPayload(int $id, string $name, int $hp): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'height' => 7,
            'weight' => 69,
            'base_experience' => 64,
            'stats' => [
                ['base_stat' => $hp, 'stat' => ['name' => 'hp']],
                ['base_stat' => 49, 'stat' => ['name' => 'attack']],
                ['base_stat' => 49, 'stat' => ['name' => 'defense']],
                ['base_stat' => 65, 'stat' => ['name' => 'special-attack']],
                ['base_stat' => 65, 'stat' => ['name' => 'special-defense']],
                ['base_stat' => 45, 'stat' => ['name' => 'speed']],
            ],
            'sprites' => [
                'front_default' => "https://img/{$id}.png",
                'other' => ['official-artwork' => ['front_default' => null]],
            ],
        ];
    }
}
