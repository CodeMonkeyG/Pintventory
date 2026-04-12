<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'vendor_id' => Vendor::factory(),
            'quantity_purchased' => $this->faker->numberBetween(1, 100),
            'unit_cost' => $this->faker->randomFloat(2, 1, 1000),
            'purchased_at' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
