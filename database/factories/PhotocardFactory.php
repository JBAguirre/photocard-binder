<?php

namespace Database\Factories;

use App\Enums\CardCondition;
use App\Enums\CardType;
use App\Enums\PhotocardStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotocardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_name' => fake()->randomElement(['NewJeans', 'IVE', 'aespa', 'LE SSERAFIM', 'Stray Kids']),
            'member_name' => fake()->firstName(),
            'album' => fake()->words(2, true),
            'version' => 'Ver. '.fake()->randomElement(['A', 'B', 'C']),
            'card_type' => fake()->randomElement(CardType::cases()),
            'status' => fake()->randomElement(PhotocardStatus::cases()),
            'condition' => fake()->randomElement(CardCondition::cases()),
            'notes' => fake()->optional()->sentence(),
            'image_path' => null,
        ];
    }
}
