<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportImageFactory extends Factory
{
    protected $model = ReportImage::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'image_path' => $this->faker->imageUrl(640, 480, 'city'),
        ];
    }
}
