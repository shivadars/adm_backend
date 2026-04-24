<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index()
    {
        $products = Product::with('category')->latest()->get();

        // Convert strings back to arrays for React compatibility
        $products->transform(function ($product) {
            $product->sizes = $product->sizes ? explode(', ', $product->sizes) : [];
            $product->tags = $product->tags ? explode(', ', $product->tags) : [];
            $product->materials = $product->materials ? explode(', ', $product->materials) : [];
            $product->colors = $product->colors ? explode(', ', $product->colors) : [];

            // Add full URL to image path if it's a local storage path
            if ($product->image && str_starts_with($product->image, '/storage')) {
                $product->image = url($product->image);
            }

            return $product;
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Map Category Name to ID if needed
        if (isset($data['category']) && !isset($data['category_id'])) {
            $category = Category::where('name', $data['category'])->first();
            if ($category) {
                $data['category_id'] = $category->id;
            }
        }

        // Convert Arrays to Strings (for sizes, tags, materials, colors)
        $arrayFields = ['sizes', 'tags', 'materials', 'colors'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = implode(', ', $data[$field]);
            }
        }

        // Handle Image Upload (Base64 or URL)
        if (isset($data['image']) && (str_contains($data['image'], 'data:image') || str_contains($data['image'], ';base64,'))) {
            $image = $data['image'];

            // Get the part after the comma
            if (str_contains($image, ',')) {
                $parts = explode(',', $image);
                $header = $parts[0];
                $data64 = $parts[1];

                // Get extension
                $extension = 'png'; // Default
                if (preg_match('/image\/(.*);/', $header, $matches)) {
                    $extension = $matches[1];
                }

                $imageName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                Storage::disk('public')->put('products/' . $imageName, base64_decode($data64));

                $data['image'] = '/storage/products/' . $imageName;
            }
        }

        // Debugging: Log the data to see what's coming in
        Log::info('Product Create Data:', ['image_present' => isset($data['image']), 'image_path' => $data['image'] ?? 'null']);

        $validator = Validator::make($data, [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sizes' => 'nullable|string',
            'tags' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0', // This is Selling Price
            'materials' => 'nullable|string',
            'colors' => 'nullable|string',
            'image' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::warning('Product Store Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($data);

        // Convert strings back to arrays so React doesn't crash when mapping
        $product->sizes = $product->sizes ? explode(', ', $product->sizes) : [];
        $product->tags = $product->tags ? explode(', ', $product->tags) : [];
        $product->materials = $product->materials ? explode(', ', $product->materials) : [];
        $product->colors = $product->colors ? explode(', ', $product->colors) : [];

        // Add full URL to image path for React
        if ($product->image && str_starts_with($product->image, '/storage')) {
            $product->image = url($product->image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $data = $request->all();

        // Handle Image Upload (Base64)
        if (isset($data['image']) && (str_contains($data['image'], 'data:image') || str_contains($data['image'], ';base64,'))) {
            $image = $data['image'];
            if (str_contains($image, ',')) {
                $parts = explode(',', $image);
                $header = $parts[0];
                $data64 = $parts[1];
                $extension = 'png';
                if (preg_match('/image\/(.*);/', $header, $matches)) {
                    $extension = $matches[1];
                }
                $imageName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                Storage::disk('public')->put('products/' . $imageName, base64_decode($data64));
                $data['image'] = '/storage/products/' . $imageName;
            }
        }

        // Convert Arrays to Strings
        $arrayFields = ['sizes', 'tags', 'materials', 'colors'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = implode(', ', $data[$field]);
            }
        }

        $validator = Validator::make($data, [
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'stock' => 'sometimes|required|integer|min:0',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('Product Update Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($data);

        // Convert back to arrays for React
        $product->sizes = $product->sizes ? explode(', ', $product->sizes) : [];
        $product->tags = $product->tags ? explode(', ', $product->tags) : [];
        $product->materials = $product->materials ? explode(', ', $product->materials) : [];
        $product->colors = $product->colors ? explode(', ', $product->colors) : [];

        if ($product->image && str_starts_with($product->image, '/storage')) {
            $product->image = url($product->image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
