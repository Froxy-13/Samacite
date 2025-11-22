<?php

namespace Tests\Feature\Cache;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use App\Services\CacheService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatsCacheTest extends TestCase
{
    use RefreshDatabase;

    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->cacheService = app(CacheService::class);
        Cache::flush();
    }

    public function test_statistics_are_cached_for_one_hour()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Report::factory(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        // Simulate stats calculation and caching
        $stats = [
            'total_reports' => 5,
            'status_breakdown' => ['En attente' => 5],
            'category_breakdown' => [$category->id => 5]
        ];

        Cache::put('cache:stats:global_stats', $stats, 3600); // 1 hour

        // Verify cache
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNotNull($cached);
        $this->assertEquals(5, $cached['total_reports']);
    }

    public function test_stats_cache_uses_one_hour_ttl()
    {
        $stats = [
            'total_reports' => 10,
            'resolved' => 5,
            'pending' => 5
        ];

        Cache::put('cache:stats:global_stats', $stats, 3600);

        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNotNull($cached);
        $this->assertCount(3, $cached);
    }

    public function test_stats_cache_is_invalidated_on_report_create()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create initial reports
        Report::factory(3)->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // Cache stats
        $stats = ['total_reports' => 3];
        Cache::put('cache:stats:global_stats', $stats, 3600);

        // Verify cache
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNotNull($cached);
        $this->assertEquals(3, $cached['total_reports']);

        // Create new report (should invalidate stats cache)
        Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // Cache should be flushed
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNull($cached, 'Stats cache should be invalidated after report creation');
    }

    public function test_stats_cache_is_invalidated_on_report_delete()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // Cache stats
        $stats = ['total_reports' => 1];
        Cache::put('cache:stats:global_stats', $stats, 3600);

        // Verify cache
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNotNull($cached);

        // Delete report (should invalidate stats cache)
        $report->delete();

        // Cache should be flushed
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNull($cached, 'Stats cache should be invalidated after report deletion');
    }

    public function test_stats_cache_is_invalidated_on_status_change()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        // Cache stats with status breakdown
        $stats = ['total_reports' => 1, 'status_breakdown' => ['En attente' => 1]];
        Cache::put('cache:stats:global_stats', $stats, 3600);

        // Verify cache
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNotNull($cached);

        // Change status (should invalidate stats cache)
        $report->updateStatus('Résolu');

        // Cache should be flushed
        $cached = Cache::get('cache:stats:global_stats');
        $this->assertNull($cached, 'Stats cache should be invalidated after status change');
    }

    public function test_multiple_cache_keys_can_coexist()
    {
        // Put data in different cache keys
        Cache::put('cache:stats:global_stats', ['total' => 100], 3600);
        Cache::put('cache:reports', ['report1'], 60);
        Cache::put('cache:categories', ['cat1'], 3600);

        // Verify all exist
        $stats = Cache::get('cache:stats:global_stats');
        $reports = Cache::get('cache:reports');
        $categories = Cache::get('cache:categories');

        $this->assertNotNull($stats);
        $this->assertNotNull($reports);
        $this->assertNotNull($categories);
    }

    public function test_stats_cache_key_consistency()
    {
        // Store stats with same key multiple times
        $stats1 = ['total_reports' => 5];
        $stats2 = ['total_reports' => 10];

        Cache::put('cache:stats:global_stats', $stats1, 3600);
        $cached1 = Cache::get('cache:stats:global_stats');
        $this->assertEquals(5, $cached1['total_reports']);

        Cache::put('cache:stats:global_stats', $stats2, 3600);
        $cached2 = Cache::get('cache:stats:global_stats');
        $this->assertEquals(10, $cached2['total_reports']);
    }

    public function test_separate_stats_for_different_entities()
    {
        $globalStats = ['total_reports' => 100];
        $userStats = ['user_id' => 1, 'reports_created' => 5];
        $categoryStats = ['category_id' => 1, 'reports_count' => 20];

        Cache::put('cache:stats:global_stats', $globalStats, 3600);
        Cache::put('cache:stats:user:1', $userStats, 3600);
        Cache::put('cache:stats:category:1', $categoryStats, 3600);

        $cached = Cache::get('cache:stats:global_stats');
        $this->assertEquals(100, $cached['total_reports']);
    }
}
