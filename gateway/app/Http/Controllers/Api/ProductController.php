<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::forService(auth()->user()->service_id)->with('category', 'ingredients')
            ->when($request->category_id, fn($q, $v) => $q->where('category_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load('category', 'ingredients'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'nullable|in:available,unavailable',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity_used' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'selling_price' => $validated['selling_price'],
            'status' => $validated['status'] ?? 'available',
        ]);

        if (!empty($validated['ingredients'])) {
            $ingredients = collect($validated['ingredients'])
                ->mapWithKeys(fn($i) => [$i['id'] => ['quantity_used' => $i['quantity_used']]]);
            $product->ingredients()->sync($ingredients);
        }

        return response()->json($product->load('category', 'ingredients'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'status' => 'nullable|in:available,unavailable',
        ]);

        $product->update($validated);

        return response()->json($product->load('category', 'ingredients'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->ingredients()->detach();
        $product->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }
}
