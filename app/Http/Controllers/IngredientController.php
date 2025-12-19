<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class IngredientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stocks.view', only: ['index', 'show', 'movements']),
            new Middleware('permission:stocks.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
            new Middleware('permission:stocks.in', only: ['addStock']),
            new Middleware('permission:stocks.out', only: ['removeStock']),
        ];
    }

    public function index(Request $request)
    {
        $query = Ingredient::query();

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Filter by low stock
        if ($request->filled('low_stock') && $request->low_stock === 'true') {
            $query->whereColumn('quantity', '<=', 'alert_level');
        }

        $ingredients = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Stocks/Index', [
            'ingredients' => $ingredients,
            'filters' => $request->only(['search', 'low_stock']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Stocks/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'alert_level' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $ingredient = Ingredient::create($validated);

        // Log initial stock
        if ($validated['quantity'] > 0) {
            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'reason' => 'Stock initial',
            ]);
        }

        ActivityLog::logCreation('Ingredient', $ingredient->id);

        return redirect()->route('stocks.index')
            ->with('success', 'Ingrédient créé avec succès.');
    }

    public function edit(Ingredient $ingredient)
    {
        return Inertia::render('Stocks/Edit', [
            'ingredient' => $ingredient,
        ]);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name,' . $ingredient->id],
            'alert_level' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $ingredient->update($validated);

        ActivityLog::logUpdate('Ingredient', $ingredient->id);

        return redirect()->route('stocks.index')
            ->with('success', 'Ingrédient mis à jour avec succès.');
    }

    public function destroy(Ingredient $ingredient)
    {
        // Check if ingredient is used in products
        if ($ingredient->products()->exists()) {
            return back()->with('error', 'Cet ingrédient est utilisé dans des produits et ne peut pas être supprimé.');
        }

        ActivityLog::logDeletion('Ingredient', $ingredient->id);

        $ingredient->delete();

        return redirect()->route('stocks.index')
            ->with('success', 'Ingrédient supprimé avec succès.');
    }

    public function addStock(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ingredient->addStock($validated['quantity']);

        StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => auth()->id(),
            'type' => 'in',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Ajout de stock',
        ]);

        ActivityLog::log('stock_add', 'Ingredient', [
            'id' => $ingredient->id,
            'quantity' => $validated['quantity'],
        ]);

        return back()->with('success', 'Stock ajouté avec succès.');
    }

    public function removeStock(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:' . $ingredient->quantity],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ingredient->removeStock($validated['quantity']);

        StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => auth()->id(),
            'type' => 'out',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Retrait de stock',
        ]);

        ActivityLog::log('stock_remove', 'Ingredient', [
            'id' => $ingredient->id,
            'quantity' => $validated['quantity'],
        ]);

        return back()->with('success', 'Stock retiré avec succès.');
    }

    public function movements(Ingredient $ingredient)
    {
        $movements = $ingredient->stockMovements()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Stocks/Movements', [
            'ingredient' => $ingredient,
            'movements' => $movements,
        ]);
    }
}
