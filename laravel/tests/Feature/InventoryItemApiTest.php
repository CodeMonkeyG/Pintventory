<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class InventoryItemApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::factory()->create();
        $this->user = User::factory()->create([
            'current_workspace_id' => $this->workspace->id,
        ]);
        $this->user->workspaces()->attach($this->workspace, ['role' => 'admin']);

        Sanctum::actingAs($this->user);
    }

    public function test_it_can_list_inventory_items()
    {
        InventoryItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/inventory-items');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_items_by_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        
        InventoryItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);
        
        InventoryItem::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/inventory-items');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_it_can_create_an_inventory_item()
    {
        $payload = [
            'title' => 'API Created Item',
            'sku' => 'API-SKU-123',
            'status' => 'in_stock',
            'quantity_on_hand' => 10,
        ];

        $response = $this->postJson('/api/inventory-items', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'API Created Item')
            ->assertJsonPath('workspace_id', $this->workspace->id);

        $this->assertDatabaseHas('inventory_items', [
            'title' => 'API Created Item',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_it_can_update_an_inventory_item()
    {
        $item = InventoryItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/inventory-items/{$item->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('title', 'Updated Title');

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_it_can_archive_an_inventory_item()
    {
        $item = InventoryItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/inventory-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Item archived');

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'status' => 'archived',
        ]);
        
        $this->assertNotNull(InventoryItem::find($item->id)->archived_at);
    }

    public function test_it_can_bulk_update_inventory_items()
    {
        $items = InventoryItem::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $ids = $items->pluck('id')->toArray();

        $response = $this->postJson('/api/inventory-items/bulk-update', [
            'ids' => $ids,
            'data' => [
                'status' => 'out_of_stock',
                'item_type' => 'unique'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', '2 items updated');

        foreach ($ids as $id) {
            $this->assertDatabaseHas('inventory_items', [
                'id' => $id,
                'status' => 'out_of_stock',
                'item_type' => 'unique'
            ]);
        }
    }

    public function test_it_can_bulk_delete_and_archive_inventory_items()
    {
        $itemsToArchive = InventoryItem::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $itemsToDelete = InventoryItem::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $archiveIds = $itemsToArchive->pluck('id')->toArray();
        $deleteIds = $itemsToDelete->pluck('id')->toArray();

        // Test Archive
        $responseArchive = $this->postJson('/api/inventory-items/bulk-delete', [
            'ids' => $archiveIds,
            'permanent' => false
        ]);

        $responseArchive->assertStatus(200)->assertJsonPath('message', '2 items archived');

        foreach ($archiveIds as $id) {
            $this->assertDatabaseHas('inventory_items', [
                'id' => $id,
                'status' => 'archived'
            ]);
        }

        // Test Permanent Delete
        $responseDelete = $this->postJson('/api/inventory-items/bulk-delete', [
            'ids' => $deleteIds,
            'permanent' => true
        ]);

        $responseDelete->assertStatus(200)->assertJsonPath('message', '2 items deleted');

        foreach ($deleteIds as $id) {
            $this->assertDatabaseMissing('inventory_items', [
                'id' => $id,
            ]);
        }
    }

    public function test_it_can_bulk_store_inventory_items()
    {
        $payload = [
            'items' => [
                [
                    'title' => 'Bulk Item 1',
                    'status' => 'in_stock',
                    'quantity_on_hand' => 5
                ],
                [
                    'title' => 'Bulk Item 2',
                    'status' => 'in_stock',
                    'quantity_on_hand' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/inventory-items/bulk-store', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', '2 items imported successfully');

        $this->assertDatabaseHas('inventory_items', [
            'title' => 'Bulk Item 1',
            'workspace_id' => $this->workspace->id,
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'title' => 'Bulk Item 2',
            'workspace_id' => $this->workspace->id,
        ]);
    }
}
