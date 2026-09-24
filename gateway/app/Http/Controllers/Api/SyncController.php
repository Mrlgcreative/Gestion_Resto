<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function push(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'changes' => 'required|array',
            'changes.*.table' => 'required|string',
            'changes.*.record_id' => 'required|integer',
            'changes.*.action' => 'required|in:created,updated,deleted',
            'changes.*.data' => 'required|array',
            'changes.*.synced_at' => 'nullable|date',
            'device_id' => 'required|string',
            'last_sync_at' => 'nullable|date',
        ]);

        // TODO: Process changes, detect conflicts, apply to server DB
        // 1. For each change, check if server has newer version
        // 2. If no conflict, apply and return success
        // 3. If conflict, return conflict details

        $results = [];
        foreach ($payload['changes'] as $change) {
            $results[] = [
                'table' => $change['table'],
                'record_id' => $change['record_id'],
                'status' => 'synced',
            ];
        }

        return response()->json([
            'synced' => $results,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'tables' => 'required|array',
            'tables.*' => 'string',
            'last_sync_at' => 'nullable|date',
            'device_id' => 'required|string',
        ]);

        $userServiceId = auth()->user()->service_id;

        $changes = [];

        return response()->json([
            'changes' => $changes,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function resolveConflict(Request $request): JsonResponse
    {
        $request->validate([
            'table' => 'required|string',
            'record_id' => 'required|integer',
            'resolution' => 'required|in:local,remote',
        ]);

        // TODO: Apply resolution strategy
        return response()->json([
            'status' => 'resolved',
        ]);
    }
}
