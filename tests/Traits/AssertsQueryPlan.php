<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait AssertsQueryPlan
{
    protected function assertNotFullTableScan(string $table, string $sql, array $bindings = []): void
    {
        if (DB::getDriverName() === 'mysql') {
            $rows = DB::select('EXPLAIN '.$sql, $bindings);
            foreach ($rows as $row) {
                $this->assertNotEquals(
                    'ALL',
                    $row->type,
                    "Query performs a full table scan on '{$table}': {$sql}"
                );
            }
        } else {
            $rows = DB::select('EXPLAIN QUERY PLAN '.$sql, $bindings);
            foreach ($rows as $row) {
                $detail = strtoupper($row->detail ?? '');
                $this->assertStringNotContainsString(
                    'SCAN '.strtoupper($table),
                    $detail,
                    "Query performs a full table scan on '{$table}': {$sql}"
                );
            }
        }
    }
}
