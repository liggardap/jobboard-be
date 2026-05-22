<?php

namespace App\Actions\Search;

use App\Actions\BaseAction;
use App\Http\Requests\Search\SearchJobsRequest;
use App\Http\Resources\BaseResponse;
use App\Interfaces\SearchServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Get(
 *     path="/jobs",
 *     summary="Full-text search jobs via Elasticsearch",
 *     tags={"Jobs"},
 *
 *     @OA\Parameter(name="q", in="query", description="Full-text search query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="filter[category]", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="filter[location]", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="filter[employment_type]", in="query", @OA\Schema(type="string", enum={"full_time","part_time","contract","internship"})),
 *     @OA\Parameter(name="filter[salary_min]", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="filter[is_remote]", in="query", @OA\Schema(type="boolean")),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=100)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Search results",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Job")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
 *                 @OA\Property(property="aggregations", type="object")
 *             )
 *         )
 *     )
 * )
 */
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
