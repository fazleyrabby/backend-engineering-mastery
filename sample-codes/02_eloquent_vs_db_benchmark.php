<?php

/**
 * Benchmark Script: Comparing Hydrated Eloquent Models vs Raw DB Queries
 * 
 * Run in CLI: php sample-codes/02_eloquent_vs_db_benchmark.php
 */

function benchmark($name, callable $callback) {
    $startMemory = memory_get_usage();
    $startTime = microtime(true);

    $callback();

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $timeTaken = number_format(($endTime - $startTime) * 1000, 2);
    $memoryUsed = number_format(($endMemory - $startMemory) / 1024, 2);

    echo "[$name]\n";
    echo "  Time: {$timeTaken} ms\n";
    echo "  Memory: {$memoryUsed} KB\n\n";
}

// Simulate 10,000 raw array rows from PDO
$rawArrayRows = array_fill(0, 10000, [
    'id' => 1,
    'user_id' => 42,
    'title' => 'Sample Post Title',
    'status' => 'published',
    'created_at' => '2026-08-14 23:50:00'
]);

benchmark("Raw Arrays (PDO Query Builder style)", function() use ($rawArrayRows) {
    $result = [];
    foreach ($rawArrayRows as $row) {
        $result[] = (object) $row;
    }
});

class MockEloquentModel {
    public array $attributes;
    public array $original;
    public function __construct(array $attrs) {
        $this->attributes = $attrs;
        $this->original = $attrs;
    }
}

benchmark("Hydrated Eloquent Class Objects", function() use ($rawArrayRows) {
    $result = [];
    foreach ($rawArrayRows as $row) {
        $result[] = new MockEloquentModel($row);
    }
});
