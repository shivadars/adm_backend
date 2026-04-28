<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    /**
     * Define the security middleware for this controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if ($request->user() && ($request->user()->role === 'admin' || $request->user()->role === 'superadmin')) {
                    return $next($request);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }, except: ['index']),
        ];
    }

    /**
     * Display a listing of products.
     */
    public function index()
    {
        $products = Product::with('category')->latest()->get();

        $products->transform(function ($product) {
            return $this->formatProduct($product);
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
        try {
            $data = $request->all();

            // Auto-create category if name provided instead of ID
            if (isset($data['category']) && !isset($data['category_id'])) {
                $categoryName = $data['category'];
                $category = Category::where('name', $categoryName)->first();

                if (!$category) {
                    $category = Category::create([
                        'name' => $categoryName,
                        'slug' => strtolower(str_replace(' ', '-', $categoryName)),
                        'status' => 'active'
                    ]);
                }

                $data['category_id'] = $category->id;
            }

            $data = $this->stringifyArrays($data);

            // Handle Image Upload - Aggressive Detection
            $file = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
            } else if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['image'];
            }

            if ($file) {
                $imageName = 'product_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('products', $imageName, 'public');
                $data['image'] = '/storage/' . $path;
            }



            // --- PROTECTIVE LAYER: Ensure only fillable fields and correct types reach the DB ---
            $fields = ['category_id', 'name', 'description', 'tags', 'mrp', 'price', 'materials', 'colors', 'image', 'status', 'featured'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $fillableData[$field] = $data[$field];
                }
            }

            // Force image to be a string path (replaces any leftover File objects)
            if (isset($fillableData['image']) && !is_string($fillableData['image'])) {
                unset($fillableData['image']);
            }

            $validator = Validator::make($fillableData, [
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'tags' => 'nullable|string',
                'mrp' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'materials' => 'nullable|string',
                'colors' => 'nullable|string',
                'image' => 'nullable',
                'status' => 'nullable|string|in:active,inactive',
                'featured' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::create($fillableData);
            $this->formatProduct($product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
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

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->image));
            }
            $file = $request->file('image');
            $imageName = 'product_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $imageName, 'public');
            $data['image'] = '/storage/' . $path;
        }

        $data = $this->stringifyArrays($data);

        $validator = Validator::make($data, [
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($data);
        $this->formatProduct($product);

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

    private function stringifyArrays($data)
    {
        $arrayFields = ['tags', 'materials', 'colors', 'categories'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $data[$field] = implode(', ', array_filter($data[$field]));
                } elseif (is_object($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                }
            }
        }
        return $data;
    }

    private function formatProduct($product)
    {
        $product->tags = $product->tags ? explode(', ', $product->tags) : [];
        $product->materials = $product->materials ? explode(', ', $product->materials) : [];
        $product->colors = $product->colors ? explode(', ', $product->colors) : [];

        if ($product->image && str_starts_with($product->image, '/storage')) {
            $product->image = url($product->image);
        }

        return $product;
    }
}
