<?php

namespace Tests\Unit\Repositories;

use App\Interfaces\ElasticsearchClientInterface;
use App\Repositories\ElasticsearchRepository;
use Mockery;
use Tests\TestCase;

class ElasticsearchRepositoryTest extends TestCase
{
    public function test_search_returns_hits_total_and_aggregations(): void
    {
        $responseData = [
            'hits' => [
                'hits' => [
                    ['_source' => ['id' => 1, 'title' => 'Backend Engineer']],
                    ['_source' => ['id' => 2, 'title' => 'Frontend Developer']],
                ],
                'total' => ['value' => 2],
            ],
            'aggregations' => ['by_category' => ['buckets' => []]],
        ];

        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->allows('search')->andReturn($responseData);

        $repo = new ElasticsearchRepository($client);
        $result = $repo->search(['query' => ['match_all' => (object) []]], 0, 15);

        $this->assertCount(2, $result['hits']);
        $this->assertEquals('Backend Engineer', $result['hits'][0]['title']);
        $this->assertEquals(2, $result['total']);
        $this->assertArrayHasKey('by_category', $result['aggregations']);
    }

    public function test_search_returns_empty_aggregations_when_not_in_response(): void
    {
        $responseData = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0],
            ],
        ];

        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->allows('search')->andReturn($responseData);

        $repo = new ElasticsearchRepository($client);
        $result = $repo->search([], 0, 15);

        $this->assertSame([], $result['aggregations']);
    }

    public function test_index_calls_client_with_correct_params(): void
    {
        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->shouldReceive('index')
            ->once()
            ->withArgs(function ($params) {
                return $params['id'] === '42'
                    && $params['body']['title'] === 'Senior Engineer';
            });

        $repo = new ElasticsearchRepository($client);
        $repo->index(42, ['title' => 'Senior Engineer']);
    }

    public function test_delete_calls_client_with_correct_id(): void
    {
        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->shouldReceive('delete')
            ->once()
            ->withArgs(fn ($params) => $params['id'] === '7');

        $repo = new ElasticsearchRepository($client);
        $repo->delete(7);
    }

    public function test_search_maps_source_documents_from_hits(): void
    {
        $source = ['id' => 10, 'title' => 'Data Engineer', 'status' => 'active'];
        $responseData = [
            'hits' => [
                'hits' => [['_source' => $source]],
                'total' => ['value' => 1],
            ],
        ];

        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->allows('search')->andReturn($responseData);

        $repo = new ElasticsearchRepository($client);
        $result = $repo->search([], 0, 15);

        $this->assertEquals($source, $result['hits'][0]);
    }

    public function test_search_passes_from_and_size_to_client(): void
    {
        $client = Mockery::mock(ElasticsearchClientInterface::class);
        $client->shouldReceive('search')
            ->once()
            ->withArgs(fn ($params) => $params['from'] === 30 && $params['size'] === 10)
            ->andReturn([
                'hits' => ['hits' => [], 'total' => ['value' => 0]],
            ]);

        $repo = new ElasticsearchRepository($client);
        $repo->search([], 30, 10);
    }
}
