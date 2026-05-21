<?php

namespace App\Services;

use App\Interfaces\SearchRepositoryInterface;
use App\Interfaces\SearchServiceInterface;

class SearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly SearchRepositoryInterface $repository,
    ) {}

    public function searchJobs(array $params): array
    {
        $query = $this->buildQuery($params);
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $from = ($page - 1) * $perPage;

        $result = $this->repository->search($query, $from, $perPage);

        return [
            'data' => $result['hits'],
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'last_page' => (int) ceil($result['total'] / $perPage),
            ],
            'aggregations' => $result['aggregations'],
        ];
    }

    private function buildQuery(array $params): array
    {
        $must = [];
        $filter = [['term' => ['status' => 'active']]];

        if (! empty($params['q'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $params['q'],
                    'fields' => ['title^3', 'company.name^2', 'description^1'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        if (! empty($params['filter']['category'])) {
            $filter[] = ['term' => ['category' => $params['filter']['category']]];
        }

        if (! empty($params['filter']['location'])) {
            $filter[] = ['term' => ['location_city' => $params['filter']['location']]];
        }

        if (! empty($params['filter']['employment_type'])) {
            $filter[] = ['term' => ['employment_type' => $params['filter']['employment_type']]];
        }

        if (! empty($params['filter']['salary_min'])) {
            $filter[] = ['range' => ['salary_min' => ['gte' => (int) $params['filter']['salary_min']]]];
        }

        if (isset($params['filter']['is_remote'])) {
            $filter[] = ['term' => ['is_remote' => $params['filter']['is_remote'] === 'true']];
        }

        return [
            'query' => [
                'bool' => [
                    'must' => $must ?: [['match_all' => (object) []]],
                    'filter' => $filter,
                ],
            ],
            'sort' => [['_score' => 'desc'], ['published_at' => 'desc']],
            'aggs' => [
                'by_category' => ['terms' => ['field' => 'category']],
            ],
        ];
    }
}
