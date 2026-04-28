<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index()
    {
        // Fetch all orders with their items, user info, and shipping address
        $orders = Order::with(['orderItems.product', 'user', 'address'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $orders
        ]);
    }

    /**
     * Update the status of an order.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status'   => 'required|string|in:placed,confirmed,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,failed',
        ]);

        $order->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order updated successfully.',
            'data'    => $order->fresh(['orderItems.product', 'user', 'address'])
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $order->load(['orderItems.product', 'user', 'address']);
        
        return response()->json([
            'status' => 'success',
            'data'   => $order
        ]);
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        
        return response()->json([
            'status'  => 'success',
            'message' => 'Order deleted successfully.'
        ]);
    }
}
