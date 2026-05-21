<?php

namespace App\Providers;

use App\Interfaces\SearchRepositoryInterface;
use App\Interfaces\SearchServiceInterface;
use App\Repositories\ElasticsearchRepository;
use App\Services\SearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchRepositoryInterface::class, ElasticsearchRepository::class);
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
    }

    public function boot(): void
    {
        //
    }
}
