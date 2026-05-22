<?php

namespace App\Interfaces;

interface ElasticsearchClientInterface
{
    /** @return array<string, mixed> */
    public function search(array $params): array;

    public function index(array $params): void;

    public function delete(array $params): void;
}
