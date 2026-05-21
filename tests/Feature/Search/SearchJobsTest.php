<?php

namespace Tests\Feature\Search;

use App\Interfaces\SearchServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SearchJobsTest extends TestCase
{
    use RefreshDatabase;

    private function makeResult(array $hits = [], int $total = 0, array $aggs = []): array
    {
        return [
            'data' => $hits,
            'meta' => [
                'current_page' => 1,
                'per_page' => 15,
                'total' => $total,
                'last_page' => $total > 0 ? (int) ceil($total / 15) : 1,
            ],
            'aggregations' => $aggs,
        ];
    }

    private function jobHit(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'title' => 'Backend Engineer',
            'category' => 'engineering',
            'employment_type' => 'full_time',
            'status' => 'active',
        ], $overrides);
    }

    public function test_returns_200_with_success_structure_when_no_results(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')->once()->andReturn($this->makeResult());
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                    'aggregations',
                ],
            ])
            ->assertJson(['success' => true, 'data' => []]);
    }

    public function test_returns_active_jobs_from_elasticsearch(): void
    {
        $hits = [$this->jobHit(), $this->jobHit(['id' => 2, 'title' => 'Senior Backend'])];
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')->once()->andReturn($this->makeResult($hits, 2));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_passes_q_param_to_search_service(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => $params['q'] === 'backend')
            ->andReturn($this->makeResult([$this->jobHit()], 1));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?q=backend');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_passes_typo_query_to_search_service_for_fuzzy_matching(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => $params['q'] === 'backand')
            ->andReturn($this->makeResult([$this->jobHit()], 1));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?q=backand');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_passes_category_filter_to_search_service(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => ($params['filter']['category'] ?? null) === 'engineering')
            ->andReturn($this->makeResult([$this->jobHit()], 1));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?filter[category]=engineering');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_passes_employment_type_filter_to_search_service(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => ($params['filter']['employment_type'] ?? null) === 'full_time')
            ->andReturn($this->makeResult([$this->jobHit()], 1));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?filter[employment_type]=full_time');

        $response->assertStatus(200);
    }

    public function test_rejects_invalid_employment_type(): void
    {
        $response = $this->getJson('/api/v1/jobs?filter[employment_type]=invalid_type');

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'validation_error')
            ->assertJsonPath('status', 422);
    }

    public function test_rejects_invalid_sort_field(): void
    {
        $response = $this->getJson('/api/v1/jobs?sort=invalid_field');

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_pagination_meta_is_correct(): void
    {
        $hits = array_map(fn ($i) => $this->jobHit(['id' => $i]), range(1, 15));
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => $params['page'] == 1 && $params['per_page'] == 15)
            ->andReturn([
                'data' => $hits,
                'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 30, 'last_page' => 2],
                'aggregations' => [],
            ]);
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 15)
            ->assertJsonPath('meta.pagination.total', 30)
            ->assertJsonPath('meta.pagination.last_page', 2);
    }

    public function test_aggregations_by_category_present_in_response(): void
    {
        $aggs = ['by_category' => ['buckets' => [['key' => 'engineering', 'doc_count' => 5]]]];
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')->once()->andReturn($this->makeResult([], 0, $aggs));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('meta.aggregations.by_category.buckets.0.key', 'engineering');
    }

    public function test_passes_page_and_per_page_to_search_service(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => $params['page'] == 2 && $params['per_page'] == 10)
            ->andReturn([
                'data' => [],
                'meta' => ['current_page' => 2, 'per_page' => 10, 'total' => 25, 'last_page' => 3],
                'aggregations' => [],
            ]);
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 10);
    }

    public function test_rejects_per_page_exceeding_maximum(): void
    {
        $response = $this->getJson('/api/v1/jobs?per_page=101');

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_rejects_non_integer_page(): void
    {
        $response = $this->getJson('/api/v1/jobs?page=abc');

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_passes_is_remote_filter_to_search_service(): void
    {
        $mock = Mockery::mock(SearchServiceInterface::class);
        $mock->shouldReceive('searchJobs')
            ->once()
            ->withArgs(fn ($params) => ($params['filter']['is_remote'] ?? null) === 'true')
            ->andReturn($this->makeResult([$this->jobHit(['is_remote' => true])], 1));
        $this->app->instance(SearchServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/jobs?filter[is_remote]=true');

        $response->assertStatus(200);
    }
}
