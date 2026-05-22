<?php

namespace App\Providers;

use App\Interfaces\CompanyRepositoryInterface;
use App\Interfaces\CompanyServiceInterface;
use App\Interfaces\SearchRepositoryInterface;
use App\Interfaces\SearchServiceInterface;
use App\Repositories\CompanyRepository;
use App\Repositories\ElasticsearchRepository;
use App\Services\CompanyService;
use App\Services\SearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchRepositoryInterface::class, ElasticsearchRepository::class);
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
    }

    public function boot(): void
    {
        //
    }
}
