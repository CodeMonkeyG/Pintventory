<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(Str::random(8)),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => 'available',
            'item_type' => 'Standard',
            'quantity_on_hand' => $this->faker->numberBetween(0, 100),
            'reorder_point' => $this->faker->numberBetween(5, 20),
            'unit' => 'pcs',
            'tags' => ['test', 'sample'],
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
