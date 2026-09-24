<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ServerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:servers.view', only: ['index', 'show', 'stats']),
            new Middleware('permission:servers.create', only: ['create', 'store']),
            new Middleware('permission:servers.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:servers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = Server::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $servers = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Servers/Index', [
            'servers' => $servers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Servers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $server = Server::create($validated);

        ActivityLog::logCreation('Server', $server->id);

        return redirect()->route('servers.index')
            ->with('success', 'Serveur créé avec succès.');
    }

    public function edit(Server $server)
    {
        return Inertia::render('Servers/Edit', [
            'server' => $server,
        ]);
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $server->update($validated);

        ActivityLog::logUpdate('Server', $server->id);

        return redirect()->route('servers.index')
            ->with('success', 'Serveur mis à jour avec succès.');
    }

    public function destroy(Server $server)
    {
        // Check if server has orders
        if ($server->orders()->exists()) {
            return back()->with('error', 'Ce serveur a des commandes associées et ne peut pas être supprimé.');
        }

        ActivityLog::logDeletion('Server', $server->id);

        $server->delete();

        return redirect()->route('servers.index')
            ->with('success', 'Serveur supprimé avec succès.');
    }

    public function toggleStatus(Server $server)
    {
        $server->update([
            'is_active' => !$server->is_active,
        ]);

        ActivityLog::logUpdate('Server', $server->id, ['is_active' => $server->is_active]);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function stats(Server $server)
    {
        return response()->json([
            'today_orders' => $server->todayOrders(),
            'today_sales' => $server->todaySales(),
        ]);
    }
}
