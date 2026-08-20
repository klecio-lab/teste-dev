<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetricQueryRequest;
use App\Http\Resources\PokemonResource;
use App\Models\Pokemon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class PokemonMetricsController extends Controller
{
    private const CACHE_TTL_SECONDS = 300;

    public function __invoke(MetricQueryRequest $request): AnonymousResourceCollection
    {
        $metric = $request->metric();
        $order = $request->order();
        $perPage = $request->perPage();
        $fields = $request->fields();
        $page = (int) $request->validated('page', 1);

        $cacheKey = sprintf(
            'pokemon_metrics:v%d:%s',
            Pokemon::metricsCacheVersion(),
            md5((string) json_encode([$metric, $order, $perPage, $fields, $page])),
        );

        $paginator = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => Pokemon::query()
                ->select($fields)
                ->orderBy($metric, $order)
                ->orderBy('external_id')
                ->paginate($perPage),
        );

        return PokemonResource::collection($paginator);
    }
}
