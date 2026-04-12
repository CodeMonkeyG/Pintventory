<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'customer_id' => Customer::factory(),
            'quantity_sold' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 1, 2000),
            'sold_at' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
