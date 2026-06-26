<?php

namespace App\Console\Commands;

use App\Interfaces\ElasticsearchIndexManagerInterface;
use App\Interfaces\JobRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReindexElasticsearch extends Command
{
    protected $signature = 'es:reindex {--fresh : Delete all existing versioned indexes before reindexing}';

    protected $description = 'Reindex all active jobs from MySQL to Elasticsearch with zero-downtime alias swap';

    public function __construct(
        private readonly ElasticsearchIndexManagerInterface $indexManager,
        private readonly JobRepositoryInterface $jobRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $alias = config('elasticsearch.index');
        $newIndex = $alias.'_v'.time();

        if ($this->option('fresh')) {
            $this->deleteVersionedIndexes($alias);
        }

        $startedAt = Carbon::now();

        $this->info("Creating index: {$newIndex}");
        $this->indexManager->createIndex($newIndex, $this->mapping());

        $this->info('Indexing active jobs...');
        $indexed = $this->indexAllJobs($newIndex);
        $this->info("Indexed {$indexed} jobs.");

        $this->info('Switching alias...');
        $this->switchAlias($alias, $newIndex);

        $this->info('Running delta sync for jobs created/updated during reindex...');
        $delta = $this->indexJobsUpdatedSince($startedAt, $newIndex);
        $this->info("Delta sync: {$delta} jobs resynced.");

        $this->info('Cleaning up old indexes...');
        $this->cleanupOldIndexes($alias, $newIndex);

        $this->info('Reindex complete.');

        return Command::SUCCESS;
    }

    private function indexJobsUpdatedSince(Carbon $since, string $indexName): int
    {
        $indexed = 0;

        $this->jobRepository->chunkUpdatedSince($since, 100, function ($jobs) use ($indexName, &$indexed): void {
            $operations = [];

            foreach ($jobs as $job) {
                $operations[] = ['index' => ['_index' => $indexName, '_id' => (string) $job->id]];
                $operations[] = $job->toSearchArray();
            }

            if (! empty($operations)) {
                $this->indexManager->bulk($operations);
                $indexed += count($jobs);
            }
        });

        return $indexed;
    }

    private function indexAllJobs(string $indexName): int
    {
        $indexed = 0;
        $bar = $this->output->createProgressBar();
        $bar->start();

        $this->jobRepository->chunkActive(100, function ($jobs) use ($indexName, $bar, &$indexed): void {
            $operations = [];

            foreach ($jobs as $job) {
                $operations[] = ['index' => ['_index' => $indexName, '_id' => (string) $job->id]];
                $operations[] = $job->toSearchArray();
            }

            $this->indexManager->bulk($operations);
            $bar->advance(count($jobs));
            $indexed += count($jobs);
        });

        $bar->finish();
        $this->newLine();

        return $indexed;
    }

    private function switchAlias(string $alias, string $newIndex): void
    {
        $actions = [['add' => ['index' => $newIndex, 'alias' => $alias]]];

        try {
            $existing = $this->indexManager->getAliasIndexes($alias);
            foreach (array_keys($existing) as $oldIndex) {
                if ($oldIndex !== $newIndex) {
                    $actions[] = ['remove' => ['index' => $oldIndex, 'alias' => $alias]];
                }
            }
        } catch (\Throwable) {
            // No alias found — if a plain index with the alias name exists, delete it first
            // so Elasticsearch can create the alias (an index and alias cannot share a name)
            try {
                $this->indexManager->getVersionedIndexes($alias);
                $this->indexManager->deleteIndex($alias);
                $this->line("Deleted plain index '{$alias}' to make way for alias.");
            } catch (\Throwable) {
                // Neither alias nor plain index exists — clean first run
            }
        }

        $this->indexManager->updateAliases($actions);
    }

    private function cleanupOldIndexes(string $alias, string $keepIndex): void
    {
        try {
            $indexes = $this->indexManager->getVersionedIndexes($alias.'_v*');
            foreach (array_keys($indexes) as $index) {
                if ($index !== $keepIndex) {
                    $this->indexManager->deleteIndex($index);
                    $this->line("Deleted old index: {$index}");
                }
            }
        } catch (\Throwable) {
            // No old indexes to clean up
        }
    }

    private function deleteVersionedIndexes(string $alias): void
    {
        try {
            $indexes = $this->indexManager->getVersionedIndexes($alias.'_v*');
            foreach (array_keys($indexes) as $index) {
                $this->indexManager->deleteIndex($index);
                $this->line("Deleted index: {$index}");
            }
        } catch (\Throwable) {
            // No versioned indexes exist yet
        }
    }

    private function mapping(): array
    {
        return [
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => ['keyword' => ['type' => 'keyword']],
                ],
                'description' => ['type' => 'text', 'analyzer' => 'standard'],
                'category' => ['type' => 'keyword'],
                'employment_type' => ['type' => 'keyword'],
                'location_city' => ['type' => 'keyword'],
                'location_country' => ['type' => 'keyword'],
                'is_remote' => ['type' => 'boolean'],
                'salary_min' => ['type' => 'integer'],
                'salary_max' => ['type' => 'integer'],
                'currency' => ['type' => 'keyword'],
                'status' => ['type' => 'keyword'],
                'published_at' => ['type' => 'date'],
                'company' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword']],
                        ],
                        'industry' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ];
    }
}
