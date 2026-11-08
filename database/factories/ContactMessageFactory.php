<?php

namespace Database\Factories;

use App\Enums\ContactSubject;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => null,
            'subject_type' => fake()->randomElement(ContactSubject::cases()),
            'message' => fake()->paragraph(),
            'is_read' => false,
            'replied_at' => null,
            'reply_message' => null,
            'admin_id' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['is_read' => true]);
    }

    /** Replied implies read: nobody answers a message without opening it. */
    public function replied(): static
    {
        return $this->state(fn (): array => [
            'is_read' => true,
            'replied_at' => now(),
            'reply_message' => 'شكراً لتواصلك معنا.',
        ]);
    }
}
