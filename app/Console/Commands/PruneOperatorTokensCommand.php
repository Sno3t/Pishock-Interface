<?php

namespace App\Console\Commands;

use App\Models\OperatorToken;
use Illuminate\Console\Command;

class PruneOperatorTokensCommand extends Command
{
    protected $signature = 'operators:prune {--days= : Override the configured retention period in days}';

    protected $description = 'Archive (soft-delete) operator links that have been revoked or expired for longer than the retention period';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('pishock.operator_token_retention_days');

        if ($days <= 0) {
            $this->info('Operator link pruning is disabled (retention period is 0 or less).');

            return self::SUCCESS;
        }

        $pruned = OperatorToken::pruneInactive($days);

        $this->info("Archived {$pruned} operator link" . ($pruned === 1 ? '' : 's') . " inactive for over {$days} day(s).");

        return self::SUCCESS;
    }
}
