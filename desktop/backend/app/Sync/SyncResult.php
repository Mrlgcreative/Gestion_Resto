<?php

namespace App\Sync;

class SyncResult
{
    public function __construct(
        public readonly string $status,
        public readonly int $synced = 0,
        public readonly int $pulled = 0,
        public readonly array $conflicts = [],
        public readonly ?string $error = null,
    ) {}

    public function failed(): bool
    {
        return in_array($this->status, ['failed', 'error']);
    }

    public function succeeded(): bool
    {
        return $this->status === 'success';
    }
}
