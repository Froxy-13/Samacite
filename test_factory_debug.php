<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Report;
use Database\Seeders\RolesAndPermissionsSeeder;

// Seed roles first
$seeder = new RolesAndPermissionsSeeder();
$seeder->run();

// Create test data
$user1 = User::factory()->create();
$user2 = User::factory()->create();
$category = Category::factory()->create();

// Create reports using forUser method
Report::factory(2)->forUser($user1)->forCategory($category)->create();
Report::factory(3)->forUser($user2)->forCategory($category)->create();

// Check the result
$reports = $category->reports()->get();
echo "Total reports: " . count($reports) . "\n";

foreach ($reports as $report) {
    echo "Report ID: " . $report->id . ", User ID: " . $report->user_id . "\n";
}

// Different distinct queries
$distinctUsers1 = $category->reports()->select('user_id')->distinct()->count();
$distinctUsers2 = $category->reports()->pluck('user_id')->unique()->count();
$distinctUsers3 = $category->reports()->select('user_id')->groupBy('user_id')->count();

echo "\nMethod 1 (select + distinct): " . $distinctUsers1 . "\n";
echo "Method 2 (pluck + unique): " . $distinctUsers2 . "\n";
echo "Method 3 (groupBy): " . $distinctUsers3 . "\n";

// Let's also check the SQL query
$query = $category->reports()->select('user_id')->distinct();
echo "\nSQL: " . $query->toSql() . "\n";
