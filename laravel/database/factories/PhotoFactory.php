<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Photo;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'storage_key' => 'photos/' . $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'caption' => $this->faker->sentence(),
            'sort_order' => 0,
            'workspace_id' => Workspace::factory(),
        ];
    }
}
