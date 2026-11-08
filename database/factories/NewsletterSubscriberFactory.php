<?php

namespace Database\Factories;

use App\Enums\NewsletterStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'status' => NewsletterStatus::Active,
            'subscribed_at' => now()->subDays($this->faker->numberBetween(0, 200)),
            'unsubscribed_at' => null,
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NewsletterStatus::Unsubscribed,
            // Always AFTER subscribing: a row that left before it joined
            // makes every date-range report nonsense.
            'unsubscribed_at' => now(),
        ]);
    }
}
