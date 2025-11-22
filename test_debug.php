<?php

use App\Models\Category;
use App\Models\Report;
use App\Models\User;

// Test script
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Limiter les résultats
$user1 = User::factory()->create();
$user2 = User::factory()->create();
$user3 = User::factory()->create();

$category = Category::factory()->create();

Report::factory(2)->create(['category_id' => $category->id, 'user_id' => $user1->id]);
Report::factory(3)->create(['category_id' => $category->id, 'user_id' => $user2->id]);
Report::factory(1)->create(['category_id' => $category->id, 'user_id' => $user3->id]);

$totalReports = $category->reports()->count();
$totalUsers = $category->reports()->select('user_id')->distinct()->count();

echo "Total reports: " . $totalReports . "\n";
echo "Total users: " . $totalUsers . "\n";
echo "Average: " . ($totalUsers > 0 ? round($totalReports / $totalUsers, 2) : 0) . "\n";

// Affiche les users réels
$reports = $category->reports()->get();
echo "\nReports in category:\n";
foreach ($reports as $report) {
    echo "User ID: " . $report->user_id . ", Report ID: " . $report->id . "\n";
}

$distinctUsers = $category->reports()->select('user_id')->distinct()->get();
echo "\nDistinct users:\n";
foreach ($distinctUsers as $user) {
    echo "User ID: " . $user->user_id . "\n";
}
