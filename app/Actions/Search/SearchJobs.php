<?php

namespace App\Actions\Search;

use App\Actions\BaseAction;
use App\Http\Requests\Search\SearchJobsRequest;
use App\Http\Resources\BaseResponse;
use App\Interfaces\SearchServiceInterface;
use Illuminate\Http\JsonResponse;

class SearchJobs extends BaseAction
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
    ) {}

    public function handle(SearchJobsRequest $request): JsonResponse
    {
        $params = [
            'q' => $request->query('q'),
            'filter' => $request->query('filter', []),
            'sort' => $request->query('sort', 'published_at'),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 15),
        ];

        $result = $this->searchService->searchJobs($params);

        return BaseResponse::success($result['data'], 200, [
            'pagination' => $result['meta'],
            'aggregations' => $result['aggregations'],
        ]);
    }
}
