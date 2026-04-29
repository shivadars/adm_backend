<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetController extends Controller
{
    /**
     * Store a newly created pet profile in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $user = $request->user();

        // Validate the request
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'breed' => 'required|string|max:255',
            'dob' => 'required|date',
            'image' => 'nullable', // File object or URL string
            'instagram_username' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'neck_length' => 'nullable|numeric',
            'chest_length' => 'nullable|numeric',
            'back_length' => 'nullable|numeric',
            'top_to_toe_height' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // ── Handle Image Upload (Aggressive Detection) ──
        $file = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
        } else if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['image'];
        }

        if ($file) {
            $imageName = 'pet_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pets', $imageName, 'public');
            $data['image'] = '/storage/' . $path;
        }

        // Add user_id to data
        $data['user_id'] = $user->id;

        $pet = Pet::create($data);

        // Add full URL to image path for response
        if ($pet->image && str_starts_with($pet->image, '/storage')) {
            $pet->image = url($pet->image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pet profile created successfully',
            'data' => $pet
        ], 201);
    }

    /**
     * Display a listing of the user's pets.
     */
    public function index(Request $request)
    {
        $pets = $request->user()->pets;

        // Add full URL to images
        $pets->transform(function ($pet) {
            if ($pet->image && str_starts_with($pet->image, '/storage')) {
                $pet->image = url($pet->image);
            }
            return $pet;
        });

        return response()->json([
            'status' => 'success',
            'data' => $pets
        ]);
    }

    /**
     * Remove the specified pet from storage.
     */
    public function destroy($id)
    {
        // Find the pet belonging to the authenticated user
        $pet = Pet::where('user_id', auth()->id())->find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found or unauthorized'
            ], 404);
        }

        // Delete the image from storage if it exists
        if ($pet->image) {
            $path = str_replace(url('/storage'), '', $pet->image);
            Storage::disk('public')->delete($path);
        }

        $pet->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pet deleted successfully'
        ]);
    }

    /**
     * Update the specified pet in storage.
     */
    public function update(Request $request, $id)
    {
        // Find the pet belonging to the authenticated user
        $pet = Pet::where('user_id', auth()->id())->find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found or unauthorized'
            ], 404);
        }

        $data = $request->all();

        // Validate the request
        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'breed' => 'sometimes|required|string|max:255',
            'dob' => 'sometimes|required|date',
            'image' => 'nullable', // File object or URL string
            'instagram_username' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'neck_length' => 'nullable|numeric',
            'chest_length' => 'nullable|numeric',
            'back_length' => 'nullable|numeric',
            'top_to_toe_height' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // ── Handle Image Upload (Aggressive Detection) ──
        $file = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
        } else if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['image'];
        }

        if ($file) {
            // Delete old image if it exists
            if ($pet->image) {
                $oldPath = str_replace('/storage/', '', $pet->image);
                Storage::disk('public')->delete($oldPath);
            }
            $imageName = 'pet_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pets', $imageName, 'public');
            $data['image'] = '/storage/' . $path;
        }

        // Remove user_id from data to prevent ownership change
        unset($data['user_id']);

        $pet->update($data);

        // Add full URL to image path for response
        if ($pet->image && str_starts_with($pet->image, '/storage')) {
            $pet->image = url($pet->image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pet profile updated successfully',
            'data' => $pet
        ]);
    }
}
