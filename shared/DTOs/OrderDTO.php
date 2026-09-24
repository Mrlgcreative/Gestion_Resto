<?php

namespace Shared\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $sessionId,
        public readonly int $userId,
        public readonly ?int $serverId,
        public readonly ?string $tableNumber,
        public readonly float $totalAmount,
        public readonly string $status,
        public readonly ?string $currency,
        public readonly ?float $exchangeRate,
        public readonly array $items,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            sessionId: $data['session_id'],
            userId: $data['user_id'],
            serverId: $data['server_id'] ?? null,
            tableNumber: $data['table_number'] ?? null,
            totalAmount: (float) $data['total_amount'],
            status: $data['status'],
            currency: $data['currency'] ?? null,
            exchangeRate: isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            items: $data['items'] ?? [],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'server_id' => $this->serverId,
            'table_number' => $this->tableNumber,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchangeRate,
            'items' => $this->items,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
