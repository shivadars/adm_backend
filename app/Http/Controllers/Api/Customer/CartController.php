<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * GET /api/cart
     * Return the authenticated user's cart items.
     */
    public function index(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        return response()->json([
            'data' => $cart ? $cart->items : [],
        ]);
    }

    /**
     * POST /api/cart
     * Sync/save the full cart (replaces previous cart).
     * Frontend sends: { items: [ { id, name, price, cartQty, size, image, ... }, ... ] }
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $cart = Cart::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['items'   => $request->input('items')],
        );

        return response()->json([
            'message' => 'Cart saved.',
            'data'    => $cart->items,
        ]);
    }

    /**
     * DELETE /api/cart
     * Clear the user's cart.
     */
    public function destroy(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Cart cleared.',
            'data'    => [],
        ]);
    }
}
