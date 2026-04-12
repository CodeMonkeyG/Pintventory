<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Workspace;
use App\Models\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_item_can_be_created()
    {
        $item = InventoryItem::factory()->create([
            'title' => 'Test Item',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'title' => 'Test Item',
        ]);
    }

    public function test_inventory_item_belongs_to_workspace()
    {
        $workspace = Workspace::factory()->create();
        $item = InventoryItem::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertInstanceOf(Workspace::class, $item->workspace);
        $this->assertEquals($workspace->id, $item->workspace->id);
    }

    public function test_inventory_item_can_have_storage_location()
    {
        $location = StorageLocation::factory()->create();
        $item = InventoryItem::factory()->create([
            'storage_location_id' => $location->id,
        ]);

        $this->assertInstanceOf(StorageLocation::class, $item->storageLocation);
        $this->assertEquals($location->id, $item->storageLocation->id);
    }

    public function test_low_stock_scope()
    {
        InventoryItem::factory()->create([
            'quantity_on_hand' => 10,
            'reorder_point' => 5,
        ]);

        $lowStockItem = InventoryItem::factory()->create([
            'quantity_on_hand' => 3,
            'reorder_point' => 5,
        ]);

        $results = InventoryItem::lowStock()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($lowStockItem->id, $results->first()->id);
    }

    public function test_search_scope()
    {
        InventoryItem::factory()->create(['title' => 'Apple', 'sku' => 'A1']);
        InventoryItem::factory()->create(['title' => 'Banana', 'sku' => 'B1']);

        $this->assertCount(1, InventoryItem::search('Apple')->get());
        $this->assertCount(1, InventoryItem::search('A1')->get());
        $this->assertCount(2, InventoryItem::search('')->get());
    }

    public function test_workspace_scope_filters_items()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $user = User::factory()->create(['current_workspace_id' => $workspace1->id]);
        Auth::login($user);

        InventoryItem::factory()->create(['workspace_id' => $workspace1->id]);
        InventoryItem::factory()->create(['workspace_id' => $workspace2->id]);

        $this->assertCount(1, InventoryItem::all());
    }

    public function test_belongs_to_workspace_trait_sets_workspace_id()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
        Auth::login($user);

        $item = new InventoryItem([
            'sku' => 'TEST-SKU',
            'title' => 'New Item',
            'created_by_user_id' => $user->id,
        ]);
        $item->save();

        $this->assertEquals($workspace->id, $item->workspace_id);
    }
}
