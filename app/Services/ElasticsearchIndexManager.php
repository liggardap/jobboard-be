<?php

namespace App\Services;

use App\Interfaces\ElasticsearchIndexManagerInterface;
use Elastic\Elasticsearch\Client;

class ElasticsearchIndexManager implements ElasticsearchIndexManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function createIndex(string $name, array $mapping): void
    {
        $this->client->indices()->create([
            'index' => $name,
            'body' => ['mappings' => $mapping],
        ]);
    }

    public function getAliasIndexes(string $alias): array
    {
        return $this->client->indices()->getAlias(['name' => $alias])->asArray();
    }

    public function updateAliases(array $actions): void
    {
        $this->client->indices()->updateAliases(['body' => ['actions' => $actions]]);
    }

    public function getVersionedIndexes(string $pattern): array
    {
        return $this->client->indices()->get(['index' => $pattern])->asArray();
    }

    public function deleteIndex(string $name): void
    {
        $this->client->indices()->delete(['index' => $name]);
    }

    public function bulk(array $operations): void
    {
        $this->client->bulk(['body' => $operations]);
    }
}
