<?php

namespace App\Repositories;

use App\Interfaces\ElasticsearchClientInterface;
use App\Interfaces\SearchRepositoryInterface;

class ElasticsearchRepository implements SearchRepositoryInterface
{
    public function __construct(
        private readonly ElasticsearchClientInterface $client,
    ) {}

    public function search(array $query, int $from = 0, int $size = 15): array
    {
        $response = $this->client->search([
            'index' => config('elasticsearch.index'),
            'body' => $query,
            'from' => $from,
            'size' => $size,
        ]);

        return [
            'hits' => array_map(fn ($hit) => $hit['_source'], $response['hits']['hits']),
            'total' => $response['hits']['total']['value'],
            'aggregations' => $response['aggregations'] ?? [],
        ];
    }

    public function index(int $id, array $document): void
    {
        $this->client->index([
            'index' => config('elasticsearch.index'),
            'id' => (string) $id,
            'body' => $document,
        ]);
    }

    public function delete(int $id): void
    {
        $this->client->delete([
            'index' => config('elasticsearch.index'),
            'id' => (string) $id,
        ]);
    }
}
