<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base collection for every paginated list endpoint in the API.
 *
 * Overrides Laravel's default pagination block (which includes
 * `links`, `path`, etc.) so the response always matches the contract's
 * shape:
 *
 *   { "data": [...], "meta": { current_page, per_page, total, last_page } }
 *
 * Every module-specific collection (UserCollection, SupplierCollection,
 * ProductCollection, ...) should extend this instead of
 * ResourceCollection directly, so pagination stays consistent
 * across the whole API without repeating this logic per module.
 */
abstract class ApiCollection extends ResourceCollection
{
    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
                'last_page' => $paginated['last_page'],
            ],
        ];
    }
}
