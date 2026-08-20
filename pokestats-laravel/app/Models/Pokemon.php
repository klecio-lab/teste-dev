<?php

namespace App\Models;

use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    // "Pokemon" é incontável no pluralizador do Laravel; sem isto a tabela
    // seria resolvida como "pokemon".
    protected $table = 'pokemons';

    /**
     * Colunas que podem ser usadas como métrica de ranking e/ou retornadas
     * como campos no endpoint de métricas.
     *
     * @var list<string>
     */
    public const METRICS = [
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'height',
        'weight',
        'base_experience',
    ];

    /**
     * Campos que podem ser selecionados no endpoint de métricas.
     *
     * @var list<string>
     */
    public const SELECTABLE_FIELDS = [
        'external_id',
        'name',
        'sprite_url',
        ...self::METRICS,
    ];

    /**
     * Chave de versionamento do cache de métricas. Ao incrementar, todas as
     * respostas cacheadas passam a ser ignoradas (invalidação por versão,
     * compatível com qualquer driver de cache).
     */
    public const METRICS_CACHE_VERSION_KEY = 'pokemon_metrics:version';

    public static function metricsCacheVersion(): int
    {
        return (int) Cache::get(self::METRICS_CACHE_VERSION_KEY, 0);
    }

    public static function bumpMetricsCacheVersion(): void
    {
        Cache::forever(self::METRICS_CACHE_VERSION_KEY, now()->timestamp);
    }

    protected $fillable = [
        'external_id',
        'name',
        'height',
        'weight',
        'base_experience',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'sprite_url',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'height' => 'integer',
            'weight' => 'integer',
            'base_experience' => 'integer',
            'hp' => 'integer',
            'attack' => 'integer',
            'defense' => 'integer',
            'special_attack' => 'integer',
            'special_defense' => 'integer',
            'speed' => 'integer',
        ];
    }
}
