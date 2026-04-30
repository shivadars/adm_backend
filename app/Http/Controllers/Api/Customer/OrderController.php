<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Product;
use App\Models\Pet;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     * List orders for the authenticated user (with items, address, pet).
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['orderItems.product', 'orderItems.pet', 'address'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * GET /api/orders/{order}
     * Show a single order with full details.
     */
    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $order->load(['orderItems.product', 'orderItems.pet', 'address']);

        return response()->json([
            'data' => $order,
        ]);
    }

    /**
     * POST /api/orders
     * Place a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'required|integer|exists:products,id',
            'items.*.cartQty' => 'required|integer|min:1',
            'items.*.size'    => 'nullable|string|max:10',
            'items.*.name'    => 'nullable|string',
            'items.*.price'   => 'nullable|numeric',
            'address'         => 'required|array',
            'address.name'    => 'required|string|max:255',
            'address.line1'   => 'required|string|max:500',
            'address.city'    => 'required|string|max:100',
            'address.pincode' => 'required|string|max:10',
            'address.phone'   => 'required|string|max:20',
            'payMethod'       => 'required|in:cod,upi,card',
            'petId'           => 'nullable|integer|exists:pets,id',
            'petSize'         => 'nullable|string|max:10',
        ]);

        $user = $request->user();

        // Fetch products to get current prices
        $productIds = collect($validated['items'])->pluck('id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return DB::transaction(function () use ($validated, $user, $products) {
            $addressData = $validated['address'];

            // ── 1. Create or reuse address ───────────────────────────────────
            $address = Address::firstOrCreate(
                [
                    'user_id'        => $user->id,
                    'address_line_1' => $addressData['line1'],
                    'postal_code'    => $addressData['pincode'],
                ],
                [
                    'full_name' => $addressData['name'],
                    'phone'     => $addressData['phone'],
                    'city'      => $addressData['city'],
                    'state'     => '',
                    'country'   => 'India',
                ],
            );

            // ── 2. Calculate totals server-side ──────────────────────────────
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product   = $products->get($item['id']);
                $subtotal += $product->price * $item['cartQty'];
            }

            $shippingCharge = $subtotal >= 999 ? 0 : 79;
            $totalAmount    = $subtotal + $shippingCharge;

            // ── 3. Generate unique order number ──────────────────────────────
            $orderNumber = 'ADM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

            // ── 4. Create the order ──────────────────────────────────────────
            $order = Order::create([
                'user_id'         => $user->id,
                'address_id'      => $address->id,
                'order_number'    => $orderNumber,
                'subtotal'        => $subtotal,
                'shipping_charge' => $shippingCharge,
                'tax_amount'      => 0,
                'total_amount'    => $totalAmount,
                'payment_method'  => $validated['payMethod'],
                'payment_status'  => 'pending',
                'order_status'    => 'placed',
                'placed_at'       => now(),
            ]);

            // ── 5. Create order items ───────────────────────────────────────
            foreach ($validated['items'] as $item) {
                $product = $products->get($item['id']);

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'price'        => $product->price,
                    'quantity'     => $item['cartQty'],
                    'total'        => $product->price * $item['cartQty'],
                    'size'         => $item['size'] ?? $validated['petSize'] ?? null,
                    'pet_id'       => $validated['petId'] ?? null,
                ]);
            }

            // ── 6. Update pet measurements if provided ───────────────────────
            if (!empty($validated['petId']) && !empty($validated['petSize'])) {
                Pet::where('id', $validated['petId'])
                    ->where('user_id', $user->id)
                    ->update(['size' => $validated['petSize']]);
            }

            // ── 7. Clear the user's server-side cart ─────────────────────────
            Cart::where('user_id', $user->id)->delete();

            // ── 8. Return the created order ─────────────────────────────────
            $order->load(['orderItems.product', 'orderItems.pet', 'address']);

            return response()->json([
                'message' => 'Order placed successfully!',
                'data'    => $order,
            ], 201);
        });
    }

    /**
     * PUT /api/orders/{order}
     * Update order status (for admin use).
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:placed,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $order->update([
            'order_status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Order status updated.',
            'data'    => $order->fresh()->load(['orderItems', 'address']),
        ]);
    }
}
