<?php

namespace App\Http\Controllers\Api;

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
            'image' => 'nullable|string', // Base64 or URL
            'instagram_username' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle Image Upload (Base64)
        if (isset($data['image']) && (str_contains($data['image'], 'data:image') || str_contains($data['image'], ';base64,'))) {
            $image = $data['image'];
            if (str_contains($image, ',')) {
                $parts = explode(',', $image);
                $header = $parts[0];
                $data64 = $parts[1];

                $extension = 'png'; // Default
                if (preg_match('/image\/(.*);/', $header, $matches)) {
                    $extension = $matches[1];
                }

                $imageName = 'pet_' . time() . '_' . uniqid() . '.' . $extension;
                Storage::disk('public')->put('pets/' . $imageName, base64_decode($data64));

                $data['image'] = '/storage/pets/' . $imageName;
            }
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
}
