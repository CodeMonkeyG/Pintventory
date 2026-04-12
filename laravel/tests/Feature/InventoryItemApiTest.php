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
}
