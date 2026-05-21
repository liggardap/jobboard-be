<?php

namespace App\Interfaces;

interface SearchRepositoryInterface
{
    public function search(array $query, int $from = 0, int $size = 15): array;

    public function index(int $id, array $document): void;

    public function delete(int $id): void;
}
