<?php

namespace App\Console\Commands;

use App\Models\OperationHistory;
use Illuminate\Console\Command;

class PruneOperationHistoryCommand extends Command
{
    protected $signature = 'history:prune {--days= : Override the configured retention period in days}';

    protected $description = 'Archive (soft-delete) operation history entries older than the retention period';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('pishock.history_retention_days');

        if ($days <= 0) {
            $this->info('History pruning is disabled (retention period is 0 or less).');

            return self::SUCCESS;
        }

        $pruned = OperationHistory::pruneOld($days);

        $this->info("Archived {$pruned} history " . str('entry')->plural($pruned) . " older than {$days} day(s).");

        return self::SUCCESS;
    }
}
