<?php

namespace Tests\Unit\Services;

use App\Interfaces\SearchRepositoryInterface;
use App\Services\SearchService;
use Mockery;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    public function test_search_jobs_passes_match_all_query_when_no_q_param(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $must = $query['query']['bool']['must'];

                return isset($must[0]['match_all']);
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs([]);
    }

    public function test_search_jobs_passes_multi_match_query_when_q_param_given(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $must = $query['query']['bool']['must'];

                return isset($must[0]['multi_match'])
                    && $must[0]['multi_match']['query'] === 'backend'
                    && $must[0]['multi_match']['fuzziness'] === 'AUTO';
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['q' => 'backend']);
    }

    public function test_search_jobs_always_filters_by_active_status(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['term']['status'] ?? null) === 'active');
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs([]);
    }

    public function test_search_jobs_adds_category_filter_when_provided(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['term']['category'] ?? null) === 'Engineering');
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['filter' => ['category' => 'Engineering']]);
    }

    public function test_search_jobs_adds_employment_type_filter_when_provided(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['term']['employment_type'] ?? null) === 'full_time');
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['filter' => ['employment_type' => 'full_time']]);
    }

    public function test_search_jobs_adds_location_filter_when_provided(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['term']['location_city'] ?? null) === 'Jakarta');
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['filter' => ['location' => 'Jakarta']]);
    }

    public function test_search_jobs_adds_salary_min_filter_when_provided(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['range']['salary_min']['gte'] ?? null) === 5000000);
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['filter' => ['salary_min' => '5000000']]);
    }

    public function test_search_jobs_adds_is_remote_filter_when_provided(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                $filter = $query['query']['bool']['filter'];

                return collect($filter)->contains(fn ($f) => ($f['term']['is_remote'] ?? null) === true);
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['filter' => ['is_remote' => 'true']]);
    }

    public function test_search_jobs_calculates_pagination_from_to_from_page(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(fn ($_query, $from, $size) => $from === 30 && $size === 15)
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs(['page' => 3, 'per_page' => 15]);
    }

    public function test_search_jobs_returns_correct_pagination_meta(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->andReturn(['hits' => [], 'total' => 45, 'aggregations' => []]);

        $service = new SearchService($repo);
        $result = $service->searchJobs(['page' => 2, 'per_page' => 15]);

        $this->assertEquals(2, $result['meta']['current_page']);
        $this->assertEquals(15, $result['meta']['per_page']);
        $this->assertEquals(45, $result['meta']['total']);
        $this->assertEquals(3, $result['meta']['last_page']);
    }

    public function test_search_jobs_returns_aggregations_from_repository(): void
    {
        $aggregations = ['by_category' => ['buckets' => [['key' => 'Engineering', 'doc_count' => 5]]]];

        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => $aggregations]);

        $service = new SearchService($repo);
        $result = $service->searchJobs([]);

        $this->assertEquals($aggregations, $result['aggregations']);
    }

    public function test_search_jobs_includes_by_category_aggregation_in_query(): void
    {
        $repo = Mockery::mock(SearchRepositoryInterface::class);
        $repo->shouldReceive('search')
            ->once()
            ->withArgs(function ($query) {
                return isset($query['aggs']['by_category']['terms']['field'])
                    && $query['aggs']['by_category']['terms']['field'] === 'category';
            })
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => []]);

        $service = new SearchService($repo);
        $service->searchJobs([]);
    }
}
