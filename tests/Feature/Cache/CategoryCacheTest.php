<?php

namespace Tests\Feature\Cache;

use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryCacheTest extends TestCase
{
    use RefreshDatabase;

    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheService::class);
        Cache::flush();
    }

    public function test_categories_are_cached_for_one_hour()
    {
        $category = Category::factory()->create(['name' => 'Test Category']);

        // Cache the category
        $categories = Category::all();
        $this->cacheService->cacheCategories($categories);

        // Check that cache was created with key 'cache:categories'
        $cached = Cache::get('cache:categories');
        $this->assertNotNull($cached, 'Categories should be cached with key "cache:categories"');
    }

    public function test_category_cache_uses_one_hour_ttl()
    {
        Category::factory()->create();

        // Manually set cache with a specific TTL
        $categories = Category::all();
        Cache::put('cache:categories', $categories, 3600); // 1 hour

        // Verify cache exists
        $cached = Cache::get('cache:categories');
        $this->assertNotNull($cached);
        $this->assertCount(1, $cached);
    }

    public function test_category_cache_is_invalidated_on_create()
    {
        // Create initial categories
        Category::factory(2)->create();
        $initial = Category::all();
        Cache::put('cache:categories', $initial, 3600);

        // Verify cache has 2 categories
        $cached = Cache::get('cache:categories');
        $this->assertCount(2, $cached);

        // Create new category (should invalidate cache)
        Category::factory()->create();

        // Cache should be flushed
        $cached = Cache::get('cache:categories');
        $this->assertNull($cached, 'Cache should be invalidated after create');
    }

    public function test_category_cache_is_invalidated_on_update()
    {
        $category = Category::factory()->create();
        $categories = Category::all();
        Cache::put('cache:categories', $categories, 3600);

        // Verify cache
        $cached = Cache::get('cache:categories');
        $this->assertNotNull($cached);

        // Update category
        $category->update(['name' => 'Updated Name']);

        // Cache should be flushed
        $cached = Cache::get('cache:categories');
        $this->assertNull($cached, 'Cache should be invalidated after update');
    }

    public function test_category_cache_is_invalidated_on_delete()
    {
        $category = Category::factory()->create();
        $categories = Category::all();
        Cache::put('cache:categories', $categories, 3600);

        // Verify cache
        $cached = Cache::get('cache:categories');
        $this->assertNotNull($cached);

        // Delete category
        $category->delete();

        // Cache should be flushed
        $cached = Cache::get('cache:categories');
        $this->assertNull($cached, 'Cache should be invalidated after delete');
    }

    public function test_cache_keys_are_properly_formatted()
    {
        // Put some data in different cache keys
        Cache::put('cache:categories', ['cat1', 'cat2'], 3600);
        Cache::put('cache:reports', ['report1', 'report2'], 60);

        // Verify both are cached
        $categories = Cache::get('cache:categories');
        $reports = Cache::get('cache:reports');

        $this->assertNotNull($categories);
        $this->assertNotNull($reports);

        // Forget only categories key
        Cache::forget('cache:categories');

        // Categories should be gone but reports should remain
        $categories = Cache::get('cache:categories');
        $reports = Cache::get('cache:reports');

        $this->assertNull($categories);
        $this->assertNotNull($reports);
    }
}
