<?php

namespace App\Console\Commands;

use App\Sync\SyncEngine;
use App\Sync\SyncResult;

use Illuminate\Console\Command;

class SyncCommand extends Command
{
    protected $signature = 'sync:run {--direction=both : push, pull, or both}';
    protected $description = 'Synchronise les données avec le gateway';

    public function handle(SyncEngine $engine): int
    {
        if (!cache('sync_authenticated', false)) {
            $this->error('Non authentifié. Lancez d\'abord sync:auth');
            return Command::FAILURE;
        }

        $direction = $this->option('direction');

        if (in_array($direction, ['push', 'both'])) {
            $this->info('Push des modifications locales...');
            $result = $engine->push();
            $this->outputResult('Push', $result);
        }

        if (in_array($direction, ['pull', 'both'])) {
            $this->info('Pull des modifications distantes...');
            $result = $engine->pull();
            $this->outputResult('Pull', $result);
        }

        return Command::SUCCESS;
    }

    private function outputResult(string $label, SyncResult $result): void
    {
        if ($result->succeeded()) {
            $this->info("✓ {$label} terminé");
            if ($result->synced > 0) {
                $this->line("  {$result->synced} enregistrements pusher");
            }
            if ($result->pulled > 0) {
                $this->line("  {$result->pulled} enregistrements puller");
            }
        } else {
            $this->error("✗ {$label} échoué: {$result->error}");
        }
    }
}
