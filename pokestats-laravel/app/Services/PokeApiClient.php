<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PokeApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.pokeapi.base_url'), '/');
    }

    /**
     * Total de Pokémons disponíveis na API.
     */
    public function count(): int
    {
        return (int) $this->get('/pokemon', ['limit' => 1])->json('count', 0);
    }

    /**
     * Uma página da listagem de Pokémons.
     *
     * @return list<array{name: string, url: string}>
     */
    public function listPage(int $limit, int $offset): array
    {
        return $this->get('/pokemon', ['limit' => $limit, 'offset' => $offset])->json('results', []);
    }

    /**
     * Busca os detalhes de vários Pokémons concorrentemente.
     * Retorna o payload decodificado indexado pela URL, ou null quando a requisição falha.
     *
     * @param  list<string>  $urls
     * @return array<string, array<string, mixed>|null>
     */
    public function details(array $urls): array
    {
        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $url) => $pool->as($url)->timeout(20)->retry(3, 500)->get($url),
            $urls,
        ));

        $details = [];

        foreach ($responses as $url => $response) {
            $details[$url] = $response instanceof Response && $response->successful()
                ? $response->json()
                : null;
        }

        return $details;
    }

    private function get(string $path, array $query = []): Response
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(20)
            ->retry(3, 500)
            ->get($path, $query)
            ->throw();
    }
}
