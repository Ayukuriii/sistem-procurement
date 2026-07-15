<?php

namespace App\Http\Controllers\Api\Export;

use App\Constants\Exports;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Support\Select2Catalog;
use Illuminate\Http\JsonResponse;

class ExportModuleSelectController extends Controller
{
    public function select(Select2QueryRequest $request): JsonResponse
    {
        return Select2Catalog::responseFromKeys(
            Exports::EXPORTABLE,
            $request->term(),
            $request->page(),
            $request->perPage(),
        );
    }
}
