<?php

namespace App\Support;

use App\Http\Responses\Select2Response;

class Select2Catalog
{
    /**
     * Build Select2 options from a list of string keys using a text resolver.
     *
     * @param  list<string>  $keys
     * @param  callable(string): string  $textResolver
     * @return list<array{id: string, text: string}>
     */
    public static function fromKeys(array $keys, callable $textResolver): array
    {
        return array_map(
            fn (string $key) => [
                'id' => $key,
                'text' => $textResolver($key),
            ],
            $keys,
        );
    }

    /**
     * @param  list<string>  $keys
     * @param  callable(string): string|null  $textResolver  defaults to Select2Response::humanize
     */
    public static function responseFromKeys(
        array $keys,
        ?string $term,
        int $page,
        int $perPage,
        ?callable $textResolver = null,
    ) {
        $textResolver ??= fn (string $key) => Select2Response::humanize($key);

        return Select2Response::fromItems(
            self::fromKeys($keys, $textResolver),
            fn (array $option) => $option,
            $term,
            $page,
            $perPage,
        );
    }
}
