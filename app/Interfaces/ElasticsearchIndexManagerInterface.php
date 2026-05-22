<?php

namespace App\Interfaces;

interface ElasticsearchIndexManagerInterface
{
    public function createIndex(string $name, array $mapping): void;

    /** @return array<string, mixed> */
    public function getAliasIndexes(string $alias): array;

    public function updateAliases(array $actions): void;

    /** @return array<string, mixed> */
    public function getVersionedIndexes(string $pattern): array;

    public function deleteIndex(string $name): void;

    public function bulk(array $operations): void;
}
