<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ingredients = Ingredient::withCount('products')
            ->when($request->low_stock, fn($q) => $q->whereColumn('quantity', '<=', 'alert_level'))
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($ingredients);
    }

    public function show(Ingredient $ingredient): JsonResponse
    {
        return response()->json($ingredient->load('products', 'stockMovements'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients',
            'quantity' => 'required|numeric|min:0',
            'alert_level' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json($ingredient, 201);
    }

    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'quantity' => 'sometimes|numeric|min:0',
            'alert_level' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
        ]);

        $ingredient->update($validated);

        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient): JsonResponse
    {
        if ($ingredient->products()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un ingrédient lié à des produits.',
            ], 422);
        }
        $ingredient->delete();

        return response()->json(['message' => 'Ingrédient supprimé.']);
    }
}
