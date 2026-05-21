<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginationResource
{
    public static function make(LengthAwarePaginator $paginator, mixed $data = null): JsonResponse
    {
        return BaseResponse::success(
            data: $data ?? $paginator->items(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }
}
