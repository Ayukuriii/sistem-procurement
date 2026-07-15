<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class Select2Response
{
    /**
     * @param  callable(mixed): array{id: string, text: string}  $map
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $map): JsonResponse
    {
        $results = collect($paginator->items())->map($map)->values()->all();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $paginator->currentPage() < $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Map items to options, optionally filter by term on id/text, then paginate in memory.
     *
     * @param  iterable<mixed>  $items
     * @param  callable(mixed): array{id: string, text: string}  $map
     */
    public static function fromItems(
        iterable $items,
        callable $map,
        ?string $term,
        int $page,
        int $perPage,
    ): JsonResponse {
        $options = Collection::make($items)
            ->map($map)
            ->values();

        if ($term !== null && $term !== '') {
            $needle = mb_strtolower($term);
            $options = $options->filter(function (array $option) use ($needle) {
                return str_contains(mb_strtolower($option['id']), $needle)
                    || str_contains(mb_strtolower($option['text']), $needle);
            })->values();
        }

        $total = $options->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $results = $options->forPage($page, $perPage)->values()->all();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $page < $lastPage,
            ],
        ]);
    }

    public static function humanize(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
