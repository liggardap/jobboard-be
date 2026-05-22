<?php

namespace Tests\Feature\Console;

use App\Interfaces\ElasticsearchIndexManagerInterface;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReindexElasticsearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_indexes_only_active_jobs_and_returns_success(): void
    {
        Job::factory()->active()->count(3)->create();
        Job::factory()->count(2)->create(); // draft — must not be indexed

        $bulkPayloads = [];

        $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);
        $manager->shouldReceive('createIndex')->once();
        $manager->shouldReceive('getAliasIndexes')->andThrow(new \Exception('no alias'));
        $manager->shouldReceive('updateAliases')->once();
        $manager->shouldReceive('getVersionedIndexes')->andThrow(new \Exception('no old indexes'));
        $manager->shouldReceive('bulk')->once()->andReturnUsing(function ($ops) use (&$bulkPayloads): void {
            $bulkPayloads = $ops;
        });

        $this->instance(ElasticsearchIndexManagerInterface::class, $manager);

        $this->artisan('es:reindex')->assertSuccessful();

        // 3 active jobs × 2 entries (action + document) = 6
        $this->assertCount(6, $bulkPayloads);
    }

    public function test_command_switches_alias_atomically_removing_old_index(): void
    {
        Job::factory()->active()->create();

        $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);
        $manager->shouldReceive('createIndex')->once();
        $manager->shouldReceive('getAliasIndexes')->andReturn(['jobs_v_old' => []]);
        $manager->shouldReceive('updateAliases')->once()->withArgs(function ($actions) {
            $hasAdd = collect($actions)->contains(fn ($a) => isset($a['add']));
            $hasRemove = collect($actions)->contains(
                fn ($a) => isset($a['remove']) && $a['remove']['index'] === 'jobs_v_old'
            );

            return $hasAdd && $hasRemove;
        });
        $manager->shouldReceive('getVersionedIndexes')->andThrow(new \Exception('no old indexes'));
        $manager->shouldReceive('bulk')->once();

        $this->instance(ElasticsearchIndexManagerInterface::class, $manager);

        $this->artisan('es:reindex')->assertSuccessful();
    }

    public function test_command_cleans_up_old_versioned_indexes(): void
    {
        Job::factory()->active()->create();

        $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);
        $manager->shouldReceive('createIndex')->once();
        $manager->shouldReceive('getAliasIndexes')->andThrow(new \Exception('no alias'));
        $manager->shouldReceive('updateAliases')->once();
        $manager->shouldReceive('getVersionedIndexes')->andReturn(['jobs_v_111' => [], 'jobs_v_222' => []]);
        $manager->shouldReceive('deleteIndex')->twice();
        $manager->shouldReceive('bulk')->once();

        $this->instance(ElasticsearchIndexManagerInterface::class, $manager);

        $this->artisan('es:reindex')->assertSuccessful();
    }

    public function test_command_with_fresh_flag_deletes_existing_indexes_first(): void
    {
        Job::factory()->active()->create();

        $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);
        $manager->shouldReceive('createIndex')->once();
        $manager->shouldReceive('getAliasIndexes')->andThrow(new \Exception('no alias'));
        $manager->shouldReceive('updateAliases')->once();
        $manager->shouldReceive('getVersionedIndexes')
            ->andReturn(['jobs_v_old1' => [], 'jobs_v_old2' => []], []);
        $manager->shouldReceive('deleteIndex')->twice();
        $manager->shouldReceive('bulk')->once();

        $this->instance(ElasticsearchIndexManagerInterface::class, $manager);

        $this->artisan('es:reindex', ['--fresh' => true])->assertSuccessful();
    }

    public function test_command_handles_first_run_with_no_jobs(): void
    {
        $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);
        $manager->shouldReceive('createIndex')->once();
        $manager->shouldReceive('getAliasIndexes')->andThrow(new \Exception('no alias'));
        $manager->shouldReceive('updateAliases')->once();
        $manager->shouldReceive('getVersionedIndexes')->andThrow(new \Exception('no old indexes'));
        $manager->shouldReceive('bulk')->never();

        $this->instance(ElasticsearchIndexManagerInterface::class, $manager);

        $this->artisan('es:reindex')->assertSuccessful();
    }
}
