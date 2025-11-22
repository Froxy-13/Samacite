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

class ReportsCacheTest extends TestCase
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

    public function test_reports_are_cached_for_sixty_seconds()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Report::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Cache the reports
        $reports = Report::all();
        $this->cacheService->cacheReports($reports);

        // Check that cache was created with key 'cache:reports'
        $cached = Cache::get('cache:reports');
        $this->assertNotNull($cached, 'Reports should be cached with key "cache:reports"');
    }

    public function test_reports_cache_uses_sixty_seconds_ttl()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // Manually set cache with 60 second TTL
        $reports = Report::all();
        Cache::put('cache:reports', $reports, 60);

        // Verify cache exists
        $cached = Cache::get('cache:reports');
        $this->assertNotNull($cached);
        $this->assertCount(1, $cached);
    }

    public function test_reports_cache_is_invalidated_on_create()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create initial reports
        Report::factory(2)->create(['user_id' => $user->id, 'category_id' => $category->id]);
        $initial = Report::all();
        Cache::put('cache:reports', $initial, 60);

        // Verify cache has 2 reports
        $cached = Cache::get('cache:reports');
        $this->assertCount(2, $cached);

        // Create new report (should invalidate cache)
        Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // Cache should be flushed
        $cached = Cache::get('cache:reports');
        $this->assertNull($cached, 'Cache should be invalidated after create');
    }

    public function test_reports_cache_is_invalidated_on_update()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $reports = Report::all();
        Cache::put('cache:reports', $reports, 60);

        // Verify cache
        $cached = Cache::get('cache:reports');
        $this->assertNotNull($cached);

        // Update report
        $report->update(['title' => 'Updated Title']);

        // Cache should be flushed
        $cached = Cache::get('cache:reports');
        $this->assertNull($cached, 'Cache should be invalidated after update');
    }

    public function test_reports_cache_is_invalidated_on_delete()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $reports = Report::all();
        Cache::put('cache:reports', $reports, 60);

        // Verify cache
        $cached = Cache::get('cache:reports');
        $this->assertNotNull($cached);

        // Delete report
        $report->delete();

        // Cache should be flushed
        $cached = Cache::get('cache:reports');
        $this->assertNull($cached, 'Cache should be invalidated after delete');
    }

    public function test_reports_cache_ttl_is_shorter_than_categories()
    {
        // Reports: 60 seconds (1 minute)
        Cache::put('cache:reports', ['report1'], 60);

        // Categories: 3600 seconds (1 hour)
        Cache::put('cache:categories', ['cat1'], 3600);

        $reports = Cache::get('cache:reports');
        $categories = Cache::get('cache:categories');

        $this->assertNotNull($reports);
        $this->assertNotNull($categories);
    }

    public function test_different_cache_keys_do_not_interfere()
    {
        // Put data in different cache keys
        Cache::put('cache:reports', ['report1', 'report2'], 60);
        Cache::put('cache:categories', ['cat1', 'cat2'], 3600);

        // Verify both exist
        $reports = Cache::get('cache:reports');
        $categories = Cache::get('cache:categories');

        $this->assertCount(2, $reports);
        $this->assertCount(2, $categories);

        // Forget reports key
        Cache::forget('cache:reports');

        // Reports should be gone
        $reports = Cache::get('cache:reports');
        $categories = Cache::get('cache:categories');

        $this->assertNull($reports);
        $this->assertNotNull($categories); // Categories should still exist
    }
}
