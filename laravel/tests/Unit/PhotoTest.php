<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_can_be_created()
    {
        $photo = Photo::factory()->create([
            'caption' => 'Test Photo Caption',
        ]);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'caption' => 'Test Photo Caption',
        ]);

        $this->assertInstanceOf(InventoryItem::class, $photo->inventoryItem);
    }

    public function test_photos_are_ordered_by_sort_order()
    {
        $item = InventoryItem::factory()->create();
        
        $photo2 = Photo::factory()->create([
            'inventory_item_id' => $item->id,
            'sort_order' => 2,
        ]);
        $photo1 = Photo::factory()->create([
            'inventory_item_id' => $item->id,
            'sort_order' => 1,
        ]);

        // We use the relationship on InventoryItem to test ordering
        $photos = $item->photos;
        $this->assertEquals($photo1->id, $photos->first()->id);
        $this->assertEquals($photo2->id, $photos->last()->id);
    }
}
