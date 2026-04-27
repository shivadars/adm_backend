<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * GET /api/addresses
     * List all addresses for the authenticated user.
     */
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * POST /api/addresses
     * Create a new address for the authenticated user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city'           => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'required|string|max:10',
            'country'        => 'nullable|string|max:100',
            'landmark'       => 'nullable|string|max:255',
            'is_default'     => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        // If this is set as default, unset any existing default
        if (!empty($validated['is_default'])) {
            Address::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return response()->json([
            'message' => 'Address created.',
            'data'    => $address,
        ], 201);
    }

    /**
     * GET /api/addresses/{address}
     * Show a single address.
     */
    public function show(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
            'data' => $address,
        ]);
    }

    /**
     * PUT /api/addresses/{address}
     * Update an existing address.
     */
    public function update(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'full_name'      => 'sometimes|required|string|max:255',
            'phone'          => 'sometimes|required|string|max:20',
            'address_line_1' => 'sometimes|required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city'           => 'sometimes|required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'sometimes|required|string|max:10',
            'country'        => 'nullable|string|max:100',
            'landmark'       => 'nullable|string|max:255',
            'is_default'     => 'boolean',
        ]);

        // If this is set as default, unset any existing default
        if (!empty($validated['is_default'])) {
            Address::where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated.',
            'data'    => $address->fresh(),
        ]);
    }

    /**
     * DELETE /api/addresses/{address}
     * Delete an address.
     */
    public function destroy(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted.',
        ]);
    }
}
