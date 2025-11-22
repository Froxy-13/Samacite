<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Report Repository
        $this->app->bind(
            \App\Repositories\Contracts\ReportRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentReportRepository::class
        );

        // Category Repository
        $this->app->bind(
            \App\Repositories\Contracts\CategoryRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentCategoryRepository::class
        );

        // Report Image Repository
        $this->app->bind(
            \App\Repositories\Contracts\ReportImageRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentReportImageRepository::class
        );

        // Comment Repository
        $this->app->bind(
            \App\Repositories\Contracts\CommentRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentCommentRepository::class
        );

        // Like Repository
        $this->app->bind(
            \App\Repositories\Contracts\LikeRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentLikeRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Category::class, \App\Policies\CategoryPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Report::class, \App\Policies\ReportPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ReportImage::class, \App\Policies\ReportImagePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Like::class, \App\Policies\LikePolicy::class);
    }
}
