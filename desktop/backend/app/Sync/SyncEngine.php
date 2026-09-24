<?php

namespace App\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEngine
{
    private string $gatewayUrl;
    private string $deviceId;
    private ?string $token;

    public function __construct()
    {
        $this->gatewayUrl = config('sync.gateway_url', env('GATEWAY_URL', 'http://localhost:8001'));
        $this->deviceId = config('sync.device_id', env('DEVICE_ID', 'desktop-1'));
        $this->token = null;
    }

    public function authenticate(string $email, string $password): bool
    {
        try {
            $response = Http::post("{$this->gatewayUrl}/api/auth/login", [
                'email' => $email,
                'password' => $password,
                'device_name' => $this->deviceId,
            ]);

            if ($response->successful()) {
                $this->token = $response->json('token');
                cache(['sync_token' => $this->token], now()->addDays(30));
                cache(['sync_authenticated' => true], now()->addDays(30));
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Sync auth failed: ' . $e->getMessage());
            return false;
        }
    }

    public function push(): SyncResult
    {
        $changes = $this->getPendingChanges();
        if (empty($changes)) {
            return new SyncResult(status: 'no_changes');
        }

        try {
            $response = Http::withToken($this->getToken())
                ->post("{$this->gatewayUrl}/api/sync/push", [
                    'changes' => $changes,
                    'device_id' => $this->deviceId,
                    'last_sync_at' => cache('last_sync_at'),
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $this->markAsSynced($changes, $result['synced'] ?? []);
                cache(['last_sync_at' => now()->toIso8601String()]);

                return new SyncResult(
                    status: 'success',
                    synced: count($result['synced'] ?? []),
                    conflicts: $result['conflicts'] ?? [],
                );
            }

            return new SyncResult(status: 'failed', error: $response->body());
        } catch (\Exception $e) {
            Log::error('Sync push failed: ' . $e->getMessage());
            return new SyncResult(status: 'error', error: $e->getMessage());
        }
    }

    public function pull(): SyncResult
    {
        try {
            $response = Http::withToken($this->getToken())
                ->post("{$this->gatewayUrl}/api/sync/pull", [
                    'tables' => $this->getTrackedTables(),
                    'device_id' => $this->deviceId,
                    'last_sync_at' => cache('last_sync_at'),
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $this->applyRemoteChanges($result['changes'] ?? []);
                cache(['last_sync_at' => now()->toIso8601String()]);

                return new SyncResult(
                    status: 'success',
                    pulled: count($result['changes'] ?? []),
                );
            }

            return new SyncResult(status: 'failed', error: $response->body());
        } catch (\Exception $e) {
            Log::error('Sync pull failed: ' . $e->getMessage());
            return new SyncResult(status: 'error', error: $e->getMessage());
        }
    }

    public function fullSync(): SyncResult
    {
        $pushResult = $this->push();
        if ($pushResult->failed()) {
            return $pushResult;
        }

        return $this->pull();
    }

    public static function trackChange(string $table, int $recordId, string $action, array $data): void
    {
        DB::table('sync_changelog')->insert([
            'table' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'payload' => json_encode($data),
            'status' => 'pending',
            'created_at' => now(),
        ]);
    }

    private function getPendingChanges(): array
    {
        return DB::table('sync_changelog')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'table' => $row->table,
                'record_id' => $row->record_id,
                'action' => $row->action,
                'data' => json_decode($row->payload, true),
                'synced_at' => $row->created_at,
            ])
            ->toArray();
    }

    private function markAsSynced(array $changes, array $synced): void
    {
        foreach ($synced as $item) {
            DB::table('sync_changelog')
                ->where('table', $item['table'])
                ->where('record_id', $item['record_id'])
                ->where('status', 'pending')
                ->update([
                    'status' => 'synced',
                    'synced_at' => now(),
                ]);
        }
    }

    private function applyRemoteChanges(array $changes): void
    {
        foreach ($changes as $change) {
            DB::table($change['table'])
                ->updateOrInsert(
                    ['id' => $change['record_id']],
                    $change['data']
                );
        }
    }

    private function getTrackedTables(): array
    {
        return [
            'orders', 'order_items', 'payments', 'products',
            'categories', 'ingredients', 'stock_movements',
            'servers', 'cashier_sessions', 'kitchen_sessions',
        ];
    }

    private function getToken(): string
    {
        return $this->token ?? cache('sync_token', '');
    }
}
