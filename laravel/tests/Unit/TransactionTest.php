<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_can_be_created()
    {
        $purchase = Purchase::factory()->create([
            'quantity_purchased' => 10,
            'unit_cost' => 5.00,
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'quantity_purchased' => 10,
            'unit_cost' => 5.00,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $purchase->inventoryItem);
        $this->assertInstanceOf(Vendor::class, $purchase->vendor);
    }

    public function test_sale_can_be_created()
    {
        $sale = Sale::factory()->create([
            'quantity_sold' => 2,
            'unit_price' => 100.00,
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'quantity_sold' => 2,
            'unit_price' => 100.00,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $sale->inventoryItem);
        $this->assertInstanceOf(Customer::class, $sale->customer);
    }
}
