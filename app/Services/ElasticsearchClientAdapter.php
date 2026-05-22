<?php

namespace App\Services;

use App\Interfaces\ElasticsearchClientInterface;
use Elastic\Elasticsearch\Client;

class ElasticsearchClientAdapter implements ElasticsearchClientInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function search(array $params): array
    {
        return $this->client->search($params)->asArray();
    }

    public function index(array $params): void
    {
        $this->client->index($params);
    }

    public function delete(array $params): void
    {
        $this->client->delete($params);
    }
}
