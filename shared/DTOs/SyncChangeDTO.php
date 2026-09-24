<?php

namespace Shared\DTOs;

class SyncChangeDTO
{
    public function __construct(
        public readonly string $table,
        public readonly int $recordId,
        public readonly string $action,
        public readonly array $data,
        public readonly ?string $syncedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            table: $data['table'],
            recordId: $data['record_id'],
            action: $data['action'],
            data: $data['data'],
            syncedAt: $data['synced_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'record_id' => $this->recordId,
            'action' => $this->action,
            'data' => $this->data,
            'synced_at' => $this->syncedAt,
        ];
    }
}
