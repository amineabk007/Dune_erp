<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use Tests\TestCase;

class StatusLabelsTest extends TestCase
{
    public function test_table_status_labels_are_in_french(): void
    {
        $table = RestaurantTable::factory()->make(['status' => 'occupied']);
        $this->assertSame('Occupée', $table->statusLabel());
        $this->assertSame('Disponible', RestaurantTable::labelForStatus('available'));
    }

    public function test_order_status_labels_are_in_french(): void
    {
        $order = new Order(['status' => 'sent']);
        $this->assertSame('Envoyée', $order->statusLabel());
        $this->assertSame('Payée', Order::labelForStatus('paid'));
    }

    public function test_order_item_status_and_destination_labels_are_in_french(): void
    {
        $item = new OrderItem(['status' => 'preparing', 'destination' => 'kitchen']);
        $this->assertSame('En préparation', $item->statusLabel());
        $this->assertSame('Cuisine', $item->destinationLabel());
    }

    public function test_payment_method_label_is_in_french(): void
    {
        $payment = new Payment(['method' => 'cash']);
        $this->assertSame('Espèces', $payment->methodLabel());
    }

    public function test_reservation_status_label_is_in_french(): void
    {
        $reservation = Reservation::factory()->make(['status' => 'confirmed']);
        $this->assertSame('Confirmée', $reservation->statusLabel());
    }

    public function test_stock_movement_type_label_is_in_french(): void
    {
        $movement = new StockMovement(['type' => 'waste']);
        $this->assertSame('Perte', $movement->typeLabel());
    }
}
