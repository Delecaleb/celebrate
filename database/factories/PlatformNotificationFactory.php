<?php

namespace Database\Factories;

use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotification>
 */
class PlatformNotificationFactory extends Factory
{
    protected $model = PlatformNotification::class;

    public function definition(): array
    {
        // The types the Activity page knows how to draw an icon for.
        $type = fake()->randomElement(['gift', 'comment', 'reaction', 'view', 'payment', 'system']);

        return [
            'user_id' => User::factory(),
            'type'    => $type,
            'title'   => fake()->sentence(4),
            'message' => fake()->sentence(10),
            'data'    => null,
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['is_read' => true]);
    }
}
