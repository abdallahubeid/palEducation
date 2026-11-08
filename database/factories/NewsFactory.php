<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        $title = 'خبر '.$this->faker->unique()->numberBetween(1, 100000);

        return [
            'author_id' => null,
            'title' => $title,
            'slug' => Str::slug($title) ?: 'news-'.uniqid(),
            'summary' => 'ملخّص قصير للخبر يظهر على البطاقة في صفحة الأخبار.',
            'content' => 'نصّ الخبر الكامل. '.str_repeat('محتوى تجريبي للخبر. ', 30),
            'is_published' => true,
            'published_at' => now()->subDays($this->faker->numberBetween(0, 90)),
        ];
    }

    /** Switched off by the editor — invisible whatever the date says. */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }

    /** Switched on but not due yet — the scheduling case. */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'published_at' => now()->addWeek(),
        ]);
    }
}
