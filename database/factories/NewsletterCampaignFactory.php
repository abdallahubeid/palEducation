<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    protected $model = NewsletterCampaign::class;

    public function definition(): array
    {
        return [
            'admin_id' => null,
            'subject' => 'مستجدّات الفصل الثاني',
            'body' => 'أضفنا محاضرات جديدة في الرياضيات والفيزياء لهذا الفصل.',
            'recipients_count' => $this->faker->numberBetween(1, 50),
            'status' => CampaignStatus::Completed,
            'sent_at' => now()->subDays($this->faker->numberBetween(0, 60)),
        ];
    }

    /** Still in the queue: no sent_at, because nothing has finished. */
    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::Processing,
            'sent_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::Failed,
            'sent_at' => now(),
        ]);
    }
}
