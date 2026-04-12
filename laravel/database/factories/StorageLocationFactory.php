<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\StorageLocation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StorageLocation>
 */
class StorageLocationFactory extends Factory
{
    protected $model = StorageLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . ' Shelf',
            'description' => $this->faker->sentence(),
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
        ];
    }
}
