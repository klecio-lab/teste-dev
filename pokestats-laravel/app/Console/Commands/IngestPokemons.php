<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use App\Services\PokeApiClient;
use Illuminate\Console\Command;

class IngestPokemons extends Command
{
    protected $signature = 'pokemon:ingest
        {--limit= : Quantidade máxima de Pokémons a importar (padrão: todos a partir do offset)}
        {--offset=0 : Deslocamento inicial na listagem da PokeAPI}
        {--concurrency=10 : Número de requisições HTTP concorrentes}
        {--page-size=100 : Tamanho das páginas da listagem (máx. 100)}';

    protected $description = 'Importa Pokémons da PokeAPI para o banco de dados local';

    private const UPSERT_BATCH = 100;

    private const UPDATE_COLUMNS = [
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
        'updated_at',
    ];

    public function handle(PokeApiClient $client): int
    {
        $offset = max(0, (int) $this->option('offset'));
        $limit = is_numeric($this->option('limit')) ? max(0, (int) $this->option('limit')) : null;
        $concurrency = max(1, (int) $this->option('concurrency'));
        $pageSize = min(100, max(1, (int) $this->option('page-size')));

        $available = max(0, $client->count() - $offset);
        $target = $limit !== null ? min($limit, $available) : $available;

        if ($target === 0) {
            $this->warn('Nenhum Pokémon para importar.');

            return self::SUCCESS;
        }

        $this->info("Importando {$target} Pokémon(s) a partir do offset {$offset}...");

        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $imported = 0;
        $failed = 0;
        $processed = 0;
        $rows = [];

        while ($processed < $target) {
            $entries = $client->listPage(min($pageSize, $target - $processed), $offset + $processed);

            if ($entries === []) {
                break;
            }

            foreach (array_chunk($entries, $concurrency) as $chunk) {
                foreach ($client->details(array_column($chunk, 'url')) as $url => $detail) {
                    $bar->advance();

                    if ($detail === null) {
                        $failed++;
                        $this->newLine();
                        $this->warn("Falha ao buscar detalhes: {$url}");

                        continue;
                    }

                    $rows[] = $this->mapToRow($detail);
                    $imported++;

                    if (count($rows) >= self::UPSERT_BATCH) {
                        $this->upsert($rows);
                        $rows = [];
                    }
                }
            }

            $processed += count($entries);
        }

        if ($rows !== []) {
            $this->upsert($rows);
        }

        $bar->finish();
        $this->newLine(2);

        if ($imported > 0) {
            Pokemon::bumpMetricsCacheVersion();
        }

        $this->info("Ingestão concluída: {$imported} importado(s)/atualizado(s), {$failed} falha(s).");

        return $imported === 0 && $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsert(array $rows): void
    {
        Pokemon::upsert($rows, ['external_id'], self::UPDATE_COLUMNS);
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function mapToRow(array $detail): array
    {
        $stats = collect($detail['stats'] ?? [])->pluck('base_stat', 'stat.name');

        return [
            'external_id' => (int) $detail['id'],
            'name' => (string) $detail['name'],
            'height' => (int) ($detail['height'] ?? 0),
            'weight' => (int) ($detail['weight'] ?? 0),
            'base_experience' => $detail['base_experience'] ?? null,
            'hp' => (int) $stats->get('hp', 0),
            'attack' => (int) $stats->get('attack', 0),
            'defense' => (int) $stats->get('defense', 0),
            'special_attack' => (int) $stats->get('special-attack', 0),
            'special_defense' => (int) $stats->get('special-defense', 0),
            'speed' => (int) $stats->get('speed', 0),
            'sprite_url' => $detail['sprites']['other']['official-artwork']['front_default']
                ?? $detail['sprites']['front_default']
                ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
