<?php

namespace App\Interfaces;

interface SearchRepositoryInterface
{
    public function search(array $params): array;

    public function index(int $id, array $document): void;

    public function delete(int $id): void;
}
