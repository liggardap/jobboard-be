<?php

namespace App\Providers;

use App\Interfaces\ApplicationRepositoryInterface;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\CompanyRepositoryInterface;
use App\Interfaces\CompanyServiceInterface;
use App\Interfaces\ElasticsearchIndexManagerInterface;
use App\Interfaces\JobRepositoryInterface;
use App\Interfaces\JobServiceInterface;
use App\Interfaces\SearchRepositoryInterface;
use App\Interfaces\SearchServiceInterface;
use App\Repositories\ApplicationRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ElasticsearchRepository;
use App\Repositories\JobRepository;
use App\Services\ApplicationService;
use App\Services\CompanyService;
use App\Services\ElasticsearchIndexManager;
use App\Services\JobService;
use App\Services\SearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApplicationRepositoryInterface::class, ApplicationRepository::class);
        $this->app->bind(ApplicationServiceInterface::class, ApplicationService::class);
        $this->app->bind(ElasticsearchIndexManagerInterface::class, ElasticsearchIndexManager::class);
        $this->app->bind(SearchRepositoryInterface::class, ElasticsearchRepository::class);
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(JobServiceInterface::class, JobService::class);
    }

    public function boot(): void
    {
        //
    }
}
