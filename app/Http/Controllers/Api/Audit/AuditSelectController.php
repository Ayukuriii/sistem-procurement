<?php

namespace App\Http\Controllers\Api\Audit;

use App\Constants\Audits;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Http\Responses\Select2Response;
use App\Support\Select2Catalog;
use Illuminate\Http\JsonResponse;

class AuditSelectController extends Controller
{
    public function types(Select2QueryRequest $request): JsonResponse
    {
        return Select2Catalog::responseFromKeys(
            Audits::types(),
            $request->term(),
            $request->page(),
            $request->perPage(),
            fn (string $key) => Select2Response::humanize($key),
        );
    }

    public function events(Select2QueryRequest $request): JsonResponse
    {
        return Select2Catalog::responseFromKeys(
            Audits::events(),
            $request->term(),
            $request->page(),
            $request->perPage(),
            fn (string $key) => Audits::actionLabel($key),
        );
    }
}
