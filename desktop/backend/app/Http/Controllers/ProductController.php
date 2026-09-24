<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index', 'show']),
            new Middleware('permission:products.create', only: ['create', 'store']),
            new Middleware('permission:products.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = Product::forService(auth()->user()->service_id)->with(['category', 'currency']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Category::forService(auth()->user()->service_id)->orderBy('name')->get();
        $currencies = Currency::all();
        $exchangeRates = ExchangeRate::with('currency')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates,
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create()
    {
        $categories = Category::forService(auth()->user()->service_id)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();

        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'status' => ['required', 'in:available,unavailable'],
            'image' => ['nullable', 'image', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.id' => ['exists:ingredients,id'],
            'ingredients.*.quantity' => ['numeric', 'min:0'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'base_price' => $validated['base_price'],
            'selling_price' => $validated['selling_price'],
            'status' => $validated['status'],
            'image' => $validated['image'] ?? null,
        ]);

        // Attach ingredients
        if (!empty($validated['ingredients'])) {
            foreach ($validated['ingredients'] as $ingredient) {
                $product->ingredients()->attach($ingredient['id'], [
                    'quantity_needed' => $ingredient['quantity'],
                ]);
            }
        }

        ActivityLog::logCreation('Product', $product->id);

        return redirect()->route('products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product)
    {
        return Inertia::render('Products/Show', [
            'product' => $product->load(['category', 'ingredients']),
        ]);
    }

    public function edit(Product $product)
    {
        $categories = Category::forService(auth()->user()->service_id)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();

        return Inertia::render('Products/Edit', [
            'product' => $product->load('ingredients'),
            'categories' => $categories,
            'ingredients' => $ingredients,
            'currencies' => $currencies,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'status' => ['required', 'in:available,unavailable'],
            'image' => ['nullable', 'image', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.id' => ['exists:ingredients,id'],
            'ingredients.*.quantity' => ['numeric', 'min:0'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'base_price' => $validated['base_price'],
            'selling_price' => $validated['selling_price'],
            'currency_id' => $validated['currency_id'],
            'status' => $validated['status'],
            'image' => $validated['image'] ?? $product->image,
        ]);

        // Sync ingredients
        if (isset($validated['ingredients'])) {
            $syncData = [];
            foreach ($validated['ingredients'] as $ingredient) {
                $syncData[$ingredient['id']] = ['quantity_needed' => $ingredient['quantity']];
            }
            $product->ingredients()->sync($syncData);
        }

        ActivityLog::logUpdate('Product', $product->id);

        return redirect()->route('products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        // Check if product has orders
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Ce produit a des commandes associées et ne peut pas être supprimé.');
        }

        // Delete image
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        ActivityLog::logDeletion('Product', $product->id);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 'available' ? 'unavailable' : 'available',
        ]);

        ActivityLog::logUpdate('Product', $product->id, ['status' => $product->status]);

        return back()->with('success', 'Statut mis à jour.');
    }
}
